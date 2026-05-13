# Saque PIX - API de Saque Digital

> API em PHP com Hyperf 3 para processamento de saques PIX com suporte a saques imediatos e agendados.

[![PHP](https://img.shields.io/badge/PHP-8.1+-blue)](https://php.net)
[![Hyperf](https://img.shields.io/badge/Hyperf-3.1-green)](https://hyperf.wiki)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)](https://mysql.com)
[![Docker](https://img.shields.io/badge/Docker-Compose-blue)](https://docker.com)

---

## Sobre o Projeto

Este projeto implementa o case técnico de saque PIX para uma conta digital. A API permite criar saques imediatos e agendados, debitar saldo com segurança transacional e enviar email de confirmação após o saque concluído.

O foco principal da solução é garantir consistência financeira mesmo sob concorrência alta. Para isso, o projeto usa transações de banco, lock pessimista na conta e lock atômico para processamento de saques agendados.

### Funcionalidades Principais

- Saque PIX imediato com débito instantâneo.
- Saque PIX agendado para data futura.
- Processamento automático via cron do Hyperf a cada 5 segundos.
- Lock pessimista para impedir saldo negativo.
- Lock atômico para evitar processamento duplicado na cron.
- Envio de email de confirmação via Mailpit.
- Validação HTTP via `FormRequest`.
- Swagger/OpenAPI para documentação dos endpoints.
- Testes cobrindo fluxo feliz, falhas, cron e concorrência extrema.

---

## Stack

- PHP 8.1+ com Hyperf 3.1
- Swoole
- MySQL 8
- Mailpit
- Docker + Docker Compose
- PHPUnit/Hyperf Testing

---

## Por que Hyperf 3 e Swoole?

O case exige segurança em cenário de concorrência. Em uma operação financeira, saldo negativo é uma falha crítica. Hyperf com Swoole permite lidar melhor com alto volume de requisições simultâneas, mantendo a aplicação viva em workers persistentes e reduzindo overhead por request.

### Comparação Rápida

| Aspecto | PHP-FPM + Nginx | Hyperf + Swoole |
|---------|-----------------|-----------------|
| Modelo | Process-based | Event-loop + workers |
| Ciclo da aplicação | Recriado por request | Processo persistente |
| Conexões DB | Tendem a ser recriadas | Pool/persistência |
| Concorrência | Boa, mas com maior overhead | Alta, com menor overhead |
| Complexidade | Baixa | Média |

### Resultado Validado

O teste de concorrência extrema executa 1.000 requisições simultâneas contra uma conta com saldo de R$ 100,00:

```text
1.000 requisições simultâneas
100 saques aprovados de R$ 1,00
900 saques rejeitados por saldo insuficiente
Saldo final: R$ 0,00
Saldos negativos: 0
```

---

## Decisões Arquiteturais

### 1. Strategy Pattern para Métodos de Saque

O projeto usa `app/Strategy` para isolar o comportamento específico de cada método de saque. Hoje existe apenas PIX via chave email, mas o fluxo principal já está preparado para receber outros métodos.

```php
interface WithdrawMethodHandlerInterface
{
    public function getMethod(): string;
    public function validate(array $data): void;
    public function persist(AccountWithdraw $withdraw, array $data): void;
    public function getNotificationEmail(array $data): ?string;
}
```

Com isso, adicionar TED, boleto ou outro método exige criar um novo handler e registrá-lo no resolver, sem reescrever o fluxo principal de saque.

### 2. Lock Pessimista no Saldo

O débito usa `SELECT ... FOR UPDATE` dentro de transação. Em operações financeiras, consistência é mais importante que throughput bruto: duas requisições não podem ler o mesmo saldo e debitar ao mesmo tempo.

O lock pessimista garante que apenas uma transação por vez altere o saldo daquela conta.

### 3. Saque Agendado sem Débito Imediato

O saque agendado registra a intenção, mas não debita o saldo no momento do agendamento. O débito acontece apenas quando a cron processa o saque.

Essa decisão evita bloquear saldo por tempo indeterminado. Se no momento do processamento não houver saldo suficiente, o saque é marcado com erro (`error = true`) e motivo (`error_reason = saldo insuficiente`).

### 4. Lock Atômico na Cron

Para evitar que duas instâncias processem o mesmo saque, o projeto usa um `UPDATE` condicional no campo `processing`:

```sql
UPDATE account_withdraw
SET processing = true
WHERE id = ?
  AND processing = false
  AND done = false
  AND error = false
```

Se `affected_rows` for `1`, a instância adquiriu o lock. Se for `0`, outro processo já assumiu o saque. Isso funciona como um compare-and-swap usando MySQL.

### 5. Email Fora da Transação

O email é enviado após o commit do saque. Se o SMTP falhar, o saque não deve ser desfeito, pois a operação financeira já foi concluída.

Falha de email é tratada como problema de notificação e registrada em log.

---

## Segurança

### Validações Implementadas

| Camada | Validação |
|--------|-----------|
| Request | Campos obrigatórios, método, PIX email, valor positivo e data futura |
| Service/Processor | Conta existente, valor maior que zero e saldo suficiente |
| Banco | Transação e lock pessimista no saldo |
| Cron | Lock atômico por saque agendado |

### Proteções

- Valor de saque precisa ser maior que zero.
- Agendamento no passado é rejeitado.
- Saque acima do saldo retorna erro e não debita.
- Saldo negativo é impedido mesmo com concorrência.
- Stack trace não é exposto em resposta HTTP.
- Chave PIX/email é mascarada nos logs.
- `.env` não deve ser commitado com credenciais reais.

---

## Como Executar

### Pré-requisitos

- Docker
- Docker Compose

### Subir o Ambiente

```bash
cp .env.example .env
docker-compose up -d --build
```

O container da aplicação executa `composer install` ao iniciar.

### Verificar Saúde da API

```bash
curl http://localhost:9502/health
```

Resposta esperada:

```json
{
  "status": "ok",
  "database": {
    "status": "ok",
    "message": "Connected"
  }
}
```

### Executar Migrations

```bash
docker-compose exec app php bin/hyperf.php migrate
```

Se existirem seeders no ambiente:

```bash
docker-compose exec app php bin/hyperf.php db:seed
```

---

## Como Executar os Testes

### Rodar Todos os Testes

```bash
docker-compose exec -T app composer test
```

Resultado esperado:

```text
OK (... tests, ... assertions)
```

### Rodar Apenas os Testes do Fluxo de Saque

```bash
docker-compose exec -T app composer test -- --filter WithdrawFlowTest
```

Esta suíte cobre:

- Saque imediato com dados válidos.
- Débito correto do saldo.
- Registro em `account_withdraw`.
- Registro em `account_withdraw_pix`.
- Bloqueio de saque acima do saldo.
- Envio de email no Mailpit.
- Criação de saque agendado sem débito imediato.
- Persistência correta de `scheduled_for`.
- Rejeição de agendamento no passado.
- Processamento de saque agendado pela cron real.
- Preenchimento de `processed_at`.
- Falha de saque agendado sem saldo.
- Garantia de que saque agendado não deixa saldo negativo.
- Idempotência no processamento agendado.
- Release do campo `processing` em caso de exceção.
- Concorrência extrema com 1.000 requisições simultâneas.

### Observação Sobre os Testes de Cron

Os testes de schedule aguardam a cron real do Hyperf processar os saques vencidos. A cron está configurada para rodar a cada 5 segundos:

```text
*/5 * * * * *
```

Por isso, a suíte pode levar alguns segundos a mais.

---

## Endpoints

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `/health` | GET | Health check da API e banco |
| `/health/test-email` | POST | Testa envio de email |
| `/account/{accountId}/balance/withdraw` | POST | Cria saque imediato ou agendado |
| `/admin/process-scheduled-withdraws` | POST | Processa saques agendados manualmente |
| `/swagger/ui` | GET | Interface Swagger |

### Saque Imediato

```bash
curl -X POST http://localhost:9502/account/{accountId}/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "usuario@email.com"
    },
    "amount": 10.00,
    "schedule": null
  }'
```

### Saque Agendado

```bash
curl -X POST http://localhost:9502/account/{accountId}/balance/withdraw \
  -H "Content-Type: application/json" \
  -d '{
    "method": "PIX",
    "pix": {
      "type": "email",
      "key": "usuario@email.com"
    },
    "amount": 10.00,
    "schedule": "2026-12-31 23:59:59"
  }'
```

---

## Serviços Docker

| Serviço | URL padrão |
|---------|------------|
| API | http://localhost:9502 |
| Swagger | http://localhost:9502/swagger/ui |
| Mailpit | http://localhost:8025 |
| MySQL | localhost:3306 |

As portas externas podem ser ajustadas no `.env`:

```env
SERVER_PORT=9502
MYSQL_EXTERNAL_PORT=3306
MAILPIT_WEB_PORT=8025
MAILPIT_SMTP_PORT=1025
```

Dentro da rede Docker, o Mailpit usa portas internas fixas:

- SMTP: `mailpit:1025`
- Web/API: `mailpit:8025`

---

## Observabilidade

Os logs registram:

- início e fim do saque imediato;
- tentativa e aquisição de lock em saque agendado;
- saldo insuficiente;
- tempo total de processamento da cron;
- quantidade de saques processados com sucesso e falha;
- falhas inesperadas com stack trace apenas em log.

Exemplo de log da cron:

```json
{
  "message": "Processamento de saques agendados concluído",
  "metrics": {
    "total_pendentes": 50,
    "processados": 50,
    "sucesso": 48,
    "falhas_saldo": 2,
    "falhas_outras": 0
  },
  "duration_seconds": 5.234,
  "avg_time_per_withdraw_seconds": 0.105
}
```

---

## Arquitetura de Pastas

```text
app/
├── Controller/          # Endpoints HTTP
├── DTO/                 # Objetos de transferência de dados
├── Enum/                # Enums do domínio
├── Exception/Handler/   # Tratamento global de erros
├── Job/                 # Cron de saques agendados
├── Model/               # Models do banco
├── Processor/           # Débito, falha e lock
├── Repositories/        # Acesso a dados
├── Request/             # Validação HTTP
├── Services/            # Casos de uso e integrações
└── Strategy/            # Métodos de saque
```

---

## Documentação Complementar

- [Desafio Tecnofit](docs/01-desafio_tecnofit.md)
- [Instalação](docs/02-instalacao.md)
- [Estrutura do Projeto](docs/03-estrutura.md)
- [Comandos Úteis](docs/04-comandos.md)
- [Configurações](docs/05-configuracoes.md)
- [Deploy](docs/06-deploy.md)

---

## Decisões que Eu Evoluiria em Produção

- Usar fila dedicada para processamento de saques agendados em larga escala.
- Adicionar `Idempotency-Key` para proteger contra retries do cliente.
- Adicionar rate limit por conta/IP.
- Separar logs técnicos de auditoria financeira.
- Usar storage seguro para segredos em vez de `.env` em produção.
- Adicionar monitoramento com métricas e alertas.

---

## Conclusão

O projeto demonstra uma implementação segura e escalável para saques PIX, com foco em consistência transacional, expansão futura de métodos de saque e testes cobrindo os pontos críticos do case.

O principal requisito de segurança financeira foi validado: mesmo com 1.000 requisições simultâneas, o saldo não fica negativo.
