<?php

declare(strict_types=1);

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;

Route::apiResource('companies', CompanyController::class);
Route::apiResource('units', UnitController::class);
Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'show']);
Route::post('expenses/{expense}/retry-conversion', [ExpenseController::class, 'retryConversion'])->name('expenses.retry-conversion');
