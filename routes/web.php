<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Catálogo de componentes de interface: só existe em ambiente local
if (app()->environment('local')) {
    Route::view('/ui', 'dev.ui-catalog')->name('dev.ui');
}
