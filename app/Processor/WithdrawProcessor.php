<?php

declare(strict_types=1);

namespace App\Processor;

use App\Model\Account;
use App\Model\AccountWithdraw;
use App\Repositories\AccountRepository;
use App\Repositories\WithdrawRepository;
use Psr\Log\LoggerInterface;

class WithdrawProcessor
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Cria o registro inicial do saque
     */
    public function createWithdrawRecord(
        Account $account,
        string $method,
        float $amount,
        bool $scheduled,
        ?string $scheduledFor
    ): AccountWithdraw {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Valor do saque deve ser maior que zero');
        }

        return WithdrawRepository::create(
            $account->id,
            $method,
            $amount,
            $scheduled,
            $scheduledFor
        );
    }

    /**
     * Processa o débito do saldo da conta
     *
     * @throws \RuntimeException Se o saldo for insuficiente
     */
    public function processDebit(Account $account, AccountWithdraw $withdraw): void
    {
        $currentBalance = (float) $account->balance;
        $amount = (float) $withdraw->amount;

        $this->logger->info('Processando débito', [
            'withdraw_id' => $withdraw->id,
            'account_id' => $account->id,
            'current_balance' => $currentBalance,
            'amount' => $amount,
        ]);

        if ($currentBalance < $amount) {
            throw new \RuntimeException(
                sprintf(
                    'Saldo insuficiente. Saldo atual: R$ %.2f, Valor solicitado: R$ %.2f',
                    $currentBalance,
                    $amount
                ),
                400
            );
        }

        $newBalance = $currentBalance - $amount;

        AccountRepository::updateBalance($account->id, $newBalance);
        WithdrawRepository::markAsCompleted($withdraw->id);

        $this->logger->info('Débito processado com sucesso', [
            'withdraw_id' => $withdraw->id,
            'account_id' => $account->id,
            'previous_balance' => $currentBalance,
            'new_balance' => $newBalance,
            'amount' => $amount,
        ]);
    }

    /**
     * Processa uma falha de saque (saldo insuficiente no momento do processamento)
     */
    public function processFailure(AccountWithdraw $withdraw, string $reason): void
    {
        WithdrawRepository::markAsFailed($withdraw->id, $reason);

        $this->logger->warning('Saque falhou', [
            'withdraw_id' => $withdraw->id,
            'account_id' => $withdraw->account_id,
            'reason' => $reason,
        ]);
    }

    /**
     * Tenta assumir o lock de processamento para um saque agendado
     * Usando UPDATE atômico como mecanismo de compare-and-swap
     */
    public function tryAcquireLock(string $withdrawId): ?AccountWithdraw
    {
        return WithdrawRepository::tryAcquireLockForScheduled($withdrawId);
    }

    /**
     * Libera o lock de processamento em caso de exceção
     */
    public function releaseLock(AccountWithdraw $withdraw): void
    {
        WithdrawRepository::releaseLock($withdraw->id);

        $this->logger->info('Lock de processamento liberado', [
            'withdraw_id' => $withdraw->id,
        ]);
    }
}
