<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller;

use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Validation\ValidationException;

#[Controller]
class HealthController extends AbstractController
{
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
}
