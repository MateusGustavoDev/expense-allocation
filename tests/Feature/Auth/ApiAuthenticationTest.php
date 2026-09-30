<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

function apiUser(): User
{
    return User::factory()->create(['email' => 'ana@example.com', 'password' => 'senha-segura']);
}

it('issues a token for valid credentials', function () {
    $user = apiUser();

    $response = $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'senha-segura', 'device_name' => 'Postman'])
        ->assertOk()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.email', 'ana@example.com')
        ->assertJsonMissingPath('user.password');

    expect($response->json('token'))->toBeString()->not->toBeEmpty()
        ->and($user->tokens()->sole()->name)->toBe('Postman');
});

it('rejects invalid credentials without telling which field is wrong', function (string $email, string $password) {
    // Debug desligado, como em produção: credencial errada é 422, não erro interno
    config(['app.debug' => false]);
    apiUser();

    $this->postJson('/api/login', ['email' => $email, 'password' => $password])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', 'Essas credenciais não foram encontradas em nossos registros.')
        ->assertJsonMissingPath('errors.password');
})->with([
    'senha errada' => ['ana@example.com', 'errada'],
    'e-mail inexistente' => ['ninguem@example.com', 'senha-segura'],
]);

it('limits login attempts per email and ip', function () {
    apiUser();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'errada'])->assertUnprocessable();
    }

    $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'senha-segura'])
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('message', fn (string $message): bool => str_starts_with($message, 'Muitas tentativas de login.'));
});

it('clears the attempts after a successful login', function () {
    apiUser();
    $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'errada']);

    $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'senha-segura'])->assertOk();

    expect(RateLimiter::attempts('ana@example.com|127.0.0.1'))->toBe(0);
});

it('accepts the token on protected routes', function () {
    apiUser();
    $token = $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'senha-segura'])->json('token');

    $this->getJson('/api/me', ['Authorization' => "Bearer {$token}"])
        ->assertOk()
        ->assertJsonPath('data.email', 'ana@example.com');
});

it('revokes only the token used to log out', function () {
    $user = apiUser();
    $kept = $user->createToken('celular')->plainTextToken;
    $revoked = $user->createToken('postman')->plainTextToken;

    $this->postJson('/api/logout', [], ['Authorization' => "Bearer {$revoked}"])->assertNoContent();

    expect(PersonalAccessToken::findToken($revoked))->toBeNull()
        ->and(PersonalAccessToken::findToken($kept))->not->toBeNull();
});

it('rejects an expired token', function () {
    $token = apiUser()->createToken('api')->plainTextToken;

    $this->travel(config('sanctum.expiration') + 1)->minutes();

    $this->getJson('/api/me', ['Authorization' => "Bearer {$token}"])->assertUnauthorized();
});

it('requires a token on every api route except login', function () {
    // Lida da tabela de rotas: uma rota nova sem auth:sanctum quebra este teste
    $routes = collect(Route::getRoutes()->getRoutes())
        ->filter(fn (RoutingRoute $route): bool => str_starts_with($route->uri(), 'api/') && $route->getName() !== 'api.login');

    expect($routes)->not->toBeEmpty();

    $unprotected = $routes
        ->map(fn (RoutingRoute $route): string => $route->methods()[0].' /'.preg_replace('/\{[^}]+\}/', '1', $route->uri()))
        ->reject(function (string $endpoint): bool {
            [$method, $uri] = explode(' ', $endpoint);
            $response = $this->json($method, $uri);

            return $response->status() === 401 && $response->json() === ['message' => 'Não autenticado.'];
        })
        ->values()
        ->all();

    expect($unprotected)->toBe([], 'Rotas da API sem autenticação: '.implode(', ', $unprotected));
});
