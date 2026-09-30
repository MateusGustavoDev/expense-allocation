<?php

declare(strict_types=1);

use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
    Unit::factory()->create(['slug' => 'unidade-a']);
});

function csvUpload(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('despesas.csv', $content);
}

it('imports a csv file and returns the report', function () {
    $csv = "data;descricao;fornecedor;valor;moeda;rateio\n"
        ."2026-09-05;Café;Padaria;100.00;BRL;unidade-a:100\n"
        ."2026-09-06;Água;Mercado;abc;BRL;unidade-a:100\n";

    $this->post('/api/expenses/import', ['file' => csvUpload($csv)], ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('data.total_rows', 2)
        ->assertJsonPath('data.imported', 1)
        ->assertJsonPath('data.failed', 1)
        ->assertJsonPath('data.errors.0.line', 3)
        ->assertJsonPath('data.errors.0.content', '2026-09-06;Água;Mercado;abc;BRL;unidade-a:100')
        ->assertJsonPath('data.errors.0.messages.0', 'O valor deve usar ponto como separador decimal e ter até 2 casas, ex.: 1500.00.');
});

it('requires a file', function () {
    $this->postJson('/api/expenses/import')
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects files that are not csv', function () {
    $this->post('/api/expenses/import', ['file' => UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('rejects a file with an unexpected header', function () {
    $this->post('/api/expenses/import', ['file' => csvUpload("a;b;c\n1;2;3\n")], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'Cabeçalho inválido. A primeira linha deve ser: data;descricao;fornecedor;valor;moeda;rateio.');
});
