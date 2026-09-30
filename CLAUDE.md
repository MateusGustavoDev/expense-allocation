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

Serviços do `compose.yaml`: `app` (PHP), `mysql`, `queue` (worker `php artisan queue:work`), `scheduler` (`php artisan schedule:work`) e `vite` (assets em dev). `app`, `queue` e `scheduler` usam a imagem do `Dockerfile` (serversideup/php + bcmath). O projeto deve subir apenas com `docker compose up` seguindo o README.

O worker mantém o código em memória: depois de alterar Jobs ou Actions, rode `docker compose restart queue`.

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
├── Services/                # Integrações externas e lógica pura reutilizável
│   ├── Csv/CsvReader.php
│   ├── Money/               # AllocationSplitter, CurrencyConverter, Decimal
│   └── ExchangeRates/       # BcbPtaxProvider, ExchangeRates (cache)
└── Validation/              # Regras compartilhadas entre API e CSV (ExpenseRules)
lang/pt_BR/                  # Mensagens de validação em português e nomes dos campos
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
- Despesas em **USD**: a despesa é **salva primeiro** com `conversion_status = pending` e, depois do commit, `ConvertExpenseCurrencyJob` é enfileirado. A indisponibilidade da API de câmbio **nunca** impede o cadastro.
- O job usa os atributos `#[Tries(5)]` e `#[Backoff(60, 300, 900, 3600)]`, é único por despesa (`ShouldBeUnique`) e, ao esgotar as tentativas, `failed()` marca `conversion_status = failed`. Provedor sem cotação para a data (`ExchangeRateUnavailableException`) falha na hora, sem novas tentativas.
- `ConvertExpenseCurrency` é idempotente e trava a linha (`lockForUpdate`): executar duas vezes não converte duas vezes. O valor em BRL é rateado de novo com o `AllocationSplitter`.
- Rede de segurança: `expenses:convert-pending` (agendado a cada 10 min em `routes/console.php`) reenfileira pendentes cuja cotação já existe; com `--failed`, reprocessa também as que falharam. Reprocessamento manual: `POST /api/expenses/{id}/retry-conversion`.
- A cotação usada é a da **data da despesa**, só depois que o dia termina no horário de Brasília (`services.ptax.timezone`). Despesa de hoje fica pendente e é convertida pela varredura no dia seguinte.
- Cotações obtidas são persistidas em `exchange_rates` (moeda + data pedida, único) e reutilizadas — a API externa é consultada no máximo uma vez por data.
- Provedor: **API PTAX do Banco Central** (cotação de **venda**), atrás da interface `Contracts\ExchangeRateProvider`, registrada no `AppServiceProvider`. Trocar de provedor não exige mudança fora de `Services/ExchangeRates` e do binding.
- Datas sem cotação (fim de semana/feriado): o provedor consulta os 7 dias até a data e usa a cotação mais recente. A data efetivamente usada fica em `expenses.exchange_rate_date`.
- Conversão com **bcmath** (`CurrencyConverter`), arredondamento comercial: centavos x cotação estouraria `int64` e float perde precisão.
- Chamadas HTTP sempre com `timeout()` explícito. Falha de rede lança exceção — o retry é responsabilidade da fila, não do cliente HTTP.

### Importação CSV

- Formato: `data;descricao;fornecedor;valor;moeda;rateio`, com cabeçalho. Rateio: `slug-unidade:percentual|slug-unidade:percentual`. Exemplo em `docs/examples/despesas-setembro.csv`.
- Unidades são referenciadas no CSV pelo `slug` (único), resolvido com uma única consulta para o arquivo inteiro.
- Cada linha é validada **isoladamente** com as mesmas `ExpenseRules` da API (`Validator::make()`) e criada pela mesma `CreateExpense` Action, cada uma em sua própria transação. Uma linha inválida **nunca** interrompe as demais.
- Moeda e slug aceitam minúsculas; descrição e fornecedor têm espaços repetidos colapsados, como na API.
- Arquivo vazio ou com cabeçalho diferente do esperado é recusado inteiro (`InvalidCsvFileException`, HTTP 422).
- Retorno: `ImportReport` com total de linhas, quantidade importada e a lista de erros por **número da linha no arquivo** (cabeçalho = linha 1), com o conteúdo original e as mensagens em português.
- `Services/Csv/CsvReader` trata BOM UTF-8, linhas em branco (sem deslocar a numeração), `\r\n` e arquivos em Windows-1252. Cada registro ocupa uma linha física.
- Importação síncrona, dentro da requisição (limite de 5 MB). Arquivos grandes seriam processados em um job, com o relatório consultado depois.

### Relatório

- Total em BRL por unidade num período (`date_from`, `date_to`, inclusivos), calculado **no banco** (`SUM ... GROUP BY` sobre `expense_allocations.amount_brl_cents`), nunca somando coleções em PHP. `GET /api/reports/unit-totals`.
- Considera apenas despesas com `conversion_status = converted`. O relatório informa separadamente pendentes e falhas do período, com a soma na moeda original, e `is_complete = false` enquanto houver alguma.
- Todas as unidades aparecem, inclusive as sem despesa no período (total zero), ordenadas do maior total para o menor.
- Participação de cada unidade em pontos-base, com arredondamento comercial: a soma das participações pode diferir de 100% em centésimos; os totais em centavos sempre fecham.
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

### Tokens de design

- Paleta `ds-{cor}-{tom}` no `@theme` de `resources/css/app.css`: `primary` (laranja Grid, #FE8400 = 500), `gray`, `blue`, `green`, `yellow`, `red` (50–900), mais `ds-black` e `ds-white`.
- Papéis: success = green, warning = yellow, danger = red, info = blue, interface = gray, ação principal e item ativo = primary.
- Nunca cores arbitrárias (`bg-[#...]`) nem a paleta padrão do Tailwind (`bg-blue-600`) nas views.
- Texto sobre `primary-500` é `ds-black`: branco tem contraste 2.47:1 e reprova no WCAG AA. Links em laranja usam `primary-700`.
- Foco: `focus-visible:ring-2 ring-ds-primary-600` (o 500 não atinge 3:1 contra o branco). Campos: borda `primary-600` + anel `primary-500/25`.
- Fontes: Inter (`font-sans`) e JetBrains Mono (`font-mono`, identificadores). Raio: `rounded-lg` em controles, `rounded-xl` em cards e tabela, `rounded-2xl` em modal.
- O Tailwind só gera classes escritas por extenso: nunca monte nome de classe por concatenação (`"bg-ds-{$cor}-100"`). Use mapas com as classes completas; exceção declarada em `@source inline()`.

### Componentes base

Blade components anônimos em `resources/views/components/ui/`. Catálogo com todas as variantes em **`/ui`** (só em ambiente local; `?open=nome` abre um modal). **Nunca** estilize botão, campo, badge ou tabela manualmente fora deles.

| Componente | Props principais |
| --- | --- |
| `x-ui.button` | `variant` (`primary`, `secondary`, `outline`, `ghost`, `danger`, `danger-outline`, `link`), `size` (`sm`, `md`, `lg`), `icon`, `icon-direction`, `icon-only` (exige `aria-label`), `full`, `loading`, `href` |
| `x-ui.input` | `name`, `label`, `hint`, `error`, `required`, `icon`, `icon-direction`, `password`, `mono`, `size`, `variant` (`default`, `soft`), `full` |
| `x-ui.select` | `name`, `label`, `hint`, `error`, `required`, `options` (`[valor => rótulo]`), `placeholder`, `value`, `size`, `full` |
| `x-ui.textarea` / `x-ui.checkbox` / `x-ui.segmented` | campo de texto longo / caixa de seleção / escolha única lado a lado (radios) |
| `x-ui.date-picker` | `name`, `label`, `hint`, `error`, `required`, `placeholder`, `value` (`AAAA-MM-DD`), `min`, `max`, `size`, `full` |
| `x-ui.date-range-picker` | idem, com valor `['from' => ..., 'to' => ...]` e atalhos (Hoje, Últimos 7/30 dias, Este mês, Mês passado, Este ano) |
| `x-ui.field` | moldura (rótulo, `*`, ajuda, erro) para controles customizados |
| `x-ui.badge` / `x-ui.status-badge` | `variant` (`neutral`, `primary`, `success`, `warning`, `danger`, `info`, `outline`, `mono`), `size`, `icon`, `dot` / `status` (`ConversionStatus`) |
| `x-ui.alert` | `variant` (`info`, `success`, `warning`, `danger`), `title`, `icon`, slot `actions` |
| `x-ui.card` / `x-ui.stat` / `x-ui.empty` | superfície com título e slots `actions`/`footer` / indicador numérico / estado vazio |
| `x-ui.page-header` | `title`, `description`, `breadcrumbs` (`[rótulo => url]`), slot `actions` |
| `x-ui.table` + `.toolbar`, `.head`, `.row`, `.cell`, `.empty`, `.loading` | slots `toolbar`, `head`, `footer`; `head` com `sortable`/`sorted-by`/`direction` chama `sortBy()` |
| `x-ui.pagination` | `paginator` (retorno de `->paginate()`), `livewire` (usa `gotoPage()`), `label` |
| `x-ui.modal` / `x-ui.confirm` | abertos por evento `open-modal` com o `name`; confirm com `action` (método Livewire) e `danger` |
| `x-ui.dropdown` + `.item`, `.separator` | slot `trigger`; item com `icon`, `danger`, `href` |
| `x-ui.toaster` | já no layout; dispare com `$this->dispatch('toast', type: 'success', message: '...')` |

Convenções:

- Props em kebab-case (`icon-only`). Tudo o que não é prop vai para o elemento nativo (`wire:model`, `wire:click`, `x-on:*`, `aria-*`, `type`).
- Em campos, `class` vai para o **invólucro** (layout: `w-72`, `col-span-2`); aparência vem das props.
- `name` dos campos vem do atributo ou do `wire:model`; o erro é lido de `$errors` por esse nome, com `aria-invalid` e `aria-describedby`.
- Botão com `wire:click` ou `wire:target` mostra loading e fica desabilitado sozinho enquanto o Livewire processa a ação.
- Variante ou tamanho inválido lança exceção: erro de digitação aparece no desenvolvimento, não em produção.
- Ícones Lucide pelo nome (`icon="plus"`), via `x-ui.icon`.
- Layout da aplicação em `resources/views/layouts/app.blade.php` (`layouts::app`, usado pelos componentes Livewire de página).

Alpine e interatividade:

- O `<body>` tem `x-data`: diretivas Alpine (`x-on`, `$dispatch`) só funcionam dentro de um escopo `x-data`.
- Livewire e Alpine são carregados pelo `resources/js/app.js` (ESM do Livewire + `@livewireScriptConfig` no layout), onde os componentes Alpine da aplicação são registrados com `Alpine.data()` antes do `Livewire.start()`.
- Controles com estado próprio expõem o valor com `x-modelable`, para aceitar `wire:model` como um input nativo.
- Painéis sobrepostos (calendários, menus) usam `x-teleport="body"` + `x-anchor`: dentro de um card com `overflow-hidden` eles seriam recortados. Clique no gatilho não conta como "clique fora".
- Datas no JavaScript: nunca `toISOString()` para gerar `AAAA-MM-DD` (converte para UTC e muda o dia no Brasil); monte com as partes locais.
- Depois de `</x-slot>` sempre quebre a linha: o Blade compila para `@endslot` sem espaço, e texto colado (`</x-slot>Texto`) quebra a diretiva e deixa um buffer de saída aberto.

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
- HTTP externo **sempre** falseado com `Http::fake()` — `Http::preventStrayRequests()` está ativo no `TestCase` e faz qualquer requisição não simulada falhar. Helpers `fakePtax()` e `fakePtaxDown()` em `tests/Pest.php`.
- A fila roda em modo `sync` nos testes: quem não testa o job em si usa `Queue::fake()`.
- Regras que dependem de "hoje" fixam o relógio com `$this->travelTo()`.
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

- Botão, campo, badge ou tabela estilizados manualmente fora de `components/ui/`.
- Cores hardcoded, valores arbitrários de cor ou a paleta padrão do Tailwind nas views.
- Nome de classe do Tailwind montado por concatenação.
- `wire:model.live` sem necessidade real.
- Lógica de negócio em Alpine.js ou em `@php` nas views.
- Dado sensível em propriedade pública de componente Livewire.

### CI/CD

- Push direto em `main` ou `staging`.
- Squash merge de `staging` → `main`.
- Filtro `paths:` em workflow cujo check é obrigatório.
- Migration no comando de start do container, ou migration que quebra a versão anterior do código.
- `APP_DEBUG=true` em produção.
