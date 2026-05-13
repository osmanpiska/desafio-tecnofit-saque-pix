<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'enable' => true,
    'port' => null,
    'json_dir' => BASE_PATH . '/storage/swagger',
    'url' => '/swagger',
    'auto_generate' => false,
    'scan' => [
        'paths' => [
            BASE_PATH . '/app',
        ],
    ],
    'processors' => [],
    'server' => [
        'http' => [
            'servers' => [
                [
                    'url' => 'http://127.0.0.1:' . env('SERVER_PORT', '9502'),
                    'description' => 'Saque PIX API Server',
                ],
            ],
            'info' => [
                'title' => 'Saque PIX API',
                'description' => 'API para saques via PIX com notificações por email',
                'version' => '1.0.0',
            ],
        ],
    ],
];
