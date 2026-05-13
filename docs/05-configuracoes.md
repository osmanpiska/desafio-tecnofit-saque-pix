# 04 - Configuracoes de Ambiente

## Arquivo .env

Criar arquivo `.env` na raiz (copiar de `.env.example`):

```bash
cp .env.example .env
```

### Configuracoes Principais

```env
# Ambiente
APP_ENV=dev
APP_NAME=saque-pix

# Servidor
SERVER_HOST=0.0.0.0
SERVER_PORT=9501

# Banco de Dados MySQL
DB_DRIVER=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=saque_pix
DB_USERNAME=root
DB_PASSWORD=secret
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=

# Timezone (configurado na instalacao)
TIMEZONE=America/Sao_Paulo

# Redis (desabilitado na instalacao)
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_DB=0

# Log
LOG_LEVEL=debug
```

## Configuracao do Banco (config/autoload/databases.php)

```php
return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'hyperf'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => env('DB_PREFIX', ''),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ],
    ],
];
```

## Configuracao do Servidor (config/autoload/server.php)

```php
return [
    'type' => Hyperf\Server\CoroutineServer::class,
    'mode' => SWOOLE_PROCESS,
    'servers' => [
        [
            'name' => 'http',
            'type' => Hyperf\Server\Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Hyperf\Server\Event\SwooleEvent::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
        ],
    ],
    'settings' => [
        'enable_coroutine' => true,
        'worker_num' => swoole_cpu_num(),
        'pid_file' => BASE_PATH . '/runtime/hyperf.pid',
        'open_tcp_nodelay' => true,
        'max_coroutine' => 100000,
        'enable_deadlock_check' => true,
        'log_level' => SWOOLE_LOG_INFO,
    ],
];
```

## Timezone

Ja configurado para `America/Sao_Paulo` na instalacao.

Verificar em `config/config.php`:

```php
date_default_timezone_set(env('TIMEZONE', 'America/Sao_Paulo'));
```

## Proximo Passo

Ver [06-deploy.md](06-deploy.md) para guia de deploy.
