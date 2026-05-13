<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\WithdrawEmailDTO;
use App\Enum\PixKeyType;
use App\Model\AccountWithdraw;
use App\Processor\WithdrawProcessor;
use App\Repositories\AccountRepository;
use App\Strategy\WithdrawMethodResolver;
use Hyperf\DbConnection\Db;
use Psr\Log\LoggerInterface;

class WithdrawService
{
    public function __construct(
        protected WithdrawMethodResolver $methodResolver,
        protected WithdrawProcessor $processor,
        protected EmailService $emailService,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Processa um saque imediato
     *
     * @param string $accountId UUID da conta
     * @param string $method Método de saque (PIX, TED, etc) - ver App\Enum\WithdrawMethod
     * @param array $methodData Dados específicos do método
     * @param float $amount Valor do saque
     * @return array Resultado do saque
     */
    public function processImmediateWithdraw(
        string $accountId,
        string $method,
        array $methodData,
        float $amount
    ): array {
        $this->logger->info('Iniciando saque imediato', [
            'account_id' => $accountId,
            'method' => $method,
            'amount' => $amount,
        ]);

        $handler = $this->methodResolver->resolve($method);
        $handler->validate($methodData);

        $withdraw = null;

        $result = Db::transaction(function () use ($accountId, $method, $amount, $methodData, $handler, &$withdraw) {
            $account = AccountRepository::findWithLock($accountId);

            if (!$account) {
                throw new \RuntimeException('Conta não encontrada', 404);
            }

            $withdraw = $this->processor->createWithdrawRecord(
                $account,
                $method,
                $amount,
                false,
                null
            );

            $handler->persist($withdraw, $methodData);

            $this->processor->processDebit($account, $withdraw);

            $this->logger->info('Saque imediato concluído', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $accountId,
                'amount' => $amount,
            ]);

            return [
                'withdraw_id' => $withdraw->id,
                'status' => 'completed',
                'amount' => $amount,
                'processed_at' => $withdraw->processed_at,
            ];
        });

        // Enviar email de notificação após o commit da transação
        $email = $handler->getNotificationEmail($methodData);
        if ($email && $withdraw) {
            $withdraw->refresh();
            $dto = new WithdrawEmailDTO(
                email: $email,
                amount: (float) $withdraw->amount,
                processedAt: $withdraw->processed_at instanceof \DateTime
                    ? $withdraw->processed_at
                    : new \DateTime((string) $withdraw->processed_at),
                withdrawId: $withdraw->id,
                pixType: PixKeyType::EMAIL,
                pixKey: $email
            );
            $this->emailService->sendWithdrawCompleted($dto);
        }

        return $result;
    }

    /**
     * Agenda um saque para processamento futuro
     *
     * @param string $accountId UUID da conta
     * @param string $method Método de saque
     * @param array $methodData Dados específicos do método
     * @param float $amount Valor do saque
     * @param string $scheduledFor Data/hora do agendamento (formato ISO 8601)
     * @return array Resultado do agendamento
     */
    public function scheduleWithdraw(
        string $accountId,
        string $method,
        array $methodData,
        float $amount,
        string $scheduledFor
    ): array {
        $this->logger->info('Iniciando agendamento de saque', [
            'account_id' => $accountId,
            'method' => $method,
            'amount' => $amount,
            'scheduled_for' => $scheduledFor,
        ]);

        $scheduledDate = new \DateTime($scheduledFor);
        $now = new \DateTime();

        if ($scheduledDate <= $now) {
            throw new \InvalidArgumentException('Data de agendamento deve ser futura');
        }

        $handler = $this->methodResolver->resolve($method);
        $handler->validate($methodData);

        return Db::transaction(function () use ($accountId, $method, $amount, $methodData, $scheduledDate, $handler) {
            $account = AccountRepository::findById($accountId);

            if (!$account) {
                throw new \RuntimeException('Conta não encontrada', 404);
            }

            $withdraw = $this->processor->createWithdrawRecord(
                $account,
                $method,
                $amount,
                true,
                $scheduledDate->format('Y-m-d H:i:s')
            );

            $handler->persist($withdraw, $methodData);

            $this->logger->info('Saque agendado com sucesso', [
                'withdraw_id' => $withdraw->id,
                'account_id' => $accountId,
                'amount' => $amount,
                'scheduled_for' => $withdraw->scheduled_for,
            ]);

            return [
                'withdraw_id' => $withdraw->id,
                'status' => 'scheduled',
                'amount' => $amount,
                'scheduled_for' => $withdraw->scheduled_for,
            ];
        });
    }
}
