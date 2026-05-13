<?php

declare(strict_types=1);

namespace App\Controller;

use App\Services\EmailService;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Validation\ValidationException;
use OpenApi\Attributes as OA;

#[Controller]
#[OA\Info(title: 'Saque PIX API', version: '1.0.0', description: 'API para saques via PIX com notificações por email')]
#[OA\Server(url: 'http://localhost:9502', description: 'Servidor local')]
class HealthController extends AbstractController
{
    public function __construct(
        private readonly EmailService $emailService
    ) {
    }
    #[GetMapping(path: '/health')]
    #[OA\Get(
        path: '/health',
        summary: 'Health check',
        description: 'Verifica status da API e conexão com banco de dados',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API funcionando normalmente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: 'ok'),
                        new OA\Property(property: 'timestamp', type: 'string', example: '2026-05-13 15:30:00'),
                        new OA\Property(property: 'database', properties: [
                            new OA\Property(property: 'status', type: 'string', example: 'ok'),
                            new OA\Property(property: 'message', type: 'string', example: 'Connected'),
                        ]),
                    ]
                )
            ),
        ]
    )]
    public function health()
    {
        $dbStatus = 'ok';
        $dbMessage = 'Connected';

        try {
            Db::select('SELECT 1');
        } catch (Exception $e) {
            $dbStatus = 'error';
            $dbMessage = $e->getMessage();
        }

        return $this->response->json([
            'status' => 'ok',
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => [
                'status' => $dbStatus,
                'message' => $dbMessage,
            ],
        ]);
    }

    #[GetMapping(path: '/health/error')]
    #[OA\Get(
        path: '/health/error',
        summary: 'Testar erro 500',
        description: 'Força um erro de servidor para testar o handler de exceções',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 500,
                description: 'Erro de servidor',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 500),
                        new OA\Property(property: 'message', type: 'string', example: 'Server Error'),
                        new OA\Property(property: 'errors', type: 'object'),
                    ]
                )
            ),
        ]
    )]
    public function forceError()
    {
        // Força uma divisão por zero para testar logging de erros
        $result = 10 / 0;

        return $this->response->json(['result' => $result]);
    }

    #[GetMapping(path: '/health/validation-error')]
    #[OA\Get(
        path: '/health/validation-error',
        summary: 'Testar erro 422',
        description: 'Força um erro de validação para testar o handler de validação',
        tags: ['Health'],
        responses: [
            new OA\Response(
                response: 422,
                description: 'Erro de validação',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 422),
                        new OA\Property(property: 'message', type: 'string', example: 'Validation Error'),
                        new OA\Property(property: 'errors', type: 'object', example: ['amount' => ['Amount must be greater than 0']]),
                    ]
                )
            ),
        ]
    )]
    public function forceValidationError()
    {
        // Simula erro de validação
        throw ValidationException::withMessages([
            'amount' => 'Amount must be greater than 0',
            'pix_key' => 'Invalid PIX key format',
        ])->status(422);
    }

    #[PostMapping(path: '/health/test-email')]
    #[OA\Post(
        path: '/health/test-email',
        summary: 'Testar envio de email',
        description: 'Envia um email de teste usando o template de saque PIX',
        tags: ['Health'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'teste@exemplo.com', description: 'Endereço de email do destinatário'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Email enviado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 200),
                        new OA\Property(property: 'message', type: 'string', example: 'Test email sent successfully'),
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'to', type: 'string', example: 'teste@exemplo.com'),
                            new OA\Property(property: 'check_mailpit', type: 'string', example: 'http://localhost:8025'),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Email não fornecido',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 400),
                        new OA\Property(property: 'message', type: 'string', example: 'Email is required'),
                    ]
                )
            ),
            new OA\Response(
                response: 500,
                description: 'Falha ao enviar email',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 500),
                        new OA\Property(property: 'message', type: 'string', example: 'Failed to send test email'),
                    ]
                )
            ),
        ]
    )]
    public function testEmail()
    {
        $email = $this->request->input('email');

        if (empty($email)) {
            return $this->response->json([
                'code' => 400,
                'message' => 'Email is required',
                'errors' => ['email' => 'Please provide an email address'],
            ])->withStatus(400);
        }

        $sent = $this->emailService->sendTestEmail($email);

        if ($sent) {
            return $this->response->json([
                'code' => 200,
                'message' => 'Test email sent successfully',
                'data' => [
                    'to' => $email,
                    'check_mailpit' => 'http://localhost:8025',
                ],
            ]);
        }

        return $this->response->json([
            'code' => 500,
            'message' => 'Failed to send test email',
            'errors' => ['email' => 'Could not send email. Check logs for details.'],
        ])->withStatus(500);
    }
}
