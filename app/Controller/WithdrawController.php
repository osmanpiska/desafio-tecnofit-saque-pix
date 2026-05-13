<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\WithdrawMethod;
use App\Request\WithdrawRequest;
use App\Services\WithdrawService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Swagger\Annotation as OA;

#[Controller]
#[OA\HyperfServer(name: 'http')]
class WithdrawController extends AbstractController
{
    #[Inject]
    protected WithdrawService $withdrawService;

    #[PostMapping(path: '/account/{accountId}/balance/withdraw')]
    #[OA\Post(
        path: '/account/{accountId}/balance/withdraw',
        operationId: 'withdraw',
        summary: 'Realizar saque PIX',
        description: 'Saque imediato ou agendado via PIX. Para saque imediato passe schedule como null.',
        tags: ['Saque'],
        parameters: [
            new OA\Parameter(
                name: 'accountId',
                in: 'path',
                required: true,
                description: 'UUID da conta',
                schema: new OA\Schema(type: 'string', format: 'uuid', example: '69770a98-38d4-4956-b996-f40657116cdc')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['method', 'pix', 'amount'],
                properties: [
                    new OA\Property(property: 'method', type: 'string', enum: ['PIX'], example: 'PIX'),
                    new OA\Property(
                        property: 'pix',
                        type: 'object',
                        required: ['type', 'key'],
                        properties: [
                            new OA\Property(property: 'type', type: 'string', enum: ['email'], example: 'email'),
                            new OA\Property(property: 'key', type: 'string', example: 'usuario@email.com'),
                        ]
                    ),
                    new OA\Property(property: 'amount', type: 'number', format: 'double', example: 150.75),
                    new OA\Property(property: 'schedule', type: 'string', format: 'date-time', nullable: true, example: null),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Saque realizado ou agendado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Saque realizado com sucesso'),
                        new OA\Property(property: 'withdraw_id', type: 'string', format: 'uuid'),
                        new OA\Property(property: 'status', type: 'string', example: 'completed'),
                        new OA\Property(property: 'amount', type: 'number', format: 'double', example: 150.75),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Erro de validação ou saldo insuficiente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Saldo insuficiente'),
                        new OA\Property(property: 'code', type: 'integer', example: 400),
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Conta não encontrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Conta não encontrada'),
                        new OA\Property(property: 'code', type: 'integer', example: 404),
                    ]
                )
            ),
        ]
    )]
    public function withdraw(string $accountId, WithdrawRequest $request)
    {
        $method = $request->input('method');
        $amount = (float) $request->input('amount');
        $methodData = $request->getMethodData();

        if ($request->isScheduled()) {
            $result = $this->withdrawService->scheduleWithdraw(
                $accountId,
                $method,
                $methodData,
                $amount,
                $request->input('schedule')
            );

            return $this->response->json([
                'message' => 'Saque agendado com sucesso',
                ...$result,
            ]);
        }

        $result = $this->withdrawService->processImmediateWithdraw(
            $accountId,
            $method,
            $methodData,
            $amount
        );

        return $this->response->json([
            'message' => 'Saque realizado com sucesso',
            ...$result,
        ]);
    }
}
