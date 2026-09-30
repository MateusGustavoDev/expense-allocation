<?php

declare(strict_types=1);

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\EnsureValidJsonBody;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [EnsureValidJsonBody::class]);

        // O Railway termina o HTTPS no proxy dele: confiar nos cabeçalhos X-Forwarded-* faz o Laravel gerar URLs
        // https (assets, Livewire, redirects). Sem isso o navegador bloqueia os assets por mixed content
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Mensagens em português para os erros gerados pelo framework na API (404, 405, 413, 500...)
        $exceptions->render(new ApiExceptionRenderer);
    })->create();
