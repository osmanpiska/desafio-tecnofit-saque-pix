<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Model\Account;

class AccountRepository
{
    /**
     * Busca uma conta pelo ID
     */
    public static function findById(string $id): ?Account
    {
        return Account::query()->where('id', $id)->first();
    }

    /**
     * Busca uma conta com lock pessimista (SELECT ... FOR UPDATE)
     */
    public static function findWithLock(string $id): ?Account
    {
        return Account::query()
            ->where('id', $id)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Atualiza o saldo de uma conta
     */
    public static function updateBalance(string $id, float $balance): bool
    {
        return Account::query()
            ->where('id', $id)
            ->update(['balance' => $balance]) > 0;
    }

    /**
     * Verifica se uma conta existe
     */
    public static function exists(string $id): bool
    {
        return Account::query()->where('id', $id)->exists();
    }
}
