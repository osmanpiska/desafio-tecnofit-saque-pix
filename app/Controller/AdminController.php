<?php

declare(strict_types=1);

namespace App\Controller;

use App\Job\ProcessScheduledWithdrawsJob;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\Swagger\Annotation as OA;

#[Controller]
#[OA\HyperfServer(name: 'http')]
class AdminController extends AbstractController
{
    #[Inject]
    protected ProcessScheduledWithdrawsJob $job;

    #[OA\Post(
        path: '/admin/process-scheduled-withdraws',
        operationId: 'processScheduledWithdraws',
        summary: 'Processar saques agendados manualmente',
        description: 'Dispara manualmente o processamento de saques agendados (simula a cron).',
        tags: ['Admin'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Processamento concluído',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Processamento de saques agendados concluído'),
                    ]
                )
            ),
        ]
    )]
    #[PostMapping(path: '/admin/process-scheduled-withdraws')]
    public function processScheduledWithdraws()
    {
        $this->job->execute();

        return $this->response->json([
            'message' => 'Processamento de saques agendados concluído',
        ]);
    }
}
