<?php

declare(strict_types=1);

namespace App\Enum;

/**
 * Tipos de chave PIX suportados.
 * Atualmente apenas EMAIL conforme requisito do case.
 * Facilmente expansível para CPF, CNPJ, PHONE, EVP no futuro.
 */
enum PixKeyType: string
{
    case EMAIL = 'email';

    /**
     * Retorna todos os valores disponíveis
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /**
     * Verifica se o tipo é válido
     */
    public static function isValid(string $type): bool
    {
        return in_array($type, self::values(), true);
    }

    /**
     * Retorna a descrição do tipo em português
     */
    public function description(): string
    {
        return match ($this) {
            self::EMAIL => 'E-mail',
        };
    }

    /**
     * Retorna a descrição em maiúsculas para exibição
     */
    public function label(): string
    {
        return strtoupper($this->value);
    }
}
