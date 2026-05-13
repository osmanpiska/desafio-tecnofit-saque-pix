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

#[Controller]
class HealthController extends AbstractController
{
    public function __construct(
        private readonly EmailService $emailService
    ) {
    }
    #[GetMapping(path: '/health')]
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
    public function forceError()
    {
        // Força uma divisão por zero para testar logging de erros
        $result = 10 / 0;

        return $this->response->json(['result' => $result]);
    }

    #[GetMapping(path: '/health/validation-error')]
    public function forceValidationError()
    {
        // Simula erro de validação
        throw ValidationException::withMessages([
            'amount' => 'Amount must be greater than 0',
            'pix_key' => 'Invalid PIX key format',
        ])->status(422);
    }

    #[PostMapping(path: '/health/test-email')]
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
