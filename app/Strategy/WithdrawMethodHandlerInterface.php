<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Model\Account;
use App\Model\AccountWithdraw;

interface WithdrawMethodHandlerInterface
{
    /**
     * Retorna o código do método de saque suportado
     * @see \App\Enum\WithdrawMethod para valores válidos
     */
    public function getMethod(): string;

    /**
     * Valida os dados específicos do método de saque
     *
     * @param array $data Dados do método (ex: ['type' => 'email', 'key' => 'test@email.com'])
     * @throws \InvalidArgumentException Se os dados forem inválidos
     */
    public function validate(array $data): void;

    /**
     * Persiste os dados específicos do método de saque
     *
     * @param AccountWithdraw $withdraw Registro de saque criado
     * @param array $data Dados do método
     */
    public function persist(AccountWithdraw $withdraw, array $data): void;

    /**
     * Retorna o endereço de email para notificação
     *
     * @param array $data Dados do método
     * @return string|null Email do destinatário ou null se não aplicável
     */
    public function getNotificationEmail(array $data): ?string;
}
