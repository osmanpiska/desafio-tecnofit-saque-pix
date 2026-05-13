<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'default' => 'smtp',
    'mailers' => [
        'smtp' => [
            'transport' => 'smtp',
            'scheme' => 'smtp',
            'host' => env('MAIL_HOST', 'mailpit'),
            'port' => (int) env('MAIL_PORT', 1025),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => null,
        ],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@saque-pix.local'),
        'name' => env('MAIL_FROM_NAME', 'SaquePIX'),
    ],
];
