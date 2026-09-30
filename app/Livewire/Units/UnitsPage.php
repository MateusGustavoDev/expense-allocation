<?php

declare(strict_types=1);

namespace App\Livewire\Units;

use App\Actions\Units\DeleteUnit;
use App\Exceptions\ResourceInUseException;
use App\Livewire\Forms\UnitForm;
use App\Models\Company;
use App\Models\Unit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cadastro de unidades: o slug mostrado aqui é o identificador usado na importação por CSV.
 */
#[Title('Unidades')]
final class UnitsPage extends Component
{
    public UnitForm $form;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    /**
     * @return Collection<int, Unit>
     */
    #[Computed]
    public function units(): Collection
    {
        return Unit::query()->with('company')->withCount('allocations')->orderBy('name')->get();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function companyOptions(): array
    {
        return Company::query()->orderBy('name')->pluck('name', 'id')->all();
    }

    public function create(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->editingId = null;
        $this->dispatch('open-modal', name: 'unit-form');
    }

    public function edit(int $unitId): void
    {
        $this->resetValidation();
        $this->form->fillFrom(Unit::query()->findOrFail($unitId));
        $this->editingId = $unitId;
        $this->dispatch('open-modal', name: 'unit-form');
    }

    public function save(): void
    {
        $this->form->save($this->editingId ? Unit::query()->findOrFail($this->editingId) : null);

        $this->dispatch('close-modal', name: 'unit-form');
        $this->dispatch('toast', type: 'success', message: $this->editingId ? 'Unidade atualizada.' : 'Unidade cadastrada.');
        $this->editingId = null;
        unset($this->units);
    }

    public function confirmDelete(int $unitId): void
    {
        $this->deletingId = $unitId;
        $this->dispatch('open-modal', name: 'delete-unit');
    }

    public function delete(DeleteUnit $deleteUnit): void
    {
        try {
            $deleteUnit->execute(Unit::query()->findOrFail($this->deletingId));
            $this->dispatch('toast', type: 'success', message: 'Unidade excluída.');
        } catch (ResourceInUseException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
        }

        $this->dispatch('close-modal', name: 'delete-unit');
        $this->deletingId = null;
        unset($this->units);
    }

    public function render(): View
    {
        return view('livewire.units.units-page');
    }
}
