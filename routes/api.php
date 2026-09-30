<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\UnitController;
use Illuminate\Support\Facades\Route;

// Nomes com prefixo api.: os nomes sem prefixo ficam para as rotas da interface (web.php)
Route::name('api.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    // Todas as demais rotas exigem o token do /api/login (Authorization: Bearer)
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        Route::apiResource('companies', CompanyController::class);
        Route::apiResource('units', UnitController::class);
        Route::post('expenses/import', [ExpenseController::class, 'import'])->name('expenses.import');
        Route::apiResource('expenses', ExpenseController::class)->only(['index', 'store', 'show']);
        Route::post('expenses/{expense}/retry-conversion', [ExpenseController::class, 'retryConversion'])->name('expenses.retry-conversion');
        Route::get('reports/unit-totals', [ReportController::class, 'unitTotals'])->name('reports.unit-totals');
    });
});
