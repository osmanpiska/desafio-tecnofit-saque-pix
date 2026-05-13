<?php

declare(strict_types=1);

return [
    'enable' => true,
    'crontab' => [
        // Configuração da cron para processar saques agendados a cada 5 segundos
        [
            'name' => 'process-scheduled-withdraws',
            'rule' => '*/5 * * * * *',
            'callback' => [\App\Job\ProcessScheduledWithdrawsJob::class, 'execute'],
            'enable' => true,
            'singleton' => true,  // Evita execuções simultâneas
            'mutex_pool' => 'default',
        ],
    ],
];
