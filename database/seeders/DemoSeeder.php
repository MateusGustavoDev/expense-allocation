<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Expenses\CreateExpense;
use App\Data\AllocationData;
use App\Data\ExpenseData;
use App\Enums\Currency;
use App\Models\Company;
use App\Models\Unit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Dados de demonstração: um grupo com 3 empresas e 5 unidades e seis meses de despesas realistas.
 *
 *     php artisan db:seed --class=DemoSeeder --force
 *
 * - Cada despesa passa pela CreateExpense, o mesmo caminho da API e da interface: rateio validado, centavos pelo
 *   AllocationSplitter e despesas em dólar enfileiradas para o worker converter pela PTAX real da data.
 * - Datas relativas ao dia em que roda (seis meses até ontem): o relatório do mês corrente nunca abre vazio.
 * - Valores variam, mas com semente fixa: rodar de novo produz o mesmo resultado. Se o grupo já existir, não faz nada.
 * - Custos compartilhados são rateados pelo número de funcionários de cada unidade, que cresce ao longo dos meses.
 */
final class DemoSeeder extends Seeder
{
    private const MONTHS = 6;

    private const MONTH_NAMES = [1 => 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    /**
     * Empresas e unidades, com o quadro de funcionários no primeiro e no último mês.
     *
     * @var array<string, array<string, array{name: string, headcount: array{int, int}}>>
     */
    private const GROUP = [
        'Atlas Tecnologia Ltda' => [
            'sp-matriz' => ['name' => 'Matriz São Paulo', 'headcount' => [48, 57]],
            'rj-filial' => ['name' => 'Filial Rio de Janeiro', 'headcount' => [22, 26]],
        ],
        'Atlas Pagamentos S.A.' => [
            'bh-operacoes' => ['name' => 'Operações Belo Horizonte', 'headcount' => [18, 22]],
            'rec-atendimento' => ['name' => 'Atendimento Recife', 'headcount' => [12, 16]],
        ],
        'Atlas Educação Ltda' => [
            'cwb-sede' => ['name' => 'Sede Curitiba', 'headcount' => [14, 15]],
        ],
    ];

    private Randomizer $random;

    /** @var array<string, int> */
    private array $unitIds = [];

    public function __construct(private readonly CreateExpense $createExpense) {}

    public function run(): void
    {
        if (Unit::query()->where('slug', 'sp-matriz')->exists()) {
            $this->command->warn('Dados de demonstração já existem: nada foi criado.');

            return;
        }

        $this->random = new Randomizer(new Mt19937(2026));
        $this->createGroup();

        $today = CarbonImmutable::now(config('app.business_timezone'))->startOfDay();
        $firstMonth = $today->startOfMonth()->subMonths(self::MONTHS - 1);
        $created = ['BRL' => 0, 'USD' => 0];

        for ($index = 0; $index < self::MONTHS; $index++) {
            $month = $firstMonth->addMonths($index);

            foreach ($this->expensesFor($month, $index) as $expense) {
                // Só datas passadas: a cotação PTAX de hoje ainda não é definitiva
                if ($expense->date->greaterThanOrEqualTo($today)) {
                    continue;
                }

                $this->createExpense->execute($expense);
                $created[$expense->currency->value]++;
            }
        }

        $this->command->info(sprintf(
            'Grupo Atlas criado: %d empresas, %d unidades, %d despesas em BRL e %d em USD (convertidas pelo worker).',
            count(self::GROUP),
            count($this->unitIds),
            $created['BRL'],
            $created['USD'],
        ));
    }

    private function createGroup(): void
    {
        foreach (self::GROUP as $companyName => $units) {
            $company = Company::query()->create(['name' => $companyName]);

            foreach ($units as $slug => $unit) {
                $this->unitIds[$slug] = Unit::query()->create(['company_id' => $company->id, 'name' => $unit['name'], 'slug' => $slug])->id;
            }
        }
    }

    /**
     * Despesas de um mês: recorrentes todo mês e eventuais em meses específicos do período.
     *
     * @return list<ExpenseData>
     */
    private function expensesFor(CarbonImmutable $month, int $index): array
    {
        $all = array_keys($this->unitIds);
        $headcount = $this->headcount($index);
        $people = array_sum($headcount);
        $tech = ['sp-matriz', 'rj-filial'];
        $competence = ' — '.self::MONTH_NAMES[$month->month].'/'.$month->year;

        // Reajuste anual dos aluguéis (IGP-M) a partir do quarto mês do período
        $rent = fn (int $cents): int => $index >= 3 ? intdiv($cents * 10_412, 10_000) : $cents;

        $expenses = [
            // SaaS cobrado em dólar, por usuário
            $this->usd('Google Workspace Business Standard'.$competence, 'Google', $month, 3, 1_440 * $people, $this->byHeadcount($all, $headcount)),
            $this->usd('Slack Pro'.$competence, 'Slack Technologies', $month, 5, 875 * $people, $this->byHeadcount($all, $headcount)),
            $this->usd('AWS — infraestrutura de produção'.$competence, 'Amazon Web Services', $month, 2, $this->vary(310_000 + 9_500 * $index, 800), ['sp-matriz' => 4_500, 'rj-filial' => 2_500, 'bh-operacoes' => 3_000]),
            $this->usd('GitHub Team'.$competence, 'GitHub', $month, 8, 400 * intdiv(($headcount['sp-matriz'] + $headcount['rj-filial']) * 45, 100), $this->byHeadcount($tech, $headcount)),
            $this->usd('Figma Organization — 9 editores'.$competence, 'Figma', $month, 12, 4_500 * 9, ['sp-matriz' => 5_000, 'rj-filial' => 3_000, 'cwb-sede' => 2_000]),
            $this->usd('Jira Software Standard'.$competence, 'Atlassian', $month, 15, 860 * 45, ['sp-matriz' => 4_000, 'rj-filial' => 2_500, 'bh-operacoes' => 3_500]),
            $this->usd('Zendesk Suite Team — 14 agentes'.$competence, 'Zendesk', $month, 20, 5_500 * 14, ['rec-atendimento' => 7_000, 'bh-operacoes' => 3_000]),
            // Rateio igual entre as três empresas: 33,34% + 33,33% + 33,33% fecha os centavos
            $this->usd('Power BI Pro — 12 licenças'.$competence, 'Microsoft', $month, 9, 1_400 * 12, ['sp-matriz' => 3_334, 'bh-operacoes' => 3_333, 'cwb-sede' => 3_333]),

            // Custos fixos em real
            $this->brl('Aluguel — escritório Av. Paulista'.$competence, 'Paulista Imóveis Administração', $month, 5, $rent(3_240_000), ['sp-matriz' => 10_000]),
            $this->brl('Aluguel — sala comercial em Botafogo'.$competence, 'Botafogo Offices', $month, 5, $rent(1_180_000), ['rj-filial' => 10_000]),
            $this->brl('Coworking — plano de 22 posições'.$competence, 'Hub BH Coworking', $month, 10, 960_000, ['bh-operacoes' => 10_000]),
            $this->brl('Aluguel — sala em Boa Viagem'.$competence, 'Recife Empresarial', $month, 5, $rent(690_000), ['rec-atendimento' => 10_000]),
            $this->brl('Aluguel — sede no Batel'.$competence, 'Imobiliária Curitibana', $month, 5, $rent(845_000), ['cwb-sede' => 10_000]),
            $this->brl('Energia elétrica — Av. Paulista'.$competence, 'Enel Distribuição São Paulo', $month, 18, $this->vary($this->seasonal(380_000, $month), 700), ['sp-matriz' => 10_000]),
            $this->brl('Link de internet dedicado 500 Mbps'.$competence, 'Vivo Empresas', $month, 22, 189_000, ['sp-matriz' => 6_000, 'rj-filial' => 4_000]),
            $this->brl('Honorários contábeis'.$competence, 'Contábil Prisma Assessoria', $month, 10, 780_000, $this->byHeadcount($all, $headcount)),
            $this->brl('Assessoria jurídica — retainer mensal'.$competence, 'Lima & Andrade Advogados', $month, 15, 550_000, $this->byHeadcount($all, $headcount)),
            $this->brl('Limpeza e conservação'.$competence, 'Brilho Serviços de Limpeza', $month, 28, 435_000, ['sp-matriz' => 7_000, 'rj-filial' => 3_000]),
            $this->brl('Café, água e itens de copa'.$competence, 'Distribuidora Grão Nobre', $month, $this->random->getInt(11, 14), $this->vary(118_000, 1_500), $this->byHeadcount($all, $headcount)),
            $this->brl('Google Ads — campanha de captação de alunos'.$competence, 'Google Ads', $month, 25, $this->vary(1_150_000, 1_800), ['cwb-sede' => 7_000, 'rj-filial' => 3_000]),
        ];

        // Eventuais, cada uma num mês do período
        $occasional = match ($index) {
            1 => [$this->brl('Notebooks Dell Latitude 5450 — 12 unidades', 'Dell Computadores do Brasil', $month, 14, 8_628_000, ['sp-matriz' => 5_000, 'rj-filial' => 2_500, 'bh-operacoes' => 2_500])],
            2 => [$this->brl('Seguro patrimonial — apólice anual', 'Porto Seguro', $month, 7, 1_496_000, $this->byHeadcount($all, $headcount))],
            3 => [
                $this->usd('Adobe Creative Cloud — plano anual, 5 licenças', 'Adobe', $month, 16, 299_940, ['sp-matriz' => 6_000, 'cwb-sede' => 4_000]),
                $this->brl('Evento de integração semestral', 'Espaço Villa Lobos Eventos', $month, 23, 3_850_000, $this->byHeadcount($all, $headcount)),
            ],
            4 => [$this->brl('Treinamento de liderança — 18 gestores', 'Escola de Gestão Horizonte', $month, 19, 1_680_000, $this->byHeadcount($all, $headcount))],
            5 => [$this->brl('Manutenção preventiva do ar-condicionado', 'Clima Frio Engenharia', $month, 6, 342_000, ['sp-matriz' => 6_500, 'rj-filial' => 3_500])],
            default => [],
        };

        return [...$expenses, ...$occasional];
    }

    /**
     * Funcionários por unidade no mês, crescendo em linha reta do primeiro ao último mês.
     *
     * @return array<string, int>
     */
    private function headcount(int $index): array
    {
        $headcount = [];

        foreach (self::GROUP as $units) {
            foreach ($units as $slug => $unit) {
                [$first, $last] = $unit['headcount'];
                $headcount[$slug] = $first + intdiv(($last - $first) * $index, self::MONTHS - 1);
            }
        }

        return $headcount;
    }

    /**
     * Percentuais proporcionais ao número de funcionários, em pontos-base somando exatamente 10000
     * (maior resto, como o AllocationSplitter faz com os centavos).
     *
     * @param  list<string>  $slugs
     * @param  array<string, int>  $headcount
     * @return array<string, int>
     */
    private function byHeadcount(array $slugs, array $headcount): array
    {
        $total = array_sum(array_map(fn (string $slug): int => $headcount[$slug], $slugs));
        $points = [];
        $remainders = [];

        foreach ($slugs as $slug) {
            $points[$slug] = intdiv($headcount[$slug] * 10_000, $total);
            $remainders[$slug] = ($headcount[$slug] * 10_000) % $total;
        }

        arsort($remainders);

        foreach (array_slice(array_keys($remainders), 0, 10_000 - array_sum($points)) as $slug) {
            $points[$slug]++;
        }

        return $points;
    }

    // Variação de até ±$maxBasisPoints (em pontos-base) sobre o valor, só com inteiros
    private function vary(int $cents, int $maxBasisPoints): int
    {
        return intdiv($cents * (10_000 + $this->random->getInt(-$maxBasisPoints, $maxBasisPoints)), 10_000);
    }

    // Conta de energia mais alta no verão (dezembro a março) e mais baixa no inverno (junho a agosto)
    private function seasonal(int $cents, CarbonImmutable $month): int
    {
        return match (true) {
            in_array($month->month, [12, 1, 2, 3], true) => intdiv($cents * 118, 100),
            in_array($month->month, [6, 7, 8], true) => intdiv($cents * 88, 100),
            default => $cents,
        };
    }

    /**
     * @param  array<string, int>  $allocation  slug => pontos-base
     */
    private function usd(string $description, string $supplier, CarbonImmutable $month, int $day, int $cents, array $allocation): ExpenseData
    {
        return $this->expense($description, $supplier, $month, $day, $cents, Currency::USD, $allocation);
    }

    /**
     * @param  array<string, int>  $allocation  slug => pontos-base
     */
    private function brl(string $description, string $supplier, CarbonImmutable $month, int $day, int $cents, array $allocation): ExpenseData
    {
        return $this->expense($description, $supplier, $month, $day, $cents, Currency::BRL, $allocation);
    }

    /**
     * @param  array<string, int>  $allocation  slug => pontos-base
     */
    private function expense(string $description, string $supplier, CarbonImmutable $month, int $day, int $cents, Currency $currency, array $allocation): ExpenseData
    {
        return new ExpenseData(
            description: $description,
            supplier: $supplier,
            date: $month->setDay(min($day, $month->daysInMonth)),
            amountCents: $cents,
            currency: $currency,
            allocations: array_map(
                fn (string $slug, int $basisPoints): AllocationData => new AllocationData($this->unitIds[$slug], $basisPoints),
                array_keys($allocation),
                $allocation,
            ),
        );
    }
}
