<?php

declare(strict_types=1);

use App\Job\ProcessScheduledWithdrawsJob;
use Hyperf\Crontab\Crontab;

return [
    'enable' => true,
    'crontab' => [
        // Configuração da cron para processar saques agendados a cada 5 segundos
        (new Crontab())
            ->setName('process-scheduled-withdraws')
            ->setRule('*/5 * * * * *')
            ->setCallback([ProcessScheduledWithdrawsJob::class, 'execute'])
            ->setEnable(true)
            // O lock de concorrência é feito no MySQL por saque; singleton exigiria RedisTaskMutex.
            ->setSingleton(false),
    ],
];
