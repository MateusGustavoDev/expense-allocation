<?php

declare(strict_types=1);

use App\Livewire\Companies\CompaniesPage;
use App\Models\Company;
use App\Models\Unit;
use Livewire\Livewire;

it('lists companies with their units count', function () {
    Unit::factory()->count(2)->for(Company::factory()->create(['name' => 'Acme Holding']))->create();

    $this->get('/companies')->assertOk()->assertSeeLivewire(CompaniesPage::class);

    Livewire::test(CompaniesPage::class)->assertSeeInOrder(['Acme Holding', '2']);
});

it('creates a company from the modal', function () {
    Livewire::test(CompaniesPage::class)
        ->call('create')
        ->assertDispatched('open-modal', name: 'company-form')
        ->set('form.name', '  Grupo    Exemplo ')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('close-modal', name: 'company-form')
        ->assertDispatched('toast', type: 'success', message: 'Empresa cadastrada.');

    expect(Company::sole()->name)->toBe('Grupo Exemplo');
});

it('validates the company name with the api rules', function () {
    Company::factory()->create(['name' => 'Acme Holding']);

    Livewire::test(CompaniesPage::class)
        ->set('form.name', 'ACME HOLDING')
        ->call('save')
        ->assertHasErrors(['form.name' => 'unique']);
});

it('edits a company', function () {
    $company = Company::factory()->create(['name' => 'Acme']);

    Livewire::test(CompaniesPage::class)
        ->call('edit', $company->id)
        ->assertSet('form.name', 'Acme')
        ->set('form.name', 'Acme Holding')
        ->call('save')
        ->assertHasNoErrors();

    expect($company->refresh()->name)->toBe('Acme Holding');
});

it('deletes a company without units', function () {
    $company = Company::factory()->create();

    Livewire::test(CompaniesPage::class)
        ->call('confirmDelete', $company->id)
        ->call('delete')
        ->assertDispatched('toast', type: 'success', message: 'Empresa excluída.');

    expect(Company::count())->toBe(0);
});

it('explains why a company with units cannot be deleted', function () {
    $company = Company::factory()->has(Unit::factory())->create();

    Livewire::test(CompaniesPage::class)
        ->call('confirmDelete', $company->id)
        ->call('delete')
        ->assertDispatched('toast', type: 'error', message: 'Não é possível excluir uma empresa com unidades cadastradas.');

    expect(Company::count())->toBe(1);
});
