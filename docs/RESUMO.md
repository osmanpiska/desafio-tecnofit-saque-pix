# Resumo - Saque PIX API

## Instalação (Docker)

```bash
# 1. Clone e entre no projeto
cd saque-pix2

# 2. Crie o .env
cat > .env << 'EOF'
APP_NAME=saque-pix
SERVER_PORT=9502
DB_PASSWORD=root
MYSQL_EXTERNAL_PORT=3306
MAILPIT_WEB_PORT=8025
MAILPIT_SMTP_PORT=1025
EOF

# 3. Inicie
docker-compose up -d

# 4. Instale dependências
docker-compose exec app composer install
```

## Endpoints

| Endpoint | Método | Descrição |
|----------|--------|-----------|
| `GET /health` | Health check do sistema |
| `GET /health/error` | Testar erro 500 |
| `GET /health/validation-error` | Testar erro 422 |
| `POST /health/test-email` | Enviar email de teste |
| `GET /swagger/ui` | Documentação Swagger |

## Testes Rápidos

```bash
# Health check
curl http://localhost:9502/health

# Testar erro 500
curl http://localhost:9502/health/error

# Testar erro 422
curl http://localhost:9502/health/validation-error

# Enviar email de teste
curl -X POST http://localhost:9502/health/test-email \
  -H "Content-Type: application/json" \
  -d '{"email": "teste@exemplo.com"}'
```

## URLs Importantes

- **API**: http://localhost:9502
- **Swagger UI**: http://localhost:9502/swagger/ui
- **Swagger JSON**: http://localhost:9502/swagger
- **Mailpit (emails)**: http://localhost:8025

## Comandos Úteis

```bash
# Reiniciar
docker-compose restart app

# Logs
docker-compose logs -f app

# Acessar container
docker-compose exec app sh

# Rodar comandos Hyperf
docker-compose exec app php bin/hyperf.php list

# Regenerar Swagger manualmente (também é gerado ao reiniciar a aplicação)
docker-compose exec app php bin/hyperf.php gen:swagger

# Testes
docker-compose exec app ./vendor/bin/pest
```

## Estrutura do Projeto

```
app/
├── Controller/
│   ├── HealthController.php    # Endpoints de teste
│   └── SwaggerController.php   # Documentação
├── Services/
│   └── EmailService.php        # Envio de emails
├── DTO/
│   └── WithdrawEmailDTO.php    # Dados do email
├── Enum/
│   ├── PixKeyType.php          # Tipo: EMAIL
│   └── WithdrawMethod.php      # Método: PIX
└── Exception/Handler/
    ├── AppExceptionHandler.php      # Erros 500
    ├── NotFoundExceptionHandler.php # Erros 404
    └── ValidationExceptionHandler.php # Erros 422

config/autoload/
├── mail.php    # Config SMTP (Mailpit)
├── swagger.php # Config Swagger
└── exceptions.php # Handlers

resources/views/emails/
└── withdraw_completed.blade.php # Template email
```

## Funcionalidades Implementadas

✅ **Exception Handling**: Handlers para 404, 422, 500 com JSON estruturado  
✅ **Email**: Envio de emails com templates Blade via Mailpit  
✅ **Swagger**: Documentação estática gerada ao iniciar a aplicação  
✅ **Health Check**: Endpoint para monitoramento  
✅ **Enums**: PixKeyType (EMAIL), WithdrawMethod (PIX)  
✅ **DTOs**: WithdrawEmailDTO tipado  

## Próximos Passos (Implementação do Case)

1. **Criar tabelas** no banco (account, account_withdraw, account_withdraw_pix)
2. **Implementar endpoint** `POST /account/{id}/balance/withdraw`
3. **Adicionar validações** de saldo e regras de negócio
4. **Criar cron** para saques agendados
5. **Integrar envio de email** no fluxo de saque

Ver [GUIA_IMPLEMENTACAO.md](GUIA_IMPLEMENTACAO.md) para detalhes do case.
