<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\ExpenseAllocation;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Route;

// Contrato de erro da API: sempre JSON com "message" em português, sem detalhes internos

it('names the missing record instead of the model class', function (string $method, string $uri, string $message) {
    $this->json($method, $uri)
        ->assertNotFound()
        ->assertExactJson(['message' => $message]);
})->with([
    'empresa' => ['GET', '/api/companies/999', 'Empresa não encontrada.'],
    'editar empresa' => ['PUT', '/api/companies/999', 'Empresa não encontrada.'],
    'excluir empresa' => ['DELETE', '/api/companies/999', 'Empresa não encontrada.'],
    'id não numérico' => ['GET', '/api/companies/abc', 'Empresa não encontrada.'],
    'unidade' => ['GET', '/api/units/999', 'Unidade não encontrada.'],
    'editar unidade' => ['PATCH', '/api/units/999', 'Unidade não encontrada.'],
    'excluir unidade' => ['DELETE', '/api/units/999', 'Unidade não encontrada.'],
    'despesa' => ['GET', '/api/expenses/999', 'Despesa não encontrada.'],
    'reconverter despesa' => ['POST', '/api/expenses/999/retry-conversion', 'Despesa não encontrada.'],
]);

it('answers an unknown api route in portuguese', function () {
    $this->getJson('/api/nao-existe')
        ->assertNotFound()
        ->assertExactJson(['message' => 'Rota não encontrada.']);
});

it('lists the allowed methods when the method is not supported', function () {
    $expense = Expense::factory()->create();

    $this->deleteJson("/api/expenses/{$expense->id}")
        ->assertStatus(405)
        ->assertHeader('Allow', 'GET, HEAD')
        ->assertExactJson(['message' => 'Método DELETE não permitido nesta rota. Métodos aceitos: GET, HEAD.']);
});

it('rejects a malformed json body instead of reporting missing fields', function () {
    $this->call('POST', '/api/companies', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'], content: '{"name": ')
        ->assertBadRequest()
        ->assertExactJson(['message' => 'O corpo da requisição não é um JSON válido.']);
});

it('rejects a body larger than the server limit', function () {
    $this->call('POST', '/api/companies', server: ['CONTENT_LENGTH' => PHP_INT_MAX, 'HTTP_ACCEPT' => 'application/json'])
        ->assertStatus(413)
        ->assertExactJson(['message' => 'O corpo da requisição excede o tamanho máximo permitido.']);
});

it('hides unexpected errors in production', function () {
    config(['app.debug' => false]);
    Route::get('/api/_test/boom', fn () => throw new RuntimeException('detalhe interno'));

    $this->getJson('/api/_test/boom')
        ->assertServerError()
        ->assertExactJson(['message' => 'Erro interno do servidor.']);
});

it('keeps the trace of unexpected errors while debugging', function () {
    config(['app.debug' => true]);
    Route::get('/api/_test/boom', fn () => throw new RuntimeException('detalhe interno'));

    $this->getJson('/api/_test/boom')
        ->assertServerError()
        ->assertJsonPath('message', 'detalhe interno')
        ->assertJsonStructure(['exception', 'trace']);
});

it('translates authentication, authorization and rate limit errors', function (Throwable $exception, int $status, string $message) {
    Route::get('/api/_test/error', fn () => throw $exception);

    $this->getJson('/api/_test/error')
        ->assertStatus($status)
        ->assertExactJson(['message' => $message]);
})->with([
    'não autenticado' => [new AuthenticationException, 401, 'Não autenticado.'],
    'sem permissão' => [new AuthorizationException, 403, 'Você não tem permissão para esta ação.'],
    'limite de requisições' => [new ThrottleRequestsException, 429, 'Muitas requisições. Tente novamente em instantes.'],
]);

it('keeps the portuguese message of domain errors without the debug trace', function () {
    // Com debug ligado o Laravel anexaria exception, file, line e trace a qualquer erro HTTP
    config(['app.debug' => true]);
    $allocation = ExpenseAllocation::factory()->create();

    $this->deleteJson("/api/units/{$allocation->unit_id}")
        ->assertConflict()
        ->assertExactJson(['message' => 'Não é possível excluir uma unidade com despesas rateadas.']);

    $this->postJson("/api/expenses/{$allocation->expense_id}/retry-conversion")
        ->assertConflict()
        ->assertExactJson(['message' => 'A despesa já foi convertida.']);
});

it('keeps the validation errors per field', function () {
    $this->postJson('/api/units', [])
        ->assertUnprocessable()
        ->assertJsonPath('message', 'O campo empresa é obrigatório. (e mais 1 erro)')
        ->assertJsonPath('errors.name.0', 'O campo nome é obrigatório.');
});

it('does not change how web pages render errors', function () {
    $this->get('/expenses/999')->assertNotFound()->assertDontSee('Despesa não encontrada.');
});
