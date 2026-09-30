<?php

declare(strict_types=1);

use App\Actions\Expenses\ImportExpensesFromCsv;
use App\Data\ImportReport;
use App\Enums\ConversionStatus;
use App\Exceptions\InvalidCsvFileException;
use App\Jobs\ConvertExpenseCurrencyJob;
use App\Models\Expense;
use App\Models\Unit;
use Illuminate\Support\Facades\Queue;

const CSV_HEADER = 'data;descricao;fornecedor;valor;moeda;rateio';

beforeEach(function () {
    Queue::fake();

    foreach (['unidade-a', 'unidade-b', 'unidade-c'] as $slug) {
        Unit::factory()->create(['slug' => $slug]);
    }
});

function importCsv(string ...$lines): ImportReport
{
    $path = tempnam(sys_get_temp_dir(), 'csv');
    file_put_contents($path, implode("\n", $lines)."\n");

    return app(ImportExpensesFromCsv::class)->execute($path);
}

it('imports the valid rows and reports the invalid ones by line number', function () {
    $report = importCsv(
        CSV_HEADER,
        '2026-09-01;Licença CRM;Fornecedor X;1500.00;USD;unidade-a:50|unidade-b:30|unidade-c:20',
        '2026-09-31;Licença Figma;Figma Inc.;180.00;USD;unidade-a:100',
        '2026-09-05;Café;Padaria;100.00;BRL;unidade-a:33.34|unidade-b:33.33|unidade-c:33.33',
        '2026-09-12;Coworking;Espaço Hub;900.00;BRL;unidade-a:50|unidade-b:40',
        '2026-09-20;Frete;Correios;85.40;BRL;unidade-x:100',
    );

    expect($report->totalRows)->toBe(5)
        ->and($report->importedCount)->toBe(2)
        ->and($report->failedCount())->toBe(3)
        ->and(array_map(fn ($error) => [$error->line, $error->messages], $report->errors))->toBe([
            [3, ['A data deve estar no formato AAAA-MM-DD e ser uma data válida.']],
            [5, ['A soma dos percentuais do rateio precisa ser exatamente 100%.']],
            [6, ['Unidade "unidade-x" não encontrada.']],
        ])
        ->and($report->errors[0]->content)->toBe('2026-09-31;Licença Figma;Figma Inc.;180.00;USD;unidade-a:100');

    expect(Expense::count())->toBe(2);
});

it('creates imported expenses exactly like the api, splitting the cents', function () {
    importCsv(CSV_HEADER, '2026-09-05;Café;Padaria;100.00;BRL;unidade-a:33.34|unidade-b:33.33|unidade-c:33.33');

    $expense = Expense::sole();
    expect($expense->conversion_status)->toBe(ConversionStatus::Converted)
        ->and($expense->allocations->pluck('amount_cents')->all())->toBe([3334, 3333, 3333]);
});

it('queues the currency conversion of imported USD expenses', function () {
    importCsv(CSV_HEADER, '2026-09-01;Licença CRM;Fornecedor X;1500.00;USD;unidade-a:100');

    Queue::assertPushed(ConvertExpenseCurrencyJob::class, 1);
});

it('accepts lowercase currency and slugs, and collapses repeated whitespace', function () {
    $report = importCsv(CSV_HEADER, '2026-09-05;Licença    CRM;Fornecedor X;10.00;brl;Unidade-A:100');

    expect($report->importedCount)->toBe(1)
        ->and(Expense::sole()->description)->toBe('Licença CRM');
});

it('reports a row with the wrong number of columns', function () {
    $report = importCsv(CSV_HEADER, '2026-09-05;Café;100.00;BRL;unidade-a:100');

    expect($report->errors[0]->messages)->toBe(['A linha deve ter 6 colunas separadas por ponto e vírgula (encontradas: 5).']);
});

it('reports a malformed allocation', function () {
    $report = importCsv(CSV_HEADER, '2026-09-05;Café;Padaria;100.00;BRL;unidade-a=100');

    expect($report->errors[0]->messages)->toBe(['Trecho de rateio inválido: "unidade-a=100". Use slug:percentual.']);
});

it('reports a unit repeated in the allocation', function () {
    $report = importCsv(CSV_HEADER, '2026-09-05;Café;Padaria;100.00;BRL;unidade-a:50|unidade-a:50');

    expect($report->errors[0]->messages)->toContain('A unidade aparece mais de uma vez no rateio.');
});

it('reports every invalid field of a row', function () {
    $report = importCsv(CSV_HEADER, '2026-09-05;;Padaria;10,50;EUR;unidade-a:100');

    expect($report->errors[0]->messages)->toBe([
        'O campo descrição é obrigatório.',
        'O valor deve usar ponto como separador decimal e ter até 2 casas, ex.: 1500.00.',
        'O campo moeda selecionado é inválido.',
    ]);
});

it('ignores blank lines and keeps the original line numbers', function () {
    $report = importCsv(CSV_HEADER, '', '2026-09-05;Café;Padaria;abc;BRL;unidade-a:100');

    expect($report->totalRows)->toBe(1)
        ->and($report->errors[0]->line)->toBe(3);
});

it('rejects a file with an unexpected header', function () {
    importCsv('date;description;supplier;amount;currency;allocation', '2026-09-05;Café;Padaria;10.00;BRL;unidade-a:100');
})->throws(InvalidCsvFileException::class, 'Cabeçalho inválido. A primeira linha deve ser: data;descricao;fornecedor;valor;moeda;rateio.');

it('rejects an empty file', function () {
    importCsv('');
})->throws(InvalidCsvFileException::class, 'O arquivo está vazio.');
