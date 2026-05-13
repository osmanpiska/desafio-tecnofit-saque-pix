<?php

declare(strict_types=1);

namespace App\Request;

use App\Enum\WithdrawMethod;
use Hyperf\Validation\Request\FormRequest;

class WithdrawRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação
     */
    public function rules(): array
    {
        $validMethods = WithdrawMethod::values();

        return [
            'method' => 'required|string|in:' . implode(',', $validMethods),
            'pix' => 'required_if:method,PIX|array',
            'pix.type' => 'required_if:method,PIX|string|in:email',
            'pix.key' => 'required_if:method,PIX|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'schedule' => 'nullable|date|after:now',
        ];
    }

    /**
     * Mensagens de erro personalizadas
     */
    public function messages(): array
    {
        $validMethods = WithdrawMethod::values();

        return [
            'method.required' => 'O método de saque é obrigatório',
            'method.in' => 'Método de saque inválido. Métodos suportados: ' . implode(', ', $validMethods),
            'pix.required_if' => 'Dados do PIX são obrigatórios quando o método é PIX',
            'pix.type.required_if' => 'O tipo de chave PIX é obrigatório',
            'pix.type.in' => 'Tipo de chave PIX inválido. Apenas email é suportado',
            'pix.key.required_if' => 'A chave PIX é obrigatória',
            'amount.required' => 'O valor do saque é obrigatório',
            'amount.numeric' => 'O valor do saque deve ser numérico',
            'amount.gt' => 'O valor do saque deve ser maior que zero',
            'schedule.date' => 'O formato da data de agendamento deve ser Y-m-d H:i ou Y-m-d H:i:s',
            'schedule.after' => 'A data de agendamento deve ser futura',
        ];
    }

    /**
     * Atributos personalizados
     */
    public function attributes(): array
    {
        return [
            'method' => 'método de saque',
            'pix' => 'dados do PIX',
            'pix.type' => 'tipo de chave PIX',
            'pix.key' => 'chave PIX',
            'amount' => 'valor do saque',
            'schedule' => 'data de agendamento',
        ];
    }

    /**
     * Retorna se é um saque agendado
     */
    public function isScheduled(): bool
    {
        return $this->has('schedule') && $this->input('schedule') !== null;
    }

    /**
     * Retorna os dados do método de saque
     */
    public function getMethodData(): array
    {
        $method = $this->input('method');

        return match ($method) {
            WithdrawMethod::PIX->value => [
                'type' => $this->input('pix.type'),
                'key' => $this->input('pix.key'),
            ],
            default => [],
        };
    }
}
