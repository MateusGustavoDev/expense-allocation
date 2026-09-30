<?php

declare(strict_types=1);

use App\Livewire\Units\UnitsPage;
use App\Models\Company;
use App\Models\ExpenseAllocation;
use App\Models\Unit;
use Livewire\Livewire;

it('lists units with their slug and company', function () {
    Unit::factory()->for(Company::factory()->create(['name' => 'Acme Holding']))->create(['name' => 'Unidade A', 'slug' => 'unidade-a']);

    $this->get('/units')->assertOk()->assertSeeLivewire(UnitsPage::class);

    Livewire::test(UnitsPage::class)->assertSeeInOrder(['Unidade A', 'unidade-a', 'Acme Holding']);
});

it('creates a unit deriving the slug from the name', function () {
    $company = Company::factory()->create();

    Livewire::test(UnitsPage::class)
        ->call('create')
        ->set('form.company_id', (string) $company->id)
        ->set('form.name', 'Unidade São Paulo')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success', message: 'Unidade cadastrada.');

    expect(Unit::sole()->slug)->toBe('unidade-sao-paulo');
});

it('validates the unit with the api rules', function () {
    Unit::factory()->create(['slug' => 'unidade-a']);

    Livewire::test(UnitsPage::class)
        ->set('form.company_id', '')
        ->set('form.name', 'Unidade A')
        ->call('save')
        ->assertHasErrors(['form.company_id' => 'required', 'form.slug' => 'unique']);
});

it('edits a unit keeping its slug', function () {
    $unit = Unit::factory()->create(['name' => 'Unidade A', 'slug' => 'unidade-a']);

    Livewire::test(UnitsPage::class)
        ->call('edit', $unit->id)
        ->assertSet('form.slug', 'unidade-a')
        ->set('form.name', 'Matriz')
        ->call('save')
        ->assertHasNoErrors();

    expect($unit->refresh())->name->toBe('Matriz')->slug->toBe('unidade-a');
});

it('explains why a unit with allocated expenses cannot be deleted', function () {
    $unit = ExpenseAllocation::factory()->create()->unit;

    Livewire::test(UnitsPage::class)
        ->call('confirmDelete', $unit->id)
        ->call('delete')
        ->assertDispatched('toast', type: 'error', message: 'Não é possível excluir uma unidade com despesas rateadas.');

    expect(Unit::find($unit->id))->not->toBeNull();
});

it('asks for a company before the first unit', function () {
    Livewire::test(UnitsPage::class)->assertSee('Cadastre uma empresa primeiro');
});
