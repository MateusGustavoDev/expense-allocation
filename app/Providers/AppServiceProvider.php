<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\ExchangeRateProvider;
use App\Models\User;
use App\Services\ExchangeRates\BcbPtaxProvider;
use Illuminate\Contracts\Config\Repository;
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
        // Trocar o provedor de câmbio é trocar este binding: o resto da aplicação depende só do contrato
        $this->app->bind(ExchangeRateProvider::class, fn ($app): BcbPtaxProvider => new BcbPtaxProvider(
            baseUrl: $app->make(Repository::class)->string('services.ptax.base_url'),
            timeoutSeconds: $app->make(Repository::class)->integer('services.ptax.timeout'),
        ));
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
