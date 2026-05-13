<?php

declare(strict_types=1);

namespace HyperfTest\Feature;

use App\Job\ProcessScheduledWithdrawsJob;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Testing\TestCase;
use Ramsey\Uuid\Uuid;

use function Hyperf\Support\env;
use function Hyperf\Support\make;

/**
 * @internal
 * @coversNothing
 */
class WithdrawFlowTest extends TestCase
{
    private const TEST_ACCOUNT_PREFIX = 'TEST Saque PIX';

    private const TEST_EMAIL_DOMAIN = 'saque-pix.test';

    private const MAILPIT_INTERNAL_WEB_PORT = 8025;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cleanupTestData();
        $this->clearMailpit();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
        parent::tearDown();
    }

    public function testImmediateWithdrawPersistsDebitPixRecordAndSendsEmail(): void
    {
        $accountId = $this->createAccount('immediate', '1000.00');
        $email = $this->testEmail('immediate');

        $response = $this->post("/account/{$accountId}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => $email],
            'amount' => 150.75,
            'schedule' => null,
        ]);

        $response->assertStatus(200);
        $body = $this->jsonBody($response);
        $withdrawId = $body['withdraw_id'] ?? null;

        self::assertNotEmpty($withdrawId);
        self::assertSame('completed', $body['status'] ?? null);
        self::assertSame('849.25', $this->accountBalance($accountId));

        $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
        self::assertSame($accountId, $withdraw->account_id);
        self::assertSame('PIX', $withdraw->method);
        self::assertSame('150.75', (string) $withdraw->amount);
        self::assertSame(0, (int) $withdraw->scheduled);
        self::assertSame(1, (int) $withdraw->done);
        self::assertSame(0, (int) $withdraw->error);
        self::assertNotNull($withdraw->processed_at);

        $pix = Db::table('account_withdraw_pix')->where('account_withdraw_id', $withdrawId)->first();
        self::assertSame('email', $pix->type);
        self::assertSame($email, $pix->key);

        self::assertTrue($this->mailpitReceived($email), 'Mailpit não recebeu o email do saque imediato.');
    }

    public function testRejectsInsufficientFundsAndPastScheduleWithHttp400(): void
    {
        $accountId = $this->createAccount('insufficient', '10.00');

        $insufficientResponse = $this->post("/account/{$accountId}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => $this->testEmail('insufficient')],
            'amount' => 999.00,
            'schedule' => null,
        ]);

        $insufficientResponse->assertStatus(400);
        self::assertSame('10.00', $this->accountBalance($accountId));
        self::assertSame(0, Db::table('account_withdraw')->where('account_id', $accountId)->count());

        $pastScheduleResponse = $this->post("/account/{$accountId}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => $this->testEmail('past')],
            'amount' => 1.00,
            'schedule' => date('Y-m-d H:i:s', time() - 300),
        ]);

        $pastScheduleResponse->assertStatus(400);
    }

    public function testScheduledWithdrawStoresScheduledForWithoutDebitingBalance(): void
    {
        $accountId = $this->createAccount('scheduled-future', '500.00');
        $scheduledFor = date('Y-m-d H:i:s', time() + 600);

        $response = $this->post("/account/{$accountId}/balance/withdraw", [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => $this->testEmail('scheduled')],
            'amount' => 100.00,
            'schedule' => $scheduledFor,
        ]);

        $response->assertStatus(200);
        $body = $this->jsonBody($response);
        $withdrawId = $body['withdraw_id'] ?? null;

        self::assertSame('scheduled', $body['status'] ?? null);
        self::assertSame('500.00', $this->accountBalance($accountId));

        $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
        self::assertSame(1, (int) $withdraw->scheduled);
        self::assertSame(0, (int) $withdraw->done);
        self::assertSame(0, (int) $withdraw->error);
        self::assertSame($scheduledFor, $withdraw->scheduled_for);
        self::assertNull($withdraw->processed_at);
    }

    public function testScheduledWithdrawProcessingSuccessFailureAndIdempotency(): void
    {
        $successAccountId = $this->createAccount('scheduled-due-success', '500.00');
        $successWithdrawId = $this->createDueScheduledWithdraw($successAccountId, '100.00', $this->testEmail('due-success'));

        $failureAccountId = $this->createAccount('scheduled-due-failure', '10.00');
        $failureWithdrawId = $this->createDueScheduledWithdraw($failureAccountId, '100.00', $this->testEmail('due-failure'));

        $this->waitForWithdraw($successWithdrawId, static fn (object $withdraw) => (int) $withdraw->done === 1);
        $this->waitForWithdraw($failureWithdrawId, static fn (object $withdraw) => (int) $withdraw->done === 1);

        $successWithdraw = Db::table('account_withdraw')->where('id', $successWithdrawId)->first();
        self::assertSame(1, (int) $successWithdraw->done);
        self::assertSame(0, (int) $successWithdraw->error);
        self::assertSame(0, (int) $successWithdraw->processing);
        self::assertNotNull($successWithdraw->processed_at);
        self::assertSame('400.00', $this->accountBalance($successAccountId));

        $balanceAfterFirstRun = $this->accountBalance($successAccountId);
        $processedAtAfterFirstRun = $successWithdraw->processed_at;

        sleep(6);

        $successWithdrawAfterSecondRun = Db::table('account_withdraw')->where('id', $successWithdrawId)->first();
        self::assertSame($balanceAfterFirstRun, $this->accountBalance($successAccountId));
        self::assertSame($processedAtAfterFirstRun, $successWithdrawAfterSecondRun->processed_at);

        $failureWithdraw = Db::table('account_withdraw')->where('id', $failureWithdrawId)->first();
        self::assertSame(1, (int) $failureWithdraw->done);
        self::assertSame(1, (int) $failureWithdraw->error);
        self::assertSame(0, (int) $failureWithdraw->processing);
        self::assertSame('saldo insuficiente', $failureWithdraw->error_reason);
        self::assertNotNull($failureWithdraw->processed_at);
        self::assertSame('10.00', $this->accountBalance($failureAccountId));
    }

    public function testScheduledWithdrawCannotMakeBalanceNegative(): void
    {
        $accountId = $this->createAccount('scheduled-negative-balance', '50.00');
        $withdrawId = $this->createDueScheduledWithdraw($accountId, '75.00', $this->testEmail('scheduled-negative'));

        $this->waitForWithdraw($withdrawId, static fn (object $withdraw) => (int) $withdraw->done === 1);

        $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
        self::assertSame(1, (int) $withdraw->done);
        self::assertSame(1, (int) $withdraw->error);
        self::assertSame(0, (int) $withdraw->processing);
        self::assertSame('saldo insuficiente', $withdraw->error_reason);
        self::assertNotNull($withdraw->processed_at);

        self::assertSame('50.00', $this->accountBalance($accountId));
        self::assertSame(0, Db::table('account')->where('id', $accountId)->where('balance', '<', 0)->count());
    }

    public function testScheduledProcessingReleasesLockWhenUnexpectedExceptionHappens(): void
    {
        $withdrawId = (string) Uuid::uuid4();
        $missingAccountId = (string) Uuid::uuid4();

        Db::statement('SET FOREIGN_KEY_CHECKS=0');
        Db::table('account_withdraw')->insert([
            'id' => $withdrawId,
            'account_id' => $missingAccountId,
            'method' => 'PIX',
            'amount' => '1.00',
            'scheduled' => true,
            'scheduled_for' => date('Y-m-d H:i:s', time() - 60),
            'done' => false,
            'error' => false,
            'processing' => false,
            'error_reason' => null,
            'processed_at' => null,
        ]);
        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawId,
            'type' => 'email',
            'key' => $this->testEmail('broken-row'),
        ]);
        Db::statement('SET FOREIGN_KEY_CHECKS=1');

        make(ProcessScheduledWithdrawsJob::class)->execute();

        $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
        self::assertSame(0, (int) $withdraw->done);
        self::assertSame(0, (int) $withdraw->error);
        self::assertSame(0, (int) $withdraw->processing);
        self::assertNull($withdraw->processed_at);
    }

    public function testExtremeConcurrencyApprovesOnlyAvailableBalanceAndNeverGoesNegative(): void
    {
        if (! extension_loaded('curl')) {
            self::markTestSkipped('Extensão curl é necessária para o teste de concorrência HTTP real.');
        }

        $accountId = $this->createAccount('concurrency-extreme', '100.00');
        $payload = [
            'method' => 'PIX',
            'pix' => ['type' => 'email', 'key' => $this->testEmail('concurrency')],
            'amount' => 1.00,
            'schedule' => null,
        ];

        $statuses = $this->sendConcurrentWithdrawRequests($accountId, $payload, 1000);
        $approved = count(array_filter($statuses, static fn (int $status) => $status === 200));
        $rejected = count(array_filter($statuses, static fn (int $status) => $status === 400));
        $unexpected = count($statuses) - $approved - $rejected;

        self::assertSame(100, $approved);
        self::assertSame(900, $rejected);
        self::assertSame(0, $unexpected);
        self::assertSame('0.00', $this->accountBalance($accountId));
        self::assertSame(0, Db::table('account')->where('balance', '<', 0)->count());
    }

    public function testCrontabConfigurationIsActive(): void
    {
        $config = make(ConfigInterface::class);
        $processes = $config->get('processes', []);
        $crontab = $config->get('crontab', []);

        self::assertContains(\Hyperf\Crontab\Process\CrontabDispatcherProcess::class, $processes);
        self::assertTrue((bool) ($crontab['enable'] ?? false));
        self::assertSame('*/5 * * * * *', $crontab['crontab'][0]['rule'] ?? null);
        self::assertTrue((bool) ($crontab['crontab'][0]['enable'] ?? false));
    }

    private function createAccount(string $suffix, string $balance): string
    {
        $accountId = (string) Uuid::uuid4();

        Db::table('account')->insert([
            'id' => $accountId,
            'name' => self::TEST_ACCOUNT_PREFIX . ' ' . $suffix,
            'balance' => $balance,
        ]);

        return $accountId;
    }

    private function createDueScheduledWithdraw(string $accountId, string $amount, string $email): string
    {
        $withdrawId = (string) Uuid::uuid4();

        Db::table('account_withdraw')->insert([
            'id' => $withdrawId,
            'account_id' => $accountId,
            'method' => 'PIX',
            'amount' => $amount,
            'scheduled' => true,
            'scheduled_for' => date('Y-m-d H:i:s', time() - 60),
            'done' => false,
            'error' => false,
            'processing' => false,
            'error_reason' => null,
            'processed_at' => null,
        ]);

        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawId,
            'type' => 'email',
            'key' => $email,
        ]);

        return $withdrawId;
    }

    private function accountBalance(string $accountId): string
    {
        return (string) Db::table('account')->where('id', $accountId)->value('balance');
    }

    private function waitForWithdraw(string $withdrawId, callable $condition, int $timeoutSeconds = 30): object
    {
        $deadline = time() + $timeoutSeconds;

        do {
            $withdraw = Db::table('account_withdraw')->where('id', $withdrawId)->first();
            if ($withdraw !== null && $condition($withdraw)) {
                return $withdraw;
            }

            usleep(500000);
        } while (time() < $deadline);

        self::fail("Saque agendado {$withdrawId} não foi processado pela cron dentro de {$timeoutSeconds}s.");
    }

    private function testEmail(string $prefix): string
    {
        return sprintf('%s-%s@%s', $prefix, substr(str_replace('-', '', (string) Uuid::uuid4()), 0, 12), self::TEST_EMAIL_DOMAIN);
    }

    private function jsonBody(mixed $response): array
    {
        if (method_exists($response, 'json')) {
            $json = $response->json();
            if (is_array($json)) {
                return $json;
            }
        }

        if (method_exists($response, 'getContent')) {
            $content = $response->getContent();
        } elseif (method_exists($response, 'getBody')) {
            $content = (string) $response->getBody();
        } else {
            $content = (string) $response;
        }

        return json_decode($content, true) ?: [];
    }

    private function cleanupTestData(): void
    {
        Db::statement('SET FOREIGN_KEY_CHECKS=0');

        Db::delete(
            "DELETE FROM account_withdraw WHERE id IN (
                SELECT account_withdraw_id FROM account_withdraw_pix WHERE `key` LIKE ?
            )",
            ['%@' . self::TEST_EMAIL_DOMAIN]
        );
        Db::delete('DELETE FROM account_withdraw_pix WHERE `key` LIKE ?', ['%@' . self::TEST_EMAIL_DOMAIN]);
        Db::delete(
            "DELETE aw FROM account_withdraw aw
             INNER JOIN account a ON a.id = aw.account_id
             WHERE a.name LIKE ?",
            [self::TEST_ACCOUNT_PREFIX . '%']
        );
        Db::delete('DELETE FROM account WHERE name LIKE ?', [self::TEST_ACCOUNT_PREFIX . '%']);

        Db::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function clearMailpit(): void
    {
        $baseUrl = $this->mailpitBaseUrl();
        if ($baseUrl === null) {
            return;
        }

        $this->httpRequest('DELETE', $baseUrl . '/api/v1/messages');
    }

    private function mailpitReceived(string $email): bool
    {
        $baseUrl = $this->mailpitBaseUrl();
        if ($baseUrl === null) {
            return false;
        }

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $body = $this->httpRequest('GET', $baseUrl . '/api/v1/messages');
            if ($body !== null && str_contains(strtolower($body), strtolower($email))) {
                return true;
            }

            usleep(300000);
        }

        return false;
    }

    private function mailpitBaseUrl(): ?string
    {
        $mailHost = (string) env('MAIL_HOST', 'mailpit');

        $candidates = array_filter([
            sprintf('http://%s:%d', $mailHost, self::MAILPIT_INTERNAL_WEB_PORT),
        ]);

        foreach ($candidates as $candidate) {
            if ($this->httpRequest('GET', $candidate . '/api/v1/messages') !== null) {
                return $candidate;
            }
        }

        return null;
    }

    private function httpRequest(string $method, string $url, ?string $body = null): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => "Content-Type: application/json\r\n",
                'content' => $body ?? '',
                'ignore_errors' => true,
                'timeout' => 3,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return $result === false ? null : $result;
    }

    /**
     * @return array<int>
     */
    private function sendConcurrentWithdrawRequests(string $accountId, array $payload, int $total): array
    {
        $host = (string) env('SERVER_HOST', '127.0.0.1');
        $port = (int) env('SERVER_PORT', 9502);
        $baseUrl = sprintf('http://%s:%d', $host === '0.0.0.0' ? '127.0.0.1' : $host, $port);
        $url = "{$baseUrl}/account/{$accountId}/balance/withdraw";
        $json = json_encode($payload);
        $statuses = [];
        $multi = curl_multi_init();
        $handles = [];
        $next = 0;
        $maxConcurrent = 100;

        $addHandle = function () use (&$next, $total, $url, $json, $multi, &$handles): void {
            if ($next >= $total) {
                return;
            }

            $handle = curl_init($url);
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $json,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 60,
            ]);

            $handles[spl_object_id($handle)] = $handle;
            curl_multi_add_handle($multi, $handle);
            $next++;
        };

        for ($i = 0; $i < min($maxConcurrent, $total); $i++) {
            $addHandle();
        }

        do {
            do {
                $status = curl_multi_exec($multi, $running);
            } while ($status === CURLM_CALL_MULTI_PERFORM);

            while ($info = curl_multi_info_read($multi)) {
                $handle = $info['handle'];
                $statuses[] = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
                curl_multi_remove_handle($multi, $handle);
                curl_close($handle);
                unset($handles[spl_object_id($handle)]);
                $addHandle();
            }

            if ($running) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running || $next < $total || $handles !== []);

        curl_multi_close($multi);

        return $statuses;
    }
}
