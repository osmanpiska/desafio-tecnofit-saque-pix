<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Model\AccountWithdrawPix;

class PixRepository
{
    /**
     * Cria um registro de dados PIX para um saque
     */
    public static function create(array $data): AccountWithdrawPix
    {
        return AccountWithdrawPix::create($data);
    }

    /**
     * Busca dados PIX pelo ID do saque
     */
    public static function findByWithdrawId(string $withdrawId): ?AccountWithdrawPix
    {
        return AccountWithdrawPix::query()
            ->where('account_withdraw_id', $withdrawId)
            ->first();
    }
}
