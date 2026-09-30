<?php

declare(strict_types=1);

use App\Livewire\Auth\LoginPage;
use App\Models\User;
use Livewire\Livewire;

function webUser(): User
{
    return User::factory()->create(['name' => 'Ana Souza', 'email' => 'ana@example.com', 'password' => 'senha-segura']);
}

it('sends guests to the login page', function (string $uri) {
    $this->get($uri)->assertRedirect('/login');
})->with(['/', '/reports', '/expenses', '/expenses/create', '/expenses/import', '/units', '/companies']);

it('renders the login page without the sidebar', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSeeLivewire(LoginPage::class)
        ->assertSee('Entrar')
        ->assertDontSee('Navegação principal');
});

it('logs in and goes back to the page the user asked for', function () {
    webUser();
    $this->get('/expenses');

    Livewire::test(LoginPage::class)
        ->set('form.email', 'ana@example.com')
        ->set('form.password', 'senha-segura')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect('/expenses');

    $this->assertAuthenticatedAs(User::sole());
});

it('shows a generic error for wrong credentials and forgets the typed password', function () {
    webUser();

    Livewire::test(LoginPage::class)
        ->set('form.email', 'ana@example.com')
        ->set('form.password', 'errada')
        ->call('login')
        ->assertHasErrors(['form.email'])
        ->assertSee('Essas credenciais não foram encontradas em nossos registros.')
        ->assertSet('form.password', '');

    $this->assertGuest();
});

it('validates the form before checking credentials', function () {
    Livewire::test(LoginPage::class)
        ->set('form.email', 'nao-e-email')
        ->call('login')
        ->assertHasErrors(['form.email' => 'email', 'form.password' => 'required']);
});

it('shares the attempt limit with the api', function () {
    webUser();

    foreach (range(1, 5) as $attempt) {
        $this->postJson('/api/login', ['email' => 'ana@example.com', 'password' => 'errada']);
    }

    Livewire::test(LoginPage::class)
        ->set('form.email', 'ana@example.com')
        ->set('form.password', 'senha-segura')
        ->call('login')
        ->assertHasErrors(['form.email'])
        ->assertSee('Muitas tentativas de login.');

    $this->assertGuest();
});

it('keeps logged users away from the login page', function () {
    $this->actingAs(webUser())->get('/login')->assertRedirect('/');
});

it('shows the user in the sidebar and logs out', function () {
    $this->actingAs(webUser());

    $this->get('/reports')->assertSee(['Ana Souza', 'ana@example.com']);

    $this->post('/logout')->assertRedirect('/login');
    $this->assertGuest();
    $this->get('/reports')->assertRedirect('/login');
});
