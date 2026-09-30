<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('creates a user from the options', function () {
    $this->artisan('users:create', ['--name' => 'Ana Souza', '--email' => 'ana@example.com', '--password' => 'senha-segura'])
        ->expectsOutput('Usuário ana@example.com criado.')
        ->assertSuccessful();

    $user = User::sole();
    expect($user->name)->toBe('Ana Souza')
        ->and(Hash::check('senha-segura', $user->password))->toBeTrue();
});

it('asks for what was not given, with the password hidden', function () {
    $this->artisan('users:create', ['--name' => 'Ana Souza'])
        ->expectsQuestion('E-mail', 'ana@example.com')
        ->expectsQuestion('Senha (mínimo de 8 caracteres)', 'senha-segura')
        ->assertSuccessful();

    expect(User::sole()->email)->toBe('ana@example.com');
});

it('refuses a duplicated email or a short password', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->artisan('users:create', ['--name' => 'Ana', '--email' => 'ana@example.com', '--password' => 'curta'])
        ->expectsOutput('O campo e-mail já está sendo utilizado.')
        ->expectsOutput('O campo senha deve ter pelo menos 8 caracteres.')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

it('seeds the development user only in the local environment', function (string $environment, int $expected) {
    app()->detectEnvironment(fn (): string => $environment);

    // Em produção o db:seed pede confirmação; --force é como ele rodaria de fato no deploy
    $this->artisan('db:seed', ['--force' => true])->assertSuccessful();

    expect(User::where('email', 'admin@example.com')->count())->toBe($expected);
})->with([
    'local' => ['local', 1],
    'staging' => ['staging', 0],
    'production' => ['production', 0],
]);
