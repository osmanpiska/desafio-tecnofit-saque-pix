# Saque PIX API - Documentação

API para saques via PIX com notificações por email.

## Stack

- PHP 8.1+ com Hyperf 3.1
- MySQL 8
- Mailpit (email de teste)
- Docker + Docker Compose

## Instalação Rápida

```bash
# 1. Clonar e entrar no projeto
cd saque-pix2

# 2. Criar arquivo .env
cp .env.example .env

# 3. Subir com Docker
docker-compose up -d

# 4. Instalar dependências
docker-compose exec app composer install
```

## Endpoints Principais

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `GET /health` | Health check |
| `GET /swagger/ui` | Documentação Swagger |
| `POST /health/test-email` | Testar envio de email |

## Testar API

```bash
# Health check
curl http://localhost:9502/health

# Testar email
curl -X POST http://localhost:9502/health/test-email \
  -H "Content-Type: application/json" \
  -d '{"email": "teste@exemplo.com"}'
```

## Serviços

| Serviço | URL |
|---------|-----|
| API | http://localhost:9502 |
| Swagger | http://localhost:9502/swagger/ui |
| Mailpit | http://localhost:8025 |

## Estrutura

```
app/
├── Controller/      # Endpoints
├── Services/        # Lógica de negócio
├── DTO/            # Data Transfer Objects
├── Enum/           # Enums (PixKeyType, WithdrawMethod)
└── Exception/      # Handlers de erro
```

## Comandos Úteis

```bash
# Reiniciar servidor
docker-compose restart app

# Ver logs
docker-compose logs -f app

# Acessar container
docker-compose exec app sh

# Regenerar Swagger manualmente (também é gerado ao reiniciar a aplicação)
docker-compose exec app php bin/hyperf.php gen:swagger
```

## Configuração

Arquivo `.env`:

```env
APP_NAME=saque-pix
SERVER_PORT=9502
DB_PASSWORD=root
MAILPIT_WEB_PORT=8025
```

Ver arquivo `.env.example` para todas as configurações disponíveis.

## Case Técnico

Este projeto implementa o case técnico da Tecnofit descrito em `docs/01-desafio_tecnofit.md`.

Funcionalidades entregues:
- Saque PIX imediato e agendado via chave email
- Notificação por email após saque concluído
- Validações de saldo (não permite negativo)
- Processamento automático via cron (a cada 5 segundos)
- Lock pessimista e atômico para concorrência
- Testes de integração cobrindo todos os cenários
