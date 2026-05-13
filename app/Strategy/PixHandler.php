<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Enum\WithdrawMethod;
use App\Model\AccountWithdraw;
use App\Model\AccountWithdrawPix;

class PixHandler implements WithdrawMethodHandlerInterface
{
    public function getMethod(): string
    {
        return WithdrawMethod::PIX->value;
    }

    public function validate(array $data): void
    {
        if (empty($data['type'])) {
            throw new \InvalidArgumentException('Tipo de chave PIX é obrigatório');
        }

        if (empty($data['key'])) {
            throw new \InvalidArgumentException('Chave PIX é obrigatória');
        }

        if ($data['type'] !== 'email') {
            throw new \InvalidArgumentException('Tipo de chave PIX inválido. Apenas email é suportado');
        }

        if (!filter_var($data['key'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Chave PIX do tipo email deve ser um email válido');
        }
    }

    public function persist(AccountWithdraw $withdraw, array $data): void
    {
        $pix = new AccountWithdrawPix();
        $pix->account_withdraw_id = $withdraw->id;
        $pix->type = $data['type'];
        $pix->key = $data['key'];
        $pix->save();
    }

    public function getNotificationEmail(array $data): ?string
    {
        if ($data['type'] === 'email') {
            return $data['key'];
        }

        return null;
    }
}
