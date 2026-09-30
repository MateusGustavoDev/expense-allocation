<?php

declare(strict_types=1);

namespace App\Livewire\Expenses;

use App\Actions\Expenses\RequeueExpenseConversion;
use App\Enums\ConversionStatus;
use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * Lista de despesas com busca, filtros, ordenação e paginação — todos na URL.
 * Usa o mesmo scope Expense::filter() da API (GET /api/expenses).
 */
#[Title('Despesas')]
final class ExpensesPage extends Component
{
    use WithPagination;

    // Filtro extra da interface: pendentes e falhas juntas (despesas ainda sem valor em BRL)
    public const STATUS_UNCONVERTED = 'unconverted';

    private const SORTABLE = ['date' => 'date', 'amount_brl' => 'amount_brl_cents'];

    #[Url(except: '')]
    public string $search = '';

    /** @var array{from: string|null, to: string|null} */
    #[Url]
    public array $period = ['from' => null, 'to' => null];

    #[Url(except: '')]
    public string $currency = '';

    #[Url(except: '')]
    public string $status = '';

    // Não pode se chamar $sortBy: no navegador, $wire.sortBy devolveria a propriedade e esconderia o método sortBy()
    #[Url(as: 'sort', except: 'date')]
    public string $sortColumn = 'date';

    #[Url(as: 'direction', except: 'desc')]
    public string $sortDirection = 'desc';

    // Qualquer filtro novo volta para a primeira página
    public function updated(string $property): void
    {
        if (in_array(explode('.', $property)[0], ['search', 'period', 'currency', 'status'], true)) {
            $this->resetPage();
        }
    }

    public function sortBy(string $column): void
    {
        if (! array_key_exists($column, self::SORTABLE)) {
            return;
        }

        $this->sortDirection = $this->sortColumn === $column && $this->sortDirection === 'desc' ? 'asc' : 'desc';
        $this->sortColumn = $column;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset('search', 'period', 'currency', 'status');
        $this->resetPage();
    }

    public function retryConversion(int $expenseId, RequeueExpenseConversion $requeue): void
    {
        try {
            $requeue->execute(Expense::query()->findOrFail($expenseId));
            $this->dispatch('toast', type: 'success', message: 'Conversão enviada de novo para a fila.');
        } catch (ConflictHttpException $exception) {
            $this->dispatch('toast', type: 'warning', message: $exception->getMessage());
        }
    }

    public function hasFilters(): bool
    {
        return $this->search !== '' || $this->currency !== '' || $this->status !== '' || ($this->period['from'] ?? null) !== null;
    }

    /**
     * @return LengthAwarePaginator<int, Expense>
     */
    #[Computed]
    public function expenses(): LengthAwarePaginator
    {
        $column = self::SORTABLE[$this->sortColumn] ?? 'date';

        return Expense::query()
            ->withCount('allocations')
            ->filter([
                'search' => trim($this->search) ?: null,
                'date_from' => $this->validDate($this->period['from'] ?? null),
                'date_to' => $this->validDate($this->period['to'] ?? null),
                'currency' => $this->currency ?: null,
                'conversion_status' => $this->statusFilter(),
            ])
            ->orderBy($column, $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(15);
    }

    public function render(): View
    {
        return view('livewire.expenses.expenses-page');
    }

    /**
     * @return list<string>|null
     */
    private function statusFilter(): ?array
    {
        return match (true) {
            $this->status === self::STATUS_UNCONVERTED => [ConversionStatus::Pending->value, ConversionStatus::Failed->value],
            ConversionStatus::tryFrom($this->status) !== null => [$this->status],
            default => null,
        };
    }

    // Valores da URL são entrada do usuário: data fora do formato é ignorada em vez de quebrar a consulta
    private function validDate(?string $date): ?string
    {
        return $date !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : null;
    }
}
