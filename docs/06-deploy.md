# 06 - Guia de Deploy

Este guia contém as configurações Docker completas para rodar o projeto Saque PIX com Hyperf, MySQL e Mailpit.

---

## Arquivos Docker

### docker-compose.yml (Produção/Recomendado)

Arquivo completo com healthcheck, networks e volumes:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: ${APP_NAME:-saque-pix}-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./vendor:/var/www/vendor
      - /var/www/runtime
    ports:
      - "${SERVER_PORT:-9502}:${SERVER_PORT:-9502}"
    environment:
      - APP_NAME=${APP_NAME:-saque-pix}
      - APP_ENV=prod
      - TZ=America/Sao_Paulo
      - DB_DRIVER=mysql
      - DB_HOST=mysql
      - DB_PORT=3306
      - DB_DATABASE=saque_pix
      - DB_USERNAME=root
      - DB_PASSWORD=root
      - DB_CHARSET=utf8mb4
      - SERVER_PORT=${SERVER_PORT:-9502}
    networks:
      - app-network
    depends_on:
      mysql:
        condition: service_healthy
    command: ["/bin/sh", "-c", "composer install --no-interaction --prefer-dist && php bin/hyperf.php start"]

  mysql:
    image: mysql:8.0
    container_name: ${APP_NAME:-saque-pix}-mysql
    restart: unless-stopped
    environment:
      - MYSQL_ROOT_PASSWORD=root
      - MYSQL_DATABASE=saque_pix
    volumes:
      - mysql-data:/var/lib/mysql
    ports:
      - "${MYSQL_EXTERNAL_PORT:-3306}:3306"
    networks:
      - app-network
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost", "-u", "root", "-proot"]
      interval: 5s
      timeout: 5s
      retries: 5

  mailpit:
    image: axllent/mailpit:latest
    container_name: ${APP_NAME:-saque-pix}-mailpit
    restart: unless-stopped
    ports:
      - "${MAILPIT_WEB_PORT:-8025}:8025"  # Interface web
      - "${MAILPIT_SMTP_PORT:-1025}:1025"  # SMTP
    networks:
      - app-network

volumes:
  mysql-data:

networks:
  app-network:
    name: ${APP_NAME:-saque-pix}-network
    driver: bridge
```

### Desenvolvimento Local

Usar o mesmo `docker-compose.yml` com `APP_ENV=dev`:

```bash
# Criar .env para desenvolvimento
cat > .env << 'EOF'
APP_NAME=saque-pix
APP_ENV=dev
APP_DEBUG=true
SERVER_PORT=9502
DB_PASSWORD=root
MYSQL_EXTERNAL_PORT=3306
MAILPIT_WEB_PORT=8025
MAILPIT_SMTP_PORT=1025
EOF

# Iniciar servicos
docker-compose up -d
```

---

## Dockerfile (Produção)

Imagem otimizada para produção:

```dockerfile
FROM hyperf/hyperf:8.4-alpine-v3.21-swoole

LABEL maintainer="Saque PIX" version="1.0" license="MIT"

# Configurar timezone
ENV TIMEZONE=America/Sao_Paulo \
    APP_ENV=prod \
    SCAN_CACHEABLE=(true)

# Instalar dependências e configurar PHP
RUN set -ex \
    && apk update \
    && apk add --no-cache git \
    && php -v \
    && php --ri swoole \
    && cd /etc/php* \
    && { \
        echo "upload_max_filesize=128M"; \
        echo "post_max_size=128M"; \
        echo "memory_limit=1G"; \
        echo "date.timezone=${TIMEZONE}"; \
    } | tee conf.d/99_overrides.ini \
    && ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone \
    && rm -rf /var/cache/apk/* /tmp/* /usr/share/man

WORKDIR /opt/www

# Copiar arquivos do projeto
COPY . /opt/www

# Instalar dependências (sem dev, otimizado)
RUN composer install --no-dev -o && php bin/hyperf.php

EXPOSE 9501

ENTRYPOINT ["php", "/opt/www/bin/hyperf.php", "start"]
```


---

## Comandos de Deploy

### Produção

```bash
# Build e iniciar todos os servicos
docker-compose up -d --build

# Ver logs da aplicacao
docker-compose logs -f app

# Ver logs do MySQL
docker-compose logs -f mysql

# Acessar container da app
docker-compose exec app sh

# Executar comandos dentro do container
docker-compose exec app php bin/hyperf.php migrate

# Parar todos os servicos
docker-compose down

# Parar e remover volumes (limpa dados do MySQL)
docker-compose down -v
```


---

## Health Check

O projeto inclui um `HealthController` com endpoints de monitoramento e teste:

```bash
# Verificar saude da aplicacao (banco de dados, timestamp)
curl http://localhost:9502/health
# Resposta: {"status":"ok","timestamp":"2026-05-13...","database":{"status":"ok"}}

# Testar erro de validacao (HTTP 422)
curl http://localhost:9502/health/validation-error
# Resposta: {"code":422,"message":"Validation Error","errors":{...}}

# Testar erro de servidor (HTTP 500)
curl http://localhost:9502/health/error
# Resposta: {"code":500,"message":"Server Error","errors":{...}}

# Testar rota inexistente (HTTP 404)
curl http://localhost:9502/rota-que-nao-existe
# Resposta: {"code":404,"message":"Not Found","errors":{...}}
```

Todos os erros retornam JSON estruturado com `code`, `message` e `errors`.

---

## Serviços e Portas

Todas as portas sao configuraveis via arquivo `.env`:

| Servico | Variavel | Porta Padrao | Descricao |
|---------|----------|--------------|-----------|
| Hyperf App | `SERVER_PORT` | 9502 | API Saque PIX |
| MySQL | `MYSQL_EXTERNAL_PORT` | 3306 | Banco de dados (externa) |
| Mailpit Web | `MAILPIT_WEB_PORT` | 8025 | Interface web de email |
| Mailpit SMTP | `MAILPIT_SMTP_PORT` | 1025 | Servidor SMTP (externo) |

### Evitando Conflitos de Porta

Quando houver multiplos projetos rodando, altere apenas estas portas no `.env`:

```env
# Projeto 1 - saque-pix
APP_NAME=saque-pix
SERVER_PORT=9502
MYSQL_EXTERNAL_PORT=3306
MAILPIT_WEB_PORT=8025
MAILPIT_SMTP_PORT=1025

# Projeto 2 - saque-pix-novo
APP_NAME=saque-pix-novo
SERVER_PORT=9503
MYSQL_EXTERNAL_PORT=3307
MAILPIT_WEB_PORT=8026
MAILPIT_SMTP_PORT=1026
```

Os nomes dos containers serao automaticamente gerados como:
- `saque-pix-novo-app`
- `saque-pix-novo-mysql`
- `saque-pix-novo-mailpit`

---

## Checklist de Deploy

### Pre-deploy

- [ ] `.env` configurado com dados corretos
- [ ] `APP_ENV=prod` para producao
- [ ] `SCAN_CACHEABLE=true` para producao
- [ ] Timezone configurado para `America/Sao_Paulo`
- [ ] Senhas fortes no banco de dados (producao)

### Build

- [ ] `docker-compose build` sem erros
- [ ] Imagens atualizadas (`docker-compose pull` para base images)

### Execucao

- [ ] `docker-compose up -d` servicos iniciados
- [ ] MySQL healthcheck passando
- [ ] Health check `/health` retornando OK
- [ ] Logs sem erros criticos

### Post-deploy

- [ ] Migrations executadas (`php bin/hyperf.php migrate`)
- [ ] Seeders executados se necessario
- [ ] Teste de saque PIX funcionando
- [ ] Email de notificacao chegando no Mailpit

---

## Solucao de Problemas

### Porta ja em uso

```bash
# Ver o que esta usando a porta
sudo lsof -i :9502

# Mudar porta no .env (nao e necessario editar docker-compose.yml)
SERVER_PORT=9503
MYSQL_EXTERNAL_PORT=3307
MAILPIT_WEB_PORT=8026
MAILPIT_SMTP_PORT=1026

# Recriar containers
docker-compose down
docker-compose up -d
```

### Permissao negada no runtime

```bash
# No host
sudo chown -R $USER:$USER runtime/

# Ou no container
docker-compose exec app chmod -R 777 runtime/
```

### MySQL nao conecta

```bash
# Verificar se MySQL esta saudavel
docker-compose ps

# Ver logs do MySQL
docker-compose logs mysql

# Testar conexao manual
docker-compose exec mysql mysql -u root -p -e "SHOW DATABASES;"
```

---

## Documentacao Completa

- [01 - Desafio Tecnofit](01-desafio_tecnofit.md)
- [02 - Instalacao](02-instalacao.md)
- [03 - Estrutura](03-estrutura.md)
- [04 - Comandos](04-comandos.md)
- [05 - Configuracoes](05-configuracoes.md)
- **06 - Deploy** (este arquivo)
