# 02 - Estrutura do Projeto

## Visao Geral do Hyperf

Hyperf e um framework PHP de alta performance baseado em Swoole, projetado para microservicos e aplicacoes de alta concorrencia.

## Estrutura de Diretorios

```
saque-pix2/
├── app/                    # Codigo da aplicacao
│   ├── Constants/          # Constantes e enums
│   ├── Controller/         # Controllers HTTP
│   ├── Exception/          # Excecoes customizadas
│   ├── Listener/           # Event listeners
│   ├── Model/              # Models (ORM)
│   └── Request/            # Validacao de requests
├── bin/                    # Scripts executaveis
│   └── hyperf.php         # Entry point CLI
├── config/                 # Configuracoes
│   ├── autoload/          # Configs auto-carregadas
│   ├── config.php         # Config principal
│   └── routes.php         # Definicao de rotas
├── test/                   # Testes com Pest
├── vendor/                 # Dependencias Composer
├── .env                    # Variaveis de ambiente
├── composer.json           # Dependencias
└── README.md              # Documentacao
```

## Container de DI

Hyperf usa injecao de dependencia automatica:

```php
use Hyperf\Di\Annotation\Inject;

class UserController
{
    #[Inject]
    protected UserService $userService;
}
```

## Sistema de Configuracoes

Configuracoes em `config/autoload/`:

- `databases.php` - Configuracao do banco
- `server.php` - Configuracao do servidor Swoole
- `cache.php` - Configuracao de cache
- `logger.php` - Configuracao de logs

## Routing

Definido em `config/routes.php`:

```php
Router::get('/users', [UserController::class, 'index']);
Router::post('/users', [UserController::class, 'store']);
```

## Models e Database

Usa o ORM do Hyperf (similar ao Eloquent):

```php
use Hyperf\DbConnection\Model\Model;

class User extends Model
{
    protected ?string $table = 'users';
    protected array $fillable = ['name', 'email'];
}
```

## Proximo Passo

Ver [04-comandos.md](04-comandos.md) para comandos uteis.
