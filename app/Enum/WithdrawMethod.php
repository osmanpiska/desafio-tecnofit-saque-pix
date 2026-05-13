<?php

declare(strict_types=1);

namespace App\Enum;

enum WithdrawMethod: string
{
    case PIX = 'PIX';
    // Futuros métodos:
    // case TED = 'TED';
    // case BOLETO = 'BOLETO';
    // case TRANSFER = 'TRANSFER';

    /**
     * Retorna todos os valores disponíveis
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Verifica se o método é válido
     */
    public static function isValid(string $method): bool
    {
        return in_array($method, self::values(), true);
    }

    /**
     * Retorna a descrição do método
     */
    public function description(): string
    {
        return match ($this) {
            self::PIX => 'Transferência via PIX',
        };
    }
}
