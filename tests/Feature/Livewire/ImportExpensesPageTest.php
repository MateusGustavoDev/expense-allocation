<?php

declare(strict_types=1);

use App\Livewire\Expenses\ImportExpensesPage;
use App\Models\Expense;
use App\Models\Unit;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

function csvWithRows(string ...$rows): UploadedFile
{
    return UploadedFile::fake()->createWithContent('despesas.csv', implode("\n", ['data;descricao;fornecedor;valor;moeda;rateio', ...$rows]));
}

it('renders the import page', function () {
    $this->get('/expenses/import')->assertOk()->assertSeeLivewire(ImportExpensesPage::class);
});

it('offers the example file for download', function () {
    $this->get('/expenses/import/example')->assertOk()->assertDownload('exemplo-despesas.csv');
});

it('imports the valid rows and reports the invalid ones by line number', function () {
    Unit::factory()->create(['slug' => 'unidade-a']);

    Livewire::test(ImportExpensesPage::class)
        ->set('file', csvWithRows(
            '2026-09-01;Aluguel;Imobiliária Y;1000.00;BRL;unidade-a:100',
            '2026-09-02;Licença;Fornecedor X;500.00;BRL;unidade-z:100',
        ))
        ->call('import')
        ->assertHasNoErrors()
        ->assertSet('report.total_rows', 2)
        ->assertSet('report.imported', 1)
        ->assertSet('report.failed', 1)
        ->assertSet('report.errors.0.line', 3)
        ->assertSet('file', null)
        ->assertDispatched('toast', type: 'warning', message: '1 de 2 linhas importadas.');

    expect(Expense::sole()->description)->toBe('Aluguel');
});

it('refuses a file with an unexpected header', function () {
    Livewire::test(ImportExpensesPage::class)
        ->set('file', UploadedFile::fake()->createWithContent('despesas.csv', "date,description\n2026-09-01,Aluguel"))
        ->call('import')
        ->assertHasErrors('file')
        ->assertSet('report', null);
});

it('requires a csv file', function () {
    Livewire::test(ImportExpensesPage::class)
        ->set('file', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
        ->call('import')
        ->assertHasErrors(['file' => 'extensions']);
});
