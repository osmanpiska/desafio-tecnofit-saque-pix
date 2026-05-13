<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Model\AccountWithdraw;
use Hyperf\Database\Model\Collection;
use Ramsey\Uuid\Uuid;

class WithdrawRepository
{
    /**
     * Busca saques agendados pendentes (máximo 50 por ciclo)
     */
    public static function getPendingScheduled(int $limit = 50): Collection
    {
        return AccountWithdraw::query()
            ->where('scheduled', true)
            ->where('done', false)
            ->where('error', false)
            ->where('processing', false)
            ->where('scheduled_for', '<=', date('Y-m-d H:i:s'))
            ->limit($limit)
            ->get();
    }

    /**
     * Cria um novo registro de saque
     */
    public static function create(
        string $accountId,
        string $method,
        float $amount,
        bool $scheduled,
        ?string $scheduledFor
    ): AccountWithdraw {
        $withdraw = new AccountWithdraw();
        $withdraw->id = Uuid::uuid4()->toString();
        $withdraw->account_id = $accountId;
        $withdraw->method = $method;
        $withdraw->amount = $amount;
        $withdraw->scheduled = $scheduled;
        $withdraw->scheduled_for = $scheduledFor;
        $withdraw->done = false;
        $withdraw->error = false;
        $withdraw->processing = false;
        $withdraw->error_reason = null;
        $withdraw->processed_at = null;
        $withdraw->save();

        return $withdraw;
    }

    /**
     * Tenta adquirir lock atômico para processamento geral
     * Retorna o saque atualizado se conseguir, null caso contrário
     */
    public static function tryAcquireLock(string $withdrawId): ?AccountWithdraw
    {
        $affected = AccountWithdraw::query()
            ->where('id', $withdrawId)
            ->where('processing', false)
            ->where('done', false)
            ->where('error', false)
            ->update(['processing' => true]);

        if ($affected === 0) {
            return null;
        }

        return AccountWithdraw::query()->find($withdrawId);
    }

    /**
     * Tenta adquirir lock atômico específico para saques agendados
     * Inclui verificação de scheduled=true e scheduled_for vencido
     */
    public static function tryAcquireLockForScheduled(string $withdrawId): ?AccountWithdraw
    {
        $affected = AccountWithdraw::query()
            ->where('id', $withdrawId)
            ->where('processing', false)
            ->where('done', false)
            ->where('error', false)
            ->where('scheduled', true)
            ->where('scheduled_for', '<=', date('Y-m-d H:i:s'))
            ->update(['processing' => true]);

        if ($affected === 0) {
            return null;
        }

        return AccountWithdraw::query()->where('id', $withdrawId)->first();
    }

    /**
     * Libera o lock de processamento (reverte processing para false)
     */
    public static function releaseLock(string $withdrawId): bool
    {
        return AccountWithdraw::query()
            ->where('id', $withdrawId)
            ->update(['processing' => false]) > 0;
    }

    /**
     * Marca um saque como concluído com sucesso
     */
    public static function markAsCompleted(string $withdrawId): bool
    {
        return AccountWithdraw::query()
            ->where('id', $withdrawId)
            ->update([
                'done' => true,
                'processing' => false,
                'processed_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Marca um saque como falho com motivo do erro
     */
    public static function markAsFailed(string $withdrawId, string $reason): bool
    {
        return AccountWithdraw::query()
            ->where('id', $withdrawId)
            ->update([
                'done' => true,
                'error' => true,
                'error_reason' => $reason,
                'processing' => false,
                'processed_at' => date('Y-m-d H:i:s'),
            ]) > 0;
    }

    /**
     * Busca um saque pelo ID
     */
    public static function findById(string $id): ?AccountWithdraw
    {
        return AccountWithdraw::query()->find($id);
    }
}
