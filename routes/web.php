<?php

declare(strict_types=1);

use App\Livewire\Companies\CompaniesPage;
use App\Livewire\Reports\UnitTotalsPage;
use App\Livewire\Units\UnitsPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reports');

Route::get('/reports', UnitTotalsPage::class)->name('reports.index');

Route::get('/units', UnitsPage::class)->name('units.index');
Route::get('/companies', CompaniesPage::class)->name('companies.index');

// Catálogo de componentes de interface: só existe em ambiente local
if (app()->environment('local')) {
    Route::view('/ui', 'dev.ui-catalog')->name('dev.ui');
}
