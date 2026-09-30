<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Actions\Companies\DeleteCompany;
use App\Exceptions\ResourceInUseException;
use App\Livewire\Forms\CompanyForm;
use App\Models\Company;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Cadastro de empresas: lista com a quantidade de unidades, criação e edição em modal, exclusão com confirmação.
 */
#[Title('Empresas')]
final class CompaniesPage extends Component
{
    public CompanyForm $form;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    /**
     * @return Collection<int, Company>
     */
    #[Computed]
    public function companies(): Collection
    {
        return Company::query()->withCount('units')->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->form->reset();
        $this->resetValidation();
        $this->editingId = null;
        $this->dispatch('open-modal', name: 'company-form');
    }

    public function edit(int $companyId): void
    {
        $this->resetValidation();
        $this->form->fillFrom(Company::query()->findOrFail($companyId));
        $this->editingId = $companyId;
        $this->dispatch('open-modal', name: 'company-form');
    }

    public function save(): void
    {
        $this->form->save($this->editingId ? Company::query()->findOrFail($this->editingId) : null);

        $this->dispatch('close-modal', name: 'company-form');
        $this->dispatch('toast', type: 'success', message: $this->editingId ? 'Empresa atualizada.' : 'Empresa cadastrada.');
        $this->editingId = null;
        unset($this->companies);
    }

    public function confirmDelete(int $companyId): void
    {
        $this->deletingId = $companyId;
        $this->dispatch('open-modal', name: 'delete-company');
    }

    public function delete(DeleteCompany $deleteCompany): void
    {
        try {
            $deleteCompany->execute(Company::query()->findOrFail($this->deletingId));
            $this->dispatch('toast', type: 'success', message: 'Empresa excluída.');
        } catch (ResourceInUseException $exception) {
            $this->dispatch('toast', type: 'error', message: $exception->getMessage());
        }

        $this->dispatch('close-modal', name: 'delete-company');
        $this->deletingId = null;
        unset($this->companies);
    }

    public function render(): View
    {
        return view('livewire.companies.companies-page');
    }
}
