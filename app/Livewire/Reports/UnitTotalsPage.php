<?php

declare(strict_types=1);

namespace App\Livewire\Reports;

use App\Actions\Reports\GetUnitTotalsReport;
use App\Data\UnitTotalsReport;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Relatório de total em BRL por unidade num período. Usa a mesma Action da API (GET /api/reports/unit-totals).
 *
 * O período fica na URL (?period[from]=...&period[to]=...): o relatório filtrado pode ser compartilhado por link.
 */
#[Title('Relatório por unidade')]
final class UnitTotalsPage extends Component
{
    /** @var array{from: string|null, to: string|null} */
    #[Url]
    public array $period = ['from' => null, 'to' => null];

    public function mount(): void
    {
        // Período vindo da URL inválido ou ausente: começa no mês corrente
        if ($this->validator()->fails()) {
            $today = CarbonImmutable::now(config()->string('app.business_timezone'));
            $this->period = ['from' => $today->startOfMonth()->toDateString(), 'to' => $today->toDateString()];
        }
    }

    public function updatedPeriod(): void
    {
        $this->validate();
        unset($this->report);
    }

    /**
     * Relatório do período, ou null enquanto o período for inválido.
     */
    #[Computed]
    public function report(): ?UnitTotalsReport
    {
        if ($this->validator()->fails()) {
            return null;
        }

        return app(GetUnitTotalsReport::class)->execute(
            CarbonImmutable::parse((string) $this->period['from']),
            CarbonImmutable::parse((string) $this->period['to']),
        );
    }

    public function render(): View
    {
        return view('livewire.reports.unit-totals-page');
    }

    /**
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'period.from' => ['required', 'date_format:Y-m-d'],
            'period.to' => ['required', 'date_format:Y-m-d', 'after_or_equal:period.from'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'period.from' => 'data inicial',
            'period.to' => 'data final',
        ];
    }

    private function validator(): Validator
    {
        return validator(['period' => $this->period], $this->rules(), [], $this->validationAttributes());
    }
}
