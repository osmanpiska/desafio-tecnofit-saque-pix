# 05 - Guia de Deploy

O projeto ja possui `Dockerfile` e `docker-compose.yml` na raiz. Aqui estao os ajustes necessarios.

## Ajustes no Dockerfile

O `Dockerfile` ja esta configurado com a imagem oficial `hyperf/hyperf:8.4-alpine-v3.21-swoole`.

### Alteracao necessaria: Timezone

Mude o timezone de `Asia/Shanghai` para `America/Sao_Paulo`:

```dockerfile
FROM hyperf/hyperf:8.4-alpine-v3.21-swoole

ENV TIMEZONE=America/Sao_Paulo \
    APP_ENV=prod \
    SCAN_CACHEABLE=(true)

# Resto do Dockerfile permanece igual
```

Ou passe como argumento no build:

```bash
docker build --build-arg timezone=America/Sao_Paulo -t saque-pix .
```

## Ajustes no Docker Compose

O `docker-compose.yml` atual usa `dev.Dockerfile` que nao existe. Crie/adapte o arquivo:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
      args:
        timezone: America/Sao_Paulo
    container_name: saque-pix-app
    ports:
      - "9501:9501"
    environment:
      - APP_ENV=dev
      - SCAN_CACHEABLE=false
      - DB_HOST=mysql
      - DB_DATABASE=saque_pix
      - DB_USERNAME=root
      - DB_PASSWORD=secret
    depends_on:
      - mysql
    volumes:
      - ./:/opt/www
      - ./runtime/logs:/opt/www/runtime/logs

  mysql:
    image: mysql:8.0
    container_name: saque-pix-mysql
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: saque_pix
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

volumes:
  mysql_data:
```

## Comandos de Deploy

```bash
# Build e subir todos os servicos
docker-compose up -d --build

# Ver logs
docker-compose logs -f app

# Acessar container da app
docker-compose exec app sh

# Parar tudo
docker-compose down

# Parar e remover volumes
docker-compose down -v
```

## Producao

Para producao, use o Dockerfile diretamente:

```bash
# Build imagem de producao
docker build --build-arg timezone=America/Sao_Paulo -t saque-pix:prod .

# Rodar
docker run -d -p 9501:9501 --name saque-pix saque-pix:prod
```

## Health Check

Adicione no `config/routes.php`:

```php
Router::get('/health', function () {
    return [
        'status' => 'ok',
        'time' => date('Y-m-d H:i:s'),
    ];
});
```

## Checklist de Deploy

- [ ] Timezone alterado para America/Sao_Paulo no Dockerfile
- [ ] MySQL adicionado ao docker-compose.yml
- [ ] Variaveis de banco configuradas no docker-compose
- [ ] `.env` configurado com dados reais
- [ ] Rota /health criada
- [ ] Build testado: `docker-compose up -d --build`

## Documentacao Completa

- [01 - Desafio Tecnofit](01-desafio_tecnofit.md)
- [02 - Instalacao](02-instalacao.md)
- [03 - Estrutura](03-estrutura.md)
- [04 - Comandos](04-comandos.md)
- [05 - Configuracoes](05-configuracoes.md)
- **06 - Deploy** (este arquivo)
