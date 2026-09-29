<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Fora de produção, lazy loading (N+1), atributos descartados e colunas não carregadas viram exceção
        Model::shouldBeStrict(! $this->app->isProduction());

        // Documentação da API (/docs/api) pública em todos os ambientes: não expõe dados, só o contrato
        Gate::define('viewApiDocs', fn (?User $user = null): bool => true);
    }
}
