<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

// Testes de Feature sobem a aplicação Laravel e rodam cada teste numa transação revertida ao final.
// Testes de Unit não usam o framework: cobrem apenas lógica pura.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// Rotas da API exigem token: os testes de regra entram autenticados. A autenticação em si é testada em Feature/Auth
pest()->beforeEach(fn () => Sanctum::actingAs(User::factory()->create()))
    ->in('Feature/Api');

// Simula a API PTAX do Banco Central respondendo com uma cotação de venda do dia informado
function fakePtax(float $rate, string $quotedOn): void
{
    Http::fake([
        'olinda.bcb.gov.br/*' => Http::response(['value' => [
            ['cotacaoCompra' => $rate - 0.0006, 'cotacaoVenda' => $rate, 'dataHoraCotacao' => "{$quotedOn} 13:05:12.345"],
        ]]),
    ]);
}

// Simula a API PTAX fora do ar
function fakePtaxDown(): void
{
    Http::fake(['olinda.bcb.gov.br/*' => Http::response('Service Unavailable', 503)]);
}
