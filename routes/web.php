<?php

declare(strict_types=1);

use App\Livewire\Companies\CompaniesPage;
use App\Livewire\Expenses\CreateExpensePage;
use App\Livewire\Expenses\ExpenseShowPage;
use App\Livewire\Expenses\ExpensesPage;
use App\Livewire\Expenses\ImportExpensesPage;
use App\Livewire\Reports\UnitTotalsPage;
use App\Livewire\Units\UnitsPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reports');

Route::get('/reports', UnitTotalsPage::class)->name('reports.index');

Route::get('/expenses', ExpensesPage::class)->name('expenses.index');
Route::get('/expenses/create', CreateExpensePage::class)->name('expenses.create');
Route::get('/expenses/import', ImportExpensesPage::class)->name('expenses.import');
Route::get('/expenses/import/example', fn () => response()->download(base_path('docs/examples/despesas-setembro.csv'), 'exemplo-despesas.csv'))
    ->name('expenses.import.example');
Route::get('/expenses/{expense}', ExpenseShowPage::class)->name('expenses.show');

Route::get('/units', UnitsPage::class)->name('units.index');
Route::get('/companies', CompaniesPage::class)->name('companies.index');

// Catálogo de componentes de interface: só existe em ambiente local
if (app()->environment('local')) {
    Route::view('/ui', 'dev.ui-catalog')->name('dev.ui');
}
