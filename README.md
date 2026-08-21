# Investment Platform

> Este repositório existe exclusivamente para a avaliação de um desafio técnico — não é um projeto em produção nem aceita contribuições.

Plataforma de investimentos que conecta empresas em captação a investidores. Suporta duas modalidades de oferta sobre o mesmo núcleo financeiro: participação em investimento coletivo e nota comercial.

## Stack

| Camada | Tecnologia |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13 |
| Banco de dados | PostgreSQL |
| Organização | Monólito modular (`internachi/modular`) |
| Dinheiro | `brick/math` + value object próprio, escala de 8 casas |
| Testes | Pest |
| Análise estática | PHPStan (level 1) |

## Subindo o ambiente

```bash
cp .env.example .env
composer install
php artisan key:generate
docker compose up -d              # PostgreSQL na porta 5434
php artisan migrate --seed        # schema + dataset de demonstração
```

Rodando os testes e a análise estática:

```bash
./vendor/bin/pest
./vendor/bin/phpstan analyse
```

> A suíte usa SQLite em memória (`phpunit.xml`), enquanto o ambiente de trabalho usa PostgreSQL.

## Domínio

### Ciclo de vida do aporte

Um **aporte** (`Placement`) representa o investimento de uma pessoa em uma oferta. Ele atravessa a máquina de estados abaixo, e carrega dois campos de estado que evoluem em paralelo: `status` (a etapa do ciclo) e `process` (a situação da reserva).

```
Draft ──▶ Contract ──▶ Payment ──▶ Active ──▶ Finished
  │           │           │           │
  └───────────┴───────────┴───────────┴──▶ Cancelled

Active ──▶ Withdrawing ──▶ WithdrawalCompleted
```

Cada etapa é uma classe em `app-modules/placements/src/Actions/StateMachine/`, resolvida por `PlacementStatus::getAction()`. A transição acontece em `AbstractPlacementStep::handle()`.

### Carteira e movimentações

Todo aporte ativo tem uma **carteira** (`Wallet`), que mantém cinco valores: saldo, rendimentos acumulados, rendimentos disponíveis, total investido e total resgatado.

As movimentações financeiras são registradas em um razão estruturado em duas tabelas:

- `ledgers` — agrupa uma operação e carrega o estado dela
- `ledger_entries` — as pernas de débito e crédito, por carteira

### Rendimento periódico

Cada oferta tem uma taxa por período (`startup_offer_rates`). O cálculo e o crédito do rendimento de uma carteira vivem em `PlacementMonthlyRentabilityAction`, que aceita execução em modo simulação (`dryRun`) antes da aplicação real. Quando o aporte tem resgate automático habilitado, a rotina encadeia a solicitação e a aprovação do resgate.

### Resgates

O fluxo é de duas etapas: `RequestWithdrawalAction` registra a solicitação, `ApproveWithdrawalAction` a efetiva, e `RejectWithdrawalAction` a recusa. Há uma janela de resgate por oferta, definida pela periodicidade e pela carência.

### Limite por investidor

Existe um teto de valor acumulado por investidor por ano-calendário, que varia conforme a categoria dele (padrão, alta renda ou qualificado). A apuração está em `CalculateInvestorCvmCapAction` e é verificada na confirmação do aporte.

### Assinatura de contratos

Contratos são assinados por um provedor externo, que devolve o resultado por webhook em `POST /webhooks/signature`. O provedor reenvia a chamada até receber um 2xx, e não garante entrega única por evento. Os eventos alimentam `AdvancePlacementOnContractSignedListener` e `CancelPlacementListener`.

## Estrutura

```
app/
├── Casts/AsMoney.php              # cast monetário
├── ValueObjects/Money.php         # value object de dinheiro
├── Models/Users/                  # investidor
├── Models/Finance/                # perfil financeiro
└── Reporting/PositionQueries.php  # consultas das telas de posição

app-modules/
├── banking/                       # carteiras, movimentações, resgates, rendimento
├── offers/                        # ofertas, taxas, empresas captadoras
└── placements/                    # aportes, máquina de estados, limite, contratos
```

## Dataset de demonstração

`DemoDataSeeder` gera uma base com a forma e o volume de uma operação real: ~900 investidores, 50 ofertas com 18 meses de taxas, ~2.000 aportes distribuídos pelo ciclo de vida, ~1.500 carteiras e o histórico de movimentações que sustenta os saldos. Inclui também ajustes e correções aplicados ao longo do tempo pela operação.

Todas as senhas de investidor são `password`.
