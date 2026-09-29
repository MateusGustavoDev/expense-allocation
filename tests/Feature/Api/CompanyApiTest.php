<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Unit;

it('lists companies ordered by name with their units count', function () {
    $beta = Company::factory()->create(['name' => 'Beta']);
    Company::factory()->create(['name' => 'Alpha']);
    Unit::factory()->count(2)->for($beta)->create();

    $this->getJson('/api/companies')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha')
        ->assertJsonPath('data.1.name', 'Beta')
        ->assertJsonPath('data.1.units_count', 2)
        ->assertJsonStructure(['data', 'links', 'meta']);
});

it('creates a company', function () {
    $this->postJson('/api/companies', ['name' => 'Acme Holding'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Acme Holding');

    expect(Company::where('name', 'Acme Holding')->exists())->toBeTrue();
});

it('requires a company name', function () {
    $this->postJson('/api/companies', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('rejects a duplicated company name', function () {
    Company::factory()->create(['name' => 'Acme Holding']);

    $this->postJson('/api/companies', ['name' => 'Acme Holding'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('updates a company keeping its own name', function () {
    $company = Company::factory()->create(['name' => 'Acme Holding']);

    $this->putJson("/api/companies/{$company->id}", ['name' => 'Acme Holding'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme Holding');
});

it('deletes a company without units', function () {
    $company = Company::factory()->create();

    $this->deleteJson("/api/companies/{$company->id}")->assertNoContent();

    expect(Company::find($company->id))->toBeNull();
});

it('refuses to delete a company that has units', function () {
    $company = Company::factory()->has(Unit::factory())->create();

    $this->deleteJson("/api/companies/{$company->id}")
        ->assertConflict()
        ->assertJsonPath('message', 'Não é possível excluir uma empresa com unidades cadastradas.');

    expect(Company::find($company->id))->not->toBeNull();
});

it('returns not found for an unknown company', function () {
    $this->getJson('/api/companies/999')->assertNotFound();
});
