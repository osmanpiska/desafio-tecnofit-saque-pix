# 05 - Configuracoes de Ambiente

Guia completo de configuracao do ambiente para o projeto Saque PIX.

---

## Arquivo .env

Criar arquivo `.env` na raiz (copiar de `.env.example`):

```bash
cp .env.example .env
```

### Variáveis de Ambiente Completas

```env
# ============================================
# Aplicacao
# ============================================
APP_NAME=saque-pix
APP_ENV=dev
APP_DEBUG=true

# ============================================
# Banco de Dados - MySQL 8
# ============================================
DB_DRIVER=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=saque_pix
DB_USERNAME=root
DB_PASSWORD=root
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=

# Porta externa do MySQL (host)
MYSQL_EXTERNAL_PORT=3306

# ============================================
# Hyperf Server
# ============================================
SERVER_HOST=0.0.0.0
SERVER_PORT=9502
SERVER_MODE=SWOOLE_PROCESS

# ============================================
# Logging
# ============================================
LOG_LEVEL=debug
LOG_CHANNEL=hyperf

# ============================================
# Email - Mailpit (servico de teste)
# ============================================
MAIL_DRIVER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=
MAIL_FROM_ADDRESS=noreply@saque-pix.local
MAIL_FROM_NAME=SaquePIX
MAILPIT_WEB_PORT=8025
MAILPIT_SMTP_PORT=1025
```

### Descricao das Variáveis

| Variavel | Valor Padrao | Descricao |
|----------|--------------|-----------|
| `APP_NAME` | saque-pix | Nome da aplicacao (define nomes dos containers) |
| `APP_ENV` | dev | Ambiente: dev, test, prod |
| `APP_DEBUG` | true | Modo debug (mostra erros detalhados) |
| `SERVER_HOST` | 0.0.0.0 | IP do servidor (0.0.0.0 = todas interfaces) |
| `SERVER_PORT` | 9502 | Porta do servidor HTTP (interna e externa) |
| `SERVER_MODE` | SWOOLE_PROCESS | Modo Swoole: SWOOLE_BASE ou SWOOLE_PROCESS |
| `LOG_LEVEL` | debug | Nivel de log: debug, info, warning, error |
| `LOG_CHANNEL` | hyperf | Canal de log padrao |
| `DB_HOST` | mysql | Host do MySQL (nome do servico no Docker) |
| `DB_PASSWORD` | root | Senha do MySQL |
| `MYSQL_EXTERNAL_PORT` | 3306 | Porta externa do MySQL no host (evita conflitos) |
| `MAIL_HOST` | mailpit | Host SMTP para envio de email |
| `MAIL_PORT` | 1025 | Porta SMTP do Mailpit (interna) |
| `MAILPIT_WEB_PORT` | 8025 | Porta externa da interface web do Mailpit |
| `MAILPIT_SMTP_PORT` | 1025 | Porta externa SMTP do Mailpit no host |

---

## Configuracao do Banco (config/autoload/databases.php)

```php
<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'default' => [
        'driver' => env('DB_DRIVER', 'mysql'),
        'host' => env('DB_HOST', 'mysql'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'saque_pix'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', 'root'),
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

---

## Configuracao do Servidor (config/autoload/server.php)

```php
<?php

declare(strict_types=1);

use Hyperf\Server\Event;
use Hyperf\Server\Server;
use Swoole\Constant;
use function Hyperf\Support\env;

return [
    'mode' => constant(env('SERVER_MODE', 'SWOOLE_BASE')),
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => env('SERVER_HOST', '0.0.0.0'),
            'port' => (int) env('SERVER_PORT', 9501),
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
            'options' => [
                'enable_request_lifecycle' => false,
            ],
        ],
    ],
    'settings' => [
        Constant::OPTION_ENABLE_COROUTINE => true,
        Constant::OPTION_WORKER_NUM => swoole_cpu_num(),
        Constant::OPTION_PID_FILE => BASE_PATH . '/runtime/hyperf.pid',
        Constant::OPTION_OPEN_TCP_NODELAY => true,
        Constant::OPTION_MAX_COROUTINE => 100000,
        Constant::OPTION_OPEN_HTTP2_PROTOCOL => true,
        Constant::OPTION_MAX_REQUEST => 100000,
        Constant::OPTION_SOCKET_BUFFER_SIZE => 2 * 1024 * 1024,
        Constant::OPTION_BUFFER_OUTPUT_SIZE => 2 * 1024 * 1024,
    ],
    'callbacks' => [
        Event::ON_WORKER_START => [Hyperf\Framework\Bootstrap\WorkerStartCallback::class, 'onWorkerStart'],
        Event::ON_PIPE_MESSAGE => [Hyperf\Framework\Bootstrap\PipeMessageCallback::class, 'onPipeMessage'],
        Event::ON_WORKER_EXIT => [Hyperf\Framework\Bootstrap\WorkerExitCallback::class, 'onWorkerExit'],
    ],
];
```

---

## Configuracao de Logging (config/autoload/logger.php)

```php
<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'default' => [
        'handler' => [
            'class' => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => BASE_PATH . '/runtime/logs/hyperf.log',
                'maxFiles' => 7,
                'level' => env('LOG_LEVEL', 'debug'),
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
];
```

### Níveis de Log

| Nivel | Uso |
|-------|-----|
| `debug` | Desenvolvimento, informacoes detalhadas |
| `info` | Eventos normais da aplicacao |
| `warning` | Advertencias, algo pode estar errado |
| `error` | Erros que precisam de atencao |

---

## Configuracao de Email (config/autoload/mail.php)

Criar arquivo `config/autoload/mail.php`:

```php
<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'default' => [
        'driver' => env('MAIL_DRIVER', 'smtp'),
        'host' => env('MAIL_HOST', 'mailpit'),
        'port' => env('MAIL_PORT', 1025),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => env('MAIL_ENCRYPTION'),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@saque-pix.local'),
            'name' => env('MAIL_FROM_NAME', 'SaquePIX'),
        ],
    ],
];
```

---

## Timezone

Configurado em `config/config.php`:

```php
<?php

declare(strict_types=1);

use function Hyperf\Support\env;

return [
    'app_name' => env('APP_NAME', 'saque-pix'),
    'app_env' => env('APP_ENV', 'dev'),
    'scan_cacheable' => env('SCAN_CACHEABLE', false),
];
```

E definido no inicio da aplicacao (`config/container.php` ou bootstrap):

```php
date_default_timezone_set(env('APP_TIMEZONE', 'America/Sao_Paulo'));
```

---

## Pacotes Adicionais

### hyperf/validation

Pacote para validação de dados e formulários:

```bash
composer require hyperf/validation:~3.1.0
```

Uso em controllers:

```php
use Hyperf\Validation\ValidationException;

// Lançar exceção de validação manualmente
throw ValidationException::withMessages([
    'field' => 'Error message',
])->status(422);
```

Uso em FormRequests:

```php
use Hyperf\Validation\Request\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
        ];
    }
}
```

### ramsey/uuid

Pacote para geração de UUIDs (preparado para uso futuro):

```bash
composer require ramsey/uuid:^4.9
```

Uso:

```php
use Ramsey\Uuid\Uuid;

$uuid = Uuid::uuid4()->toString(); // Ex: "550e8400-e29b-41d4-a716-446655440000"
```

---

## Configuracao de Exceções

Configurar handlers em `config/autoload/exceptions.php`:

```php
<?php

declare(strict_types=1);

return [
    'handler' => [
        'http' => [
            App\Exception\Handler\NotFoundExceptionHandler::class,
            App\Exception\Handler\ValidationExceptionHandler::class,
            Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler::class,
            App\Exception\Handler\AppExceptionHandler::class,
        ],
    ],
];
```

Handlers em ordem de prioridade:
1. `NotFoundExceptionHandler` - Retorna 404 em formato JSON
2. `ValidationExceptionHandler` - Retorna 422 com erros de validação
3. `HttpExceptionHandler` - Handler padrão do Hyperf
4. `AppExceptionHandler` - Captura erros 500 não tratados

---

## Constantes de Erro

Editar `app/Constants/ErrorCode.php`:

```php
<?php

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

#[Constants]
class ErrorCode extends AbstractConstants
{
    /**
     * @Message("Server Error！")
     */
    public const SERVER_ERROR = 500;

    /**
     * @Message("Validation Error")
     */
    public const VALIDATION_ERROR = 422;

    /**
     * @Message("Not Found")
     */
    public const NOT_FOUND = 404;
}
```

---

## Proximo Passo

Ver [06-deploy.md](06-deploy.md) para guia de deploy com Docker.
