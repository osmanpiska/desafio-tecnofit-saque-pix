# 01 - Guia de Instalacao

## Requisitos

- WSL2 com Ubuntu
- PHP 8.4+
- Composer
- Mise (gerenciador de versoes)

## Passo 1: Instalar Mise

```bash
# Instalar mise via script oficial
curl https://mise.run | sh

# Verificar instalacao
~/.local/bin/mise --version
```

## Passo 2: Ativar Mise no Shell

Adicionar ao `~/.bashrc`:

```bash
echo 'eval "$(/home/osmanpiska/.local/bin/mise activate bash)"' >> ~/.bashrc
source ~/.bashrc
```

## Passo 3: Instalar Dependencias de Compilacao

```bash
sudo apt update && sudo apt install -y \
  autoconf build-essential libssl-dev \
  libcurl4-openssl-dev libedit-dev libreadline-dev \
  zlib1g-dev libonig-dev libxml2-dev \
  libsqlite3-dev libzip-dev pkg-config \
  bison flex libbison-dev libgd-dev libpq-dev re2c
```

## Passo 4: Instalar PHP 8.4 via Mise

```bash
# Instalar PHP 8.4
mise install php@8.4

# Definir como global
mise use --global php@8.4

# Verificar
php -v  # Deve mostrar PHP 8.4.x
```

## Passo 5: Instalar Composer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=$HOME/.local/bin --filename=composer
rm composer-setup.php
```

## Passo 6: Criar Projeto Hyperf

```bash
# Entrar no diretorio do projeto
cd ~/Projetos/Teknofit/saque-pix2

# Instalar projeto (interativo)
composer create-project hyperf/hyperf-skeleton .
```

### Selecoes durante a instalacao:

| Pergunta | Resposta |
|----------|----------|
| Time zone | `America/Sao_Paulo` |
| Database (MySQL) | `y` |
| Redis Client | `n` |
| RPC protocol | `n` |
| Config Center | `n` |
| Constants | `y` |
| Async Queue | `n` |
| AMQP | `n` |
| Model Cache | `n` |
| Elasticsearch | `n` |
| Tracer | `n` |
| Pest | `y` |

## Passo 7: Verificar Instalacao

```bash
# Verificar PHP
php -v

# Verificar Composer
composer --version

# Verificar estrutura do projeto
ls -la
```

## Proximo Passo

Ver [03-estrutura.md](03-estrutura.md) para entender a arquitetura do projeto.
