<?php

declare(strict_types=1);

namespace App\DTO;

use App\Enum\PixKeyType;
use DateTime;

class WithdrawEmailDTO
{
    public function __construct(
        public readonly string $email,
        public readonly float $amount,
        public readonly DateTime $processedAt,
        public readonly string $withdrawId,
        public readonly PixKeyType $pixType,
        public readonly string $pixKey
    ) {
        $this->validate();
    }

    /**
     * Valida os dados do DTO
     */
    private function validate(): void
    {
        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address');
        }

        if ($this->amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        if (empty($this->withdrawId)) {
            throw new \InvalidArgumentException('Withdraw ID cannot be empty');
        }

        if (empty($this->pixKey)) {
            throw new \InvalidArgumentException('PIX key cannot be empty');
        }
    }

    /**
     * Cria um DTO a partir de dados brutos (array)
     * Faz conversões necessárias antes de instanciar
     */
    public static function fromArray(array $data): self
    {
        return new self(
            email: $data['email'],
            amount: (float) $data['amount'],
            processedAt: new DateTime($data['processedAt']),
            withdrawId: $data['withdrawId'],
            pixType: PixKeyType::from($data['pixType']),
            pixKey: $data['pixKey']
        );
    }

    /**
     * Converte o DTO para array para uso no template
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'amount' => $this->amount,
            'processed_at' => $this->processedAt->format('d/m/Y H:i:s'),
            'withdraw_id' => $this->withdrawId,
            'pix_type' => $this->pixType->label(),
            'pix_key' => $this->pixKey,
        ];
    }
}
