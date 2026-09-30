<?php

declare(strict_types=1);

use App\Livewire\Reports\UnitTotalsPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/reports');

Route::get('/reports', UnitTotalsPage::class)->name('reports.index');

// Catálogo de componentes de interface: só existe em ambiente local
if (app()->environment('local')) {
    Route::view('/ui', 'dev.ui-catalog')->name('dev.ui');
}
