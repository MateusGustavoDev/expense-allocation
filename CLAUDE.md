# Expense Allocation — Diretrizes de Desenvolvimento

Sistema para ratear despesas compartilhadas entre unidades de empresas de um mesmo grupo: cadastro de unidades e despesas, rateio percentual, conversão de moeda, importação por CSV e relatório por unidade.

Este documento define stack, arquitetura, padrões de código, regras de domínio e o fluxo de CI/CD. Serve como referência para qualquer trabalho neste repositório.

---

## Stack

| Camada           | Tecnologia                                      |
| ---------------- | ----------------------------------------------- |
| Linguagem        | PHP 8.4 com `declare(strict_types=1)`           |
| Framework        | Laravel 13                                      |
| Banco            | MySQL 8                                         |
| ORM              | Eloquent                                        |
| Interface        | Blade + Livewire (componentes em classe)        |
| Estilos          | Tailwind CSS v4 (via Vite)                      |
| Filas            | Laravel Queues, driver `database`               |
| Autenticação     | Laravel Sanctum (tokens de API)                 |
| Testes           | Pest                                            |
| Formatação       | Laravel Pint                                    |
| Análise estática | Larastan (PHPStan)                              |
| Ambiente         | Docker Compose                                  |
| CI               | GitHub Actions                                  |
| Hospedagem       | Railway (environments `staging` e `production`) |

### Ambiente local

Todo o desenvolvimento roda via Docker — não é necessário PHP nem Composer instalados no host. Comandos PHP sempre dentro do container.

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --seed
docker compose exec app composer test           # Pest
docker compose exec app composer format         # Pint (aplica)
docker compose exec app composer format:check   # Pint (só verifica)
docker compose exec app composer analyse        # Larastan
```

Larastan roda no **nível 8** (`phpstan.neon`): tipos declarados em tudo e `null` tratado de forma estrita. Não use baseline nem `@phpstan-ignore` para silenciar erro — corrija o tipo.

Serviços do `compose.yaml`: `app` (PHP), `mysql`, `queue` (worker `php artisan queue:work`) e `vite` (assets em dev). O projeto deve subir apenas com `docker compose up` seguindo o README.

---

## Princípios Gerais

- Código, nomes de arquivos, classes, métodos, variáveis, rotas e colunas em **inglês**.
- Comentários em **português**, somente quando agregam valor real (o *porquê*, não o *o quê*).
- Textos exibidos ao usuário (mensagens de validação, labels, UI) em **português**.
- Sem emojis em código, comentários, commits ou documentação técnica.
- `declare(strict_types=1);` no topo de todo arquivo PHP.
- Tipagem completa: parâmetros, retornos e propriedades sempre tipados. Sem `mixed` quando o tipo é conhecido.
- Remova `dd()`, `dump()`, `var_dump()` e `ray()` antes de commitar.
- Nunca exponha secrets no código. Use `.env` e mantenha `.env.example` atualizado.
- Regra de negócio **nunca** fica em controller, componente Livewire ou view.
- Prefira soluções simples e explicáveis a soluções espertas.

---

## Estrutura de Pastas

```
app/
├── Actions/                 # Casos de uso — um por classe, método execute()
│   ├── Expenses/
│   │   ├── CreateExpense.php
│   │   ├── ConvertExpenseCurrency.php
│   │   └── ImportExpensesFromCsv.php
│   └── Reports/
│       └── GetUnitTotalsReport.php
├── Contracts/               # Interfaces (ExchangeRateProvider)
├── Data/                    # Objetos de valor imutáveis (ImportReport, AllocationShare)
├── Enums/                   # Currency, ConversionStatus
├── Exceptions/              # Exceções de domínio
├── Http/
│   ├── Controllers/Api/     # Controllers da API REST (finos)
│   ├── Requests/            # Form Requests — validação de entrada
│   └── Resources/           # API Resources — contrato de saída
├── Jobs/                    # ConvertExpenseCurrencyJob
├── Livewire/                # Componentes da interface, por domínio
├── Models/
├── Providers/               # Bindings do container (interface -> implementação)
└── Services/                # Integrações externas e lógica pura reutilizável
    ├── Money/AllocationSplitter.php
    └── ExchangeRates/BcbPtaxProvider.php
database/
├── factories/
├── migrations/
└── seeders/
resources/views/
├── components/ui/           # Blade components base (button, input, spinner...)
├── components/layouts/
└── livewire/
routes/
├── api.php
└── web.php
tests/
├── Unit/                    # Lógica pura (AllocationSplitter, parser de CSV)
└── Feature/                 # Endpoints, Actions com banco, Jobs, Livewire
```

**Regra central:** API e interface são **dois pontos de entrada para as mesmas Actions**. O controller da API e o componente Livewire chamam `CreateExpense::execute()` — nunca duplicam a regra.

---

## Nomenclatura

| Contexto               | Convenção                        | Exemplo                                     |
| ---------------------- | -------------------------------- | ------------------------------------------- |
| Classes                | `PascalCase`, singular           | `Expense`, `CreateExpense`                  |
| Métodos e variáveis    | `camelCase`                      | `$amountCents`, `splitByPercentages()`      |
| Tabelas                | `snake_case`, plural             | `expenses`, `expense_allocations`           |
| Colunas                | `snake_case`                     | `amount_cents`, `conversion_status`         |
| Rotas da API           | `kebab-case`, plural             | `/api/expenses`, `/api/reports/unit-totals` |
| Blade views/components | `kebab-case`                     | `expense-form.blade.php`, `<x-ui.button>`   |
| Controllers            | `{Resource}Controller`           | `ExpenseController`                         |
| Form Requests          | `{Resource}Request` se store e update têm as mesmas regras; senão `{Action}{Resource}Request` | `UnitRequest`, `StoreExpenseRequest` |
| API Resources          | `{Resource}Resource`             | `ExpenseResource`                           |
| Jobs                   | `{Verbo}{Coisa}Job`              | `ConvertExpenseCurrencyJob`                 |
| Actions                | Verbo + substantivo              | `ImportExpensesFromCsv`                     |
| Enums                  | Singular, cases em `PascalCase`  | `ConversionStatus::Pending`                 |
| Testes                 | Descritivos em inglês (Pest)     | `it('distributes leftover cents...')`       |

---

## Formato de Dados — API

Todo payload e toda resposta usam **`snake_case`** — padrão nativo do Laravel, sem transformação.

- Valores monetários trafegam como **string decimal** (`"1500.00"`) na entrada e na saída, nunca como float.
- Datas no formato `Y-m-d` (`"2026-09-01"`); timestamps em ISO 8601.
- Listagens são paginadas (`->paginate()`), retornando `data`, `links` e `meta` do Resource Collection.
- Erros de validação seguem o formato padrão do Laravel (HTTP 422 com `message` e `errors` por campo).

```json
{
  "data": {
    "id": 1,
    "description": "Licença CRM",
    "supplier": "Fornecedor X",
    "date": "2026-09-01",
    "amount": "1500.00",
    "currency": "USD",
    "exchange_rate": "5.412300",
    "amount_brl": "8118.45",
    "conversion_status": "converted",
    "allocations": [
      { "unit_id": 1, "unit_name": "Unidade A", "percentage": "50.00", "amount": "750.00", "amount_brl": "4059.23" }
    ]
  }
}
```

---

## Regras de Domínio

### Dinheiro

- **Todo valor monetário é persistido como inteiro em centavos** (`BIGINT UNSIGNED`, colunas com sufixo `_cents`). Nunca `FLOAT`/`DOUBLE`, nunca cálculo com float.
- Conversão entre string decimal e centavos acontece **somente na borda** (Form Request / Resource / parser de CSV), por uma função única e testada. Não use `(int) ($value * 100)` — float perde precisão.
- Percentuais são persistidos em **pontos-base** (`UNSIGNED INT`, `10000` = 100%), aceitando até 2 casas decimais na entrada.

### Rateio

- A soma dos percentuais precisa ser **exatamente 10000 pontos-base**. Validado no Form Request e novamente na Action (a Action também é chamada pelo import de CSV).
- Uma unidade não pode aparecer duas vezes no mesmo rateio.
- A divisão dos centavos usa o método do **maior resto** em `AllocationSplitter`: calcula a parte inteira de cada unidade e distribui os centavos restantes, um a um, para as maiores frações (desempate determinístico pela ordem de entrada). A soma das partes **sempre** é igual ao total.
  - Exemplo: 10000 centavos em 3 × 33,33% → `3334 + 3333 + 3333`.
- O rateio é calculado duas vezes com o mesmo algoritmo: sobre o valor original (`amount_cents`) e sobre o valor convertido (`amount_brl_cents`), para que o relatório em BRL feche exatamente.
- `AllocationSplitter` é uma classe pura, sem banco e sem framework — coberta por testes unitários, incluindo casos de borda (1 centavo, 1 unidade, percentuais quebrados).

### Conversão de Moeda

- Despesas em **BRL**: `exchange_rate = 1`, `amount_brl_cents = amount_cents`, `conversion_status = converted` na criação.
- Despesas em **USD**: a despesa é **salva primeiro** com `conversion_status = pending` e a conversão é despachada para a fila (`ConvertExpenseCurrencyJob`). A indisponibilidade da API de câmbio **nunca** impede o cadastro.
- O Job usa `$tries` e `backoff()` exponencial. Ao esgotar as tentativas, `failed()` marca `conversion_status = failed`. Um comando Artisan (`expenses:retry-conversions`) re-enfileira as falhas.
- A cotação usada é a da **data da despesa**. Cotações obtidas são persistidas em `exchange_rates` (moeda + data, único) e reutilizadas — a API externa é consultada no máximo uma vez por data.
- Provedor: **API PTAX do Banco Central**, atrás da interface `Contracts\ExchangeRateProvider`, registrada no container. Trocar de provedor não pode exigir mudança fora de `Services/ExchangeRates` e do binding.
- Datas sem cotação (fim de semana/feriado): usar o último dia útil anterior. Decisão documentada no README como pergunta ao negócio.
- Chamadas HTTP sempre com `timeout()` explícito. Falha de rede lança exceção — o retry é responsabilidade da fila, não do cliente HTTP.

### Importação CSV

- Formato: `data;descricao;fornecedor;valor;moeda;rateio`, com cabeçalho. Rateio: `slug-unidade:percentual|slug-unidade:percentual`.
- Unidades são referenciadas no CSV pelo `slug` (único).
- Cada linha é validada **isoladamente** (`Validator::make()` com as mesmas regras da criação) e criada pela mesma `CreateExpense` Action, cada uma em sua própria transação. Uma linha inválida **nunca** interrompe as demais.
- Retorno: `ImportReport` com total de linhas, quantidade importada e a lista de erros por **número da linha** (considerando o cabeçalho) com as mensagens.
- Tratar BOM UTF-8, linhas em branco e quebras de linha `\r\n`.

### Relatório

- Total em BRL por unidade num período (`date_from`, `date_to`, inclusivos), calculado **no banco** (`SUM ... GROUP BY`), nunca somando coleções em PHP.
- Considera apenas despesas com `conversion_status = converted`. O relatório informa separadamente a quantidade de despesas pendentes/falhas no período, para deixar claro quando o total está incompleto.
- Uma única Action (`GetUnitTotalsReport`) atende API e interface.

---

## Backend

### Models e Migrations

- Toda alteração de schema via migration. Nunca edite migration já commitada — crie outra.
- Chaves estrangeiras com `constrained()` e comportamento de delete explícito (`restrictOnDelete()` para unidades com despesas).
- Índices em colunas usadas em filtros (`expenses.date`, `expense_allocations.unit_id`).
- Enums nativos do PHP com `casts()` no model.
- Campos preenchíveis declarados com o atributo `#[Fillable([...])]` (Laravel 13). Nunca `#[Unguarded]` ou `$guarded = []`.
- Relacionamentos com tipo de retorno e generics para o Larastan (`@return HasMany<Unit, $this>`).
- Evite N+1: use `with()` ao carregar relacionamentos em listagens. `Model::shouldBeStrict()` está ativo fora de produção e lança exceção em lazy loading.
- Relacionamentos cuja ordem importa declaram `orderBy()` na própria relação: sem `ORDER BY`, o MySQL devolve as linhas na ordem do índice usado.
- Agregações (`sum`, `avg`) do MySQL chegam como string pelo PDO: converta explicitamente para `int`.

```php
#[Fillable(['description', 'supplier', 'date', 'amount_cents', 'currency'])]
final class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'currency' => Currency::class,
            'conversion_status' => ConversionStatus::class,
        ];
    }

    /**
     * @return HasMany<ExpenseAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(ExpenseAllocation::class);
    }
}
```

### Controllers

Controllers são finos: recebem o Form Request, chamam a Action e retornam o Resource. Sem regra de negócio, sem `try/catch` de domínio.

- **CRUD simples** (empresas, unidades) usa Eloquent direto no controller (`Company::create($request->validated())`). Criar uma Action que só repassa para o model é indireção sem ganho.
- **Actions** entram quando há regra de negócio (rateio, conversão, importação, relatório) ou quando a mesma operação é chamada por mais de um ponto de entrada (API, Livewire, CSV).
- Rotas com `Route::apiResource()` e route model binding (`show(Company $company)`) — registro inexistente vira 404 automaticamente.
- Remoção bloqueada por dependência lança `ResourceInUseException` (estende `ConflictHttpException`, HTTP 409).

```php
final class ExpenseController extends Controller
{
    public function store(StoreExpenseRequest $request, CreateExpense $createExpense): ExpenseResource
    {
        $expense = $createExpense->execute($request->toData());

        return new ExpenseResource($expense);
    }
}
```

### Form Requests

Toda entrada passa por um Form Request. Regras que dependem de vários campos (soma = 100%) ficam em `after()`. Form Requests de criação expõem `toData()`, que devolve um objeto de `app/Data` já normalizado (centavos, pontos-base, enums) para a Action.

```php
final class StoreExpenseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d'],
            // string + numeric + regex: formato exato que Decimal converte sem float
            'amount' => ['required', 'string', 'numeric', 'regex:/^\d{1,12}(\.\d{1,2})?$/', 'gt:0'],
            'currency' => ['required', Rule::enum(Currency::class)],
            'allocations.*.unit_id' => ['required', 'integer', 'distinct', 'exists:units,id'],
            // ...
        ];
    }

    public function toData(): ExpenseData { /* ... */ }
}
```

- Valores decimais chegam como **string** (`"1500.00"`); número JSON é recusado.
- `gt`/`lt`/`between` só comparam numericamente quando a regra `numeric` está presente — sem ela, comparam o **tamanho** da string.

### Actions

- Uma classe por caso de uso, com um único método público `execute()`, dependências injetadas no construtor.
- Operações que escrevem em mais de uma tabela rodam em `DB::transaction()`.
- Dispatch de Jobs após o commit (`->afterCommit()` ou `ShouldQueueAfterCommit`), para o worker nunca buscar uma despesa que ainda não existe.
- Violações de regra de negócio lançam exceções de domínio (`app/Exceptions`), renderizadas como 422 no handler global.

### API Resources

- Toda resposta passa por um Resource — nunca retorne model cru.
- A conversão de centavos para string decimal acontece aqui.
- Relacionamentos com `whenLoaded()`.

### Documentação da API

- OpenAPI gerado automaticamente pelo **Scramble** a partir de rotas, Form Requests e API Resources. UI em `/docs/api`, especificação em `/docs/api.json`, pública em todos os ambientes (gate `viewApiDocs`).
- Não duplique o contrato em anotações: tipos, validação e formato de resposta vêm do código.
- Todo controller da API recebe `#[Group('Nome')]`; todo método, um PHPDoc cuja primeira linha é o resumo em português (`Listar empresas`).
- Status diferente de 200 que o Scramble não infere: `/** @status 201 */` acima do `return`.
- Erros HTTP de domínio estendem as exceções HTTP do Symfony (ex.: `ConflictHttpException`) para aparecerem na documentação.
- Descrição de campo de entrada: comentário acima da regra no Form Request.

### Autenticação

- Rotas da API protegidas por `auth:sanctum`. Token emitido em `POST /api/login`.
- A interface usa autenticação por sessão padrão do Laravel.

---

## Interface (Blade + Livewire)

### Componentes base

Todos os elementos visuais reutilizáveis ficam em `resources/views/components/ui/` como Blade components anônimos. **Nunca** estilize um botão, input ou spinner manualmente fora deles.

| Componente     | Props                                                                                             |
| -------------- | ------------------------------------------------------------------------------------------------- |
| `x-ui.button`  | `variant` (`primary`, `outline`, `ghost`, `danger`), `size` (`sm`, `md`, `lg`), `full`, `loading` |
| `x-ui.input`   | `label`, `name`, `error` (lê de `$errors` automaticamente)                                        |
| `x-ui.select`  | `label`, `name`, `options`, `placeholder`                                                         |
| `x-ui.spinner` | `size`                                                                                            |
| `x-ui.card`    | slot + `title`                                                                                    |
| `x-ui.table`   | slots `head` e `body`                                                                             |
| `x-ui.modal`   | `name`, controlado por Alpine.js                                                                  |

```blade
{{-- resources/views/components/ui/button.blade.php --}}
@props(['variant' => 'primary', 'size' => 'md', 'full' => false, 'loading' => false])

@php
    $variants = [
        'primary' => 'bg-blue-600 text-white hover:bg-blue-700',
        'outline' => 'border border-blue-600 text-blue-600 hover:bg-blue-50',
        'ghost' => 'text-gray-700 hover:bg-gray-100',
        'danger' => 'bg-red-600 text-white hover:bg-red-700',
    ];
    $sizes = [
        'sm' => 'h-8 px-3 text-xs',
        'md' => 'h-10 px-4 text-sm',
        'lg' => 'h-12 px-6 text-base',
    ];
@endphp

<button
    {{ $attributes->merge(['type' => 'button'])->class([
        'inline-flex items-center justify-center gap-2 rounded-lg font-medium transition-colors disabled:pointer-events-none disabled:opacity-50',
        $variants[$variant],
        $sizes[$size],
        'w-full' => $full,
    ]) }}
>
    @if ($loading)
        <x-ui.spinner size="sm" />
    @endif
    {{ $slot }}
</button>
```

Cores e espaçamentos vêm de tokens definidos no `@theme` do Tailwind v4 (`resources/css/app.css`). Não use valores arbitrários (`bg-[#123456]`) nas views.

### Componentes Livewire

- Um componente por tela ou bloco com estado (`ExpenseForm`, `ExpenseList`, `CsvImport`, `UnitTotalsReport`).
- O componente **só** orquestra: valida, chama a Action, atualiza o estado. Regra de negócio fica na Action.
- Formulários com **Form Objects** (`Livewire\Form`).
- Estado de carregamento com `wire:loading` / `wire:target`.
- Use `wire:model` padrão (sincroniza no submit); `wire:model.live` só quando a UI precisa reagir a cada tecla (ex.: soma do rateio em tempo real).
- Interações puramente visuais (abrir modal, mostrar/ocultar) com **Alpine.js** no cliente — sem roundtrip ao servidor.
- Comunicação entre componentes por eventos (`$this->dispatch()` + `#[On]`).
- Propriedades públicas são enviadas ao navegador: nunca guarde dado sensível nelas, e trate-as como entrada do usuário (valide sempre).

---

## Testes

Prioridade de cobertura:

1. **Unit** — `AllocationSplitter` (fechamento de centavos, casos de borda) e conversão decimal ↔ centavos.
2. **Feature** — criação de despesa (rateio ≠ 100% rejeitado, unidade duplicada, BRL convertida na hora, USD fica pendente e dispara Job).
3. **Feature** — Job de conversão com `Http::fake()`: sucesso, API fora do ar (despesa continua existindo, Job relança), esgotamento marca `failed`, cache de cotação por data.
4. **Feature** — import de CSV com linhas válidas e inválidas misturadas; relatório de erros com número da linha correto.
5. **Feature** — relatório por unidade e período (limites de data, exclusão de pendentes).
6. **Feature** — autenticação da API (401 sem token).

Padrões:

- Pest com `RefreshDatabase`; dados via **factories**, nunca inserts manuais.
- HTTP externo **sempre** falseado com `Http::fake()` e `Http::preventStrayRequests()` — nenhum teste bate em API real.
- `Queue::fake()` para verificar dispatch; teste o Job chamando `handle()` diretamente para verificar comportamento.
- Um comportamento por teste, nome descrevendo a regra: `it('keeps the expense when the exchange API is down')`.
- Banco de testes MySQL (mesmo engine de produção), não SQLite.

---

## CI/CD e Deploy

### Fluxo de branches

```
feature/* ──PR──► staging ──(auto deploy)──► ambiente staging ──► validação manual
                                                   │
staging ─────────PR──────► main ──(auto deploy)──► produção
```

- Todo trabalho nasce numa branch `feature/*`, `fix/*` ou `chore/*` a partir de `staging`.
- `feature/*` → `staging` via PR. `staging` → `main` via PR. **Nenhum push direto** em `main` ou `staging`.
- A `main` só aceita PR vindo de `staging` (check `enforce-source-branch` no workflow falha se `github.head_ref != 'staging'`).
- **Merge sempre com merge commit — nunca squash.** Squash de `staging` → `main` cria um commit que não existe em `staging`, as branches divergem e o PR seguinte arrasta commits antigos ou conflitos. Merge commit também preserva o histórico granular dos commits.

### Branch protection (ruleset do GitHub) em `main` e `staging`

- Exigir pull request antes do merge (aprovações = 0 — o GitHub não permite aprovar o próprio PR).
- Exigir status checks obrigatórios passando e branch atualizada com a base.
- Bloquear force push e deleção da branch.
- **Sem bypass para administradores** — do contrário o próprio dono do repositório consegue dar push direto.

### Checks obrigatórios do PR

Workflow único em `.github/workflows/ci.yml`, disparado em `pull_request` para `main` e `staging`. Sem filtro de `paths:` — check obrigatório que é pulado fica eternamente "Expected" e trava o merge.

| Etapa            | Comando                                    |
| ---------------- | ------------------------------------------ |
| Formatação       | `composer format:check`                    |
| Análise estática | `composer analyse`                         |
| Build de assets  | `npm ci && npm run build`                  |
| Unit + Feature   | `php artisan test --exclude-group=browser` |
| E2E (navegador)  | `php artisan test --group=browser`         |

- MySQL roda como `services` do GitHub Actions, com health check antes dos testes.
- E2E de navegador com **Pest browser testing** (Playwright por baixo), limitado aos fluxos críticos: criar despesa com rateio, importar CSV, consultar relatório. Regras de negócio são cobertas por Unit/Feature, não por E2E.
- `concurrency` com `cancel-in-progress: true` para cancelar execuções antigas do mesmo PR.

### Deploy

- **Não há workflow de deploy no GitHub Actions.** O Railway fica conectado ao repositório e cada environment observa sua branch: `staging` → environment `staging`, `main` → environment `production`.
- **"Wait for CI" ativado** nos serviços: o Railway só faz deploy de um commit cujos checks do GitHub passaram.
- Aplicação é **um único deploy** (Laravel renderiza Blade/Livewire; o Vite só gera assets no build). Não existe deploy separado de frontend, nem ordem entre front e back.
- Serviços por environment, todos a partir do mesmo `Dockerfile`:
  - `web` — servidor HTTP da aplicação.
  - `worker` — `php artisan queue:work`, com config própria (`railway.worker.json`).
  - `mysql` — banco gerenciado do Railway, um por environment.
- `preDeployCommand: php artisan migrate --force` — roda uma vez antes da nova versão entrar no ar; se falhar, o deploy aborta e a versão anterior continua servindo. Nunca rodar migration no comando de start.
- `healthcheckPath: /up` (rota de health nativa do Laravel) — a troca de versão só acontece se a nova instância responder.
- Migrations devem ser **compatíveis com a versão anterior do código** durante o deploy (adicionar coluna nullable primeiro, remover só num deploy posterior).
- Variáveis de ambiente configuradas por environment no Railway. `APP_DEBUG=false` em produção, `APP_KEY` distinta por environment.

---

## Commits

- **Conventional Commits** em inglês: `feat:`, `fix:`, `test:`, `refactor:`, `chore:`, `docs:`, `ci:`.
- Commits pequenos e atômicos, cada um deixando o projeto funcionando e com testes passando.
- Teste junto com a funcionalidade que ele cobre, não um commit gigante de testes no final.
- Mensagem descreve o porquê quando não for óbvio: `feat: split allocations using largest remainder to keep cents consistent`.
- PRs mesclados com merge commit (ver CI/CD) — os commits da branch permanecem no histórico.

---

## O que Nunca Fazer

### Backend

- `float` ou `DOUBLE` para dinheiro, ou cálculo monetário com float.
- Regra de negócio em controller, componente Livewire ou view.
- Retornar model Eloquent direto na API sem Resource.
- `$guarded = []` ou `$request->all()` para preencher model — use `$request->validated()`.
- Query dentro de loop (N+1).
- Chamada HTTP externa sem `timeout()`, ou dentro do request de criação de despesa.
- Perder a despesa por falha na API de câmbio.
- Editar migration já commitada.
- Somar totais do relatório em PHP em vez de no banco.
- Teste que depende de API externa real.

### Interface

- Botão, input ou spinner estilizado manualmente fora de `components/ui/`.
- Cores hardcoded ou valores arbitrários do Tailwind nas views.
- `wire:model.live` sem necessidade real.
- Lógica de negócio em Alpine.js ou em `@php` nas views.
- Dado sensível em propriedade pública de componente Livewire.

### CI/CD

- Push direto em `main` ou `staging`.
- Squash merge de `staging` → `main`.
- Filtro `paths:` em workflow cujo check é obrigatório.
- Migration no comando de start do container, ou migration que quebra a versão anterior do código.
- `APP_DEBUG=true` em produção.
