<?php

declare(strict_types=1);

namespace App\Job;

use App\DTO\WithdrawEmailDTO;
use App\Enum\PixKeyType;
use App\Model\AccountWithdraw;
use App\Processor\WithdrawProcessor;
use App\Repositories\AccountRepository;
use App\Repositories\WithdrawRepository;
use App\Services\EmailService;
use Hyperf\DbConnection\Db;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ProcessScheduledWithdrawsJob
{
    protected LoggerInterface $logger;
    protected WithdrawProcessor $processor;
    protected EmailService $emailService;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->logger = $container->get(LoggerInterface::class);
        $this->processor = $container->get(WithdrawProcessor::class);
        $this->emailService = $container->get(EmailService::class);
    }

    public function execute(): void
    {
        $startTime = microtime(true);
        $this->logger->info('Iniciando processamento de saques agendados');

        // Métricas do ciclo
        $metrics = [
            'total_pendentes' => 0,
            'processados' => 0,
            'sucesso' => 0,
            'falhas_saldo' => 0,
            'falhas_outras' => 0,
        ];

        $withdraws = $this->fetchPendingWithdraws();

        if ($withdraws->isEmpty()) {
            $this->logger->debug('Nenhum saque agendado pendente encontrado');
            return;
        }

        $metrics['total_pendentes'] = $withdraws->count();
        $this->logger->info('Saques pendentes encontrados', [
            'count' => $metrics['total_pendentes'],
        ]);

        foreach ($withdraws as $withdraw) {
            $result = $this->processWithdraw($withdraw);
            $metrics['processados']++;

            match ($result) {
                'success' => $metrics['sucesso']++,
                'insufficient_funds' => $metrics['falhas_saldo']++,
                'error' => $metrics['falhas_outras']++,
                default => null,
            };
        }

        $duration = round(microtime(true) - $startTime, 3);
        $avgTimePerWithdraw = $metrics['processados'] > 0 ? round($duration / $metrics['processados'], 3) : 0;

        $this->logger->info('Processamento de saques agendados concluído', [
            'metrics' => $metrics,
            'duration_seconds' => $duration,
            'avg_time_per_withdraw_seconds' => $avgTimePerWithdraw,
        ]);
    }

    /**
     * Busca saques agendados pendentes (máximo 50 por ciclo)
     */
    private function fetchPendingWithdraws()
    {
        return WithdrawRepository::getPendingScheduled(50);
    }

    /**
     * Processa um saque individual
     * @return string 'success' | 'insufficient_funds' | 'error' | 'skipped'
     */
    private function processWithdraw(AccountWithdraw $withdraw): string
    {
        $this->logger->info('Tentando adquirir lock para saque', [
            'withdraw_id' => $withdraw->id,
        ]);

        // Tenta assumir o lock atômico via UPDATE
        $lockedWithdraw = $this->processor->tryAcquireLock($withdraw->id);

        if (!$lockedWithdraw) {
            $this->logger->info('Lock não adquirido, outro processo já está processando', [
                'withdraw_id' => $withdraw->id,
            ]);
            return 'skipped';
        }

        $this->logger->info('Lock adquirido, iniciando processamento', [
            'withdraw_id' => $withdraw->id,
            'account_id' => $lockedWithdraw->account_id,
            'amount' => $lockedWithdraw->amount,
        ]);

        try {
            $result = 'success';
            $notification = null;

            Db::transaction(function () use ($lockedWithdraw, &$result, &$notification) {
                // Recarrega com lock pessimista na conta
                $account = AccountRepository::findWithLock($lockedWithdraw->account_id);

                if (!$account) {
                    $result = 'error';
                    $this->processor->processFailure($lockedWithdraw, 'conta não encontrada');
                    $this->logger->warning('Saque agendado falhou por conta inexistente', [
                        'withdraw_id' => $lockedWithdraw->id,
                        'account_id' => $lockedWithdraw->account_id,
                    ]);
                    return;
                }

                // Tenta processar o débito
                try {
                    $this->processor->processDebit($account, $lockedWithdraw);
                    $this->logger->info('Saque agendado processado com sucesso', [
                        'withdraw_id' => $lockedWithdraw->id,
                        'account_id' => $account->id,
                        'amount' => $lockedWithdraw->amount,
                    ]);

                    $pixData = $lockedWithdraw->pix?->toArray();
                    if ($pixData) {
                        $email = $pixData['type'] === 'email' ? $pixData['key'] : null;
                        if ($email) {
                            $lockedWithdraw->refresh();
                            $notification = new WithdrawEmailDTO(
                                email: $email,
                                amount: (float) $lockedWithdraw->amount,
                                processedAt: $lockedWithdraw->processed_at instanceof \DateTime
                                    ? $lockedWithdraw->processed_at
                                    : new \DateTime((string) $lockedWithdraw->processed_at),
                                withdrawId: $lockedWithdraw->id,
                                pixType: PixKeyType::EMAIL,
                                pixKey: $email
                            );
                        }
                    }
                } catch (\RuntimeException $e) {
                    // Saldo insuficiente - marca como falho
                    if ($e->getCode() === 400) {
                        $result = 'insufficient_funds';
                        $this->processor->processFailure($lockedWithdraw, 'saldo insuficiente');
                        $this->logger->warning('Saque agendado falhou por saldo insuficiente', [
                            'withdraw_id' => $lockedWithdraw->id,
                            'account_id' => $account->id,
                            'amount' => $lockedWithdraw->amount,
                            'current_balance' => $account->balance,
                        ]);
                    } else {
                        throw $e;
                    }
                }
            });

            if ($notification instanceof WithdrawEmailDTO) {
                $this->emailService->sendWithdrawCompleted($notification);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('Erro ao processar saque agendado', [
                'withdraw_id' => $lockedWithdraw->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Libera o lock em caso de exceção para permitir nova tentativa
            $this->processor->releaseLock($lockedWithdraw);

            return 'error';
        }
    }
}
