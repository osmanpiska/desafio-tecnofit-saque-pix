# 01 - Case Tecnico: Saque PIX

Fonte: `Case Tecnico - Saque Pix_.pdf`.

---

## Saque PIX

O projeto se refere a uma plataforma de conta digital que permite ao usuario realizar saque PIX do saldo disponivel na plataforma.

## Tecnologias base a serem utilizadas

- Docker (https://docker.com)
- PHP Hyperf 3 (https://hyperf.wiki)
- Mysql 8
- Mailhog (ou equivalente)

## Tabelas do banco de dados

### `account`

- `id`: string (uuid)
- `name`: string
- `balance`: decimal

### `account_withdraw`

- `id`: string (uuid)
- `account_id`: string (uuid)
- `method`: string
- `amount`: decimal
- `scheduled`: boolean
- `scheduled_for`: datetime
- `done`: boolean
- `error`: boolean
- `error_reason`: string

### `account_withdraw_pix`

- `account_withdraw_id`: string (uuid)
- `type`: string
- `key`: string

Voce pode adicionar qualquer coluna nas tabelas caso seja necessario para sua implementacao.

> **Definir saldo disponivel:** para adicionar saldo na conta, pode ser feita a atualizacao diretamente no banco de dados do registro na tabela `account`.

---

## Fluxos

### Realizar saque - estrutura obrigatoria

```json
POST /account/{accountId}/balance/withdraw
Body: {
  "method": "PIX",
  "pix": {
    "type": "email",
    "key": "fulano@email.com"
  },
  "amount": 150.75,

  // Define o agendamento do saque
  // (null informa que o saque deve ocorrer imediatamente)
  "schedule": null | "2026-01-01 15:00"
}
```

## Regras de negocio

- A operacao do saque deve ser registrada no banco de dados, usando as tabelas `account_withdraw` e `account_withdraw_pix`.
- O saque sem agendamento deve realizar o saque de imediato.
- O saque com agendamento deve ser processado somente via cron.
- O saque deve deduzir o saldo da conta na tabela `account`.
- Atualmente so existe a opcao de saque via PIX, podendo ser somente para chaves do tipo email.
- A implementacao deve possibilitar uma facil expansao de outras formas de saque no futuro.
- Nao e permitido sacar um valor maior do que o disponivel no saldo da conta digital.
- O saldo da conta nao pode ficar negativo.
- Para saque agendado, nao e permitido agendar para um momento no passado.
- Para saque agendado, deve ser usado o componente crontab do proprio Hyperf.
- Preferencialmente rodar a cada 5s no Hyperf: `*/5 * * * * *`.

---

## Enviar email de notificacao

Apos realizar o saque, deve ser enviado um email para o email do PIX, informando que o saque foi efetuado.

O template do email e irrelevante, a unica exigencia e conter:

- data e hora do saque;
- valor sacado;
- dados do PIX informado.

Utilize um servico de teste de email, por exemplo, Mailhog.

---

## Processar saque agendado

Uma cron ira verificar se ha saques agendados pendentes e fara o processamento do saque.

Caso no momento do saque for identificado que nao ha saldo suficiente, deve ser registrado no banco de dados que o saque foi processado, mas com falha de saldo insuficiente.

---

## Pontos de atencao

Desenvolva o projeto garantindo:

- Performance.
- Observabilidade.
- Compatibilidade com escalabilidade horizontal.
- Seguranca.

E obrigatorio que o projeto seja totalmente dockerizado:

- Utilize o docker compose para compor os servicos utilizados.
- Antes do envio deste projeto, teste a estrutura docker do zero para garantir que nada ficou dependente do seu ambiente.

Quaisquer opcoes sobre decisoes tomadas e/ou outras formas de implementacoes, descreva-as no `README.md` do projeto.

**Foque no que foi pedido no case.**

De novo, **foque no que foi pedido no case**.

---

## Proximo Passo

Ver [02-instalacao.md](02-instalacao.md) para comecar a instalacao do ambiente.