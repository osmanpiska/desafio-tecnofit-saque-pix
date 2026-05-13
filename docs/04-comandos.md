# 03 - Comandos Uteis

## Iniciar Servidor de Desenvolvimento

```bash
# Modo padrao (com reload automatico)
php bin/hyperf.php start

# Modo daemon (roda em background)
php bin/hyperf.php start -d

# Parar servidor
php bin/hyperf.php stop
```

## Comandos CLI do Hyperf

```bash
# Listar todos os comandos disponiveis
php bin/hyperf.php list

# Ver informacoes do sistema
php bin/hyperf.php info

# Ver rotas registradas
php bin/hyperf.php route:list

# Limpar cache
php bin/hyperf.php vendor:publish
```

## Rodar Testes

```bash
# Rodar todos os testes com Pest
./vendor/bin/pest

# Rodar com cobertura
./vendor/bin/pest --coverage

# Rodar testes especificos
./vendor/bin/pest test/Unit/ExampleTest.php
```

## Logs

```bash
# Ver logs em tempo real
tail -f runtime/logs/hyperf.log

# Limpar logs
rm -rf runtime/logs/*
```

## Composer

```bash
# Instalar dependencias
composer install

# Atualizar dependencias
composer update

# Adicionar pacote
composer require nome/pacote

# Remover pacote
composer remove nome/pacote
```

## Mise (Gerenciamento de Versoes)

```bash
# Ver versao atual do PHP
php -v

# Listar versoes instaladas
mise list php

# Mudar versao do PHP
mise use php@8.4

# Definir versao global
mise use --global php@8.4
```

## Health Check

```bash
# Verificar se servidor esta rodando
curl http://localhost:9501/

# Verificar health (se configurado)
curl http://localhost:9501/health
```

## Proximo Passo

Ver [05-configuracoes.md](05-configuracoes.md) para configuracoes de ambiente.
