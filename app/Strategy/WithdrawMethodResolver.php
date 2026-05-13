<?php

declare(strict_types=1);

namespace App\Strategy;

use App\Enum\WithdrawMethod;

class WithdrawMethodResolver
{
    /**
     * @var array<string, WithdrawMethodHandlerInterface>
     */
    protected array $handlers = [];

    public function __construct()
    {
        $this->registerHandler(new PixHandler());
    }

    /**
     * @return array<string> Lista de métodos suportados (valores do enum WithdrawMethod)
     */
    public function getSupportedMethods(): array
    {
        return WithdrawMethod::values();
    }

    public function registerHandler(WithdrawMethodHandlerInterface $handler): void
    {
        $this->handlers[$handler->getMethod()] = $handler;
    }

    public function resolve(string $method): WithdrawMethodHandlerInterface
    {
        $method = strtoupper($method);

        // Valida se o método existe no enum
        if (!WithdrawMethod::isValid($method)) {
            throw new \InvalidArgumentException(
                "Método de saque '{$method}' não é válido. " .
                "Métodos disponíveis: " . implode(', ', WithdrawMethod::values())
            );
        }

        if (!isset($this->handlers[$method])) {
            throw new \InvalidArgumentException(
                "Método de saque '{$method}' ainda não foi implementado. " .
                "Métodos implementados: " . implode(', ', array_keys($this->handlers))
            );
        }

        return $this->handlers[$method];
    }
}
