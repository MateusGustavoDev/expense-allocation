<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Unit;

it('creates a unit deriving the slug from its name', function () {
    $company = Company::factory()->create();

    $this->postJson('/api/units', ['company_id' => $company->id, 'name' => 'Unidade São Paulo'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'unidade-sao-paulo')
        ->assertJsonPath('data.company.id', $company->id);
});

it('normalizes an explicit slug', function () {
    $company = Company::factory()->create();

    $this->postJson('/api/units', ['company_id' => $company->id, 'name' => 'Matriz', 'slug' => 'Unidade A'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'unidade-a');
});

it('validates required fields', function () {
    $this->postJson('/api/units', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['company_id', 'name']);
});

it('rejects an unknown company', function () {
    $this->postJson('/api/units', ['company_id' => 999, 'name' => 'Matriz'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('company_id');
});

it('rejects a duplicated slug', function () {
    Unit::factory()->create(['slug' => 'unidade-a']);

    $this->postJson('/api/units', ['company_id' => Company::factory()->create()->id, 'name' => 'Unidade A'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

it('rejects a duplicated name within the same company', function () {
    $unit = Unit::factory()->create(['name' => 'Matriz']);

    $this->postJson('/api/units', ['company_id' => $unit->company_id, 'name' => 'Matriz', 'slug' => 'outra-matriz'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});

it('allows the same name in different companies', function () {
    Unit::factory()->create(['name' => 'Matriz', 'slug' => 'matriz-acme']);

    $this->postJson('/api/units', ['company_id' => Company::factory()->create()->id, 'name' => 'Matriz', 'slug' => 'matriz-globex'])
        ->assertCreated();
});

it('filters units by company', function () {
    $company = Company::factory()->create();
    Unit::factory()->count(2)->for($company)->create();
    Unit::factory()->create();

    $this->getJson("/api/units?company_id={$company->id}")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('updates a unit keeping its own slug', function () {
    $unit = Unit::factory()->create(['slug' => 'unidade-a']);

    $this->putJson("/api/units/{$unit->id}", ['company_id' => $unit->company_id, 'name' => 'Novo nome', 'slug' => 'unidade-a'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Novo nome')
        ->assertJsonPath('data.slug', 'unidade-a');
});

it('deletes a unit', function () {
    $unit = Unit::factory()->create();

    $this->deleteJson("/api/units/{$unit->id}")->assertNoContent();

    expect(Unit::find($unit->id))->toBeNull();
});
