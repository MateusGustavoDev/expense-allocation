<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\Auth\AuthenticateUser;
use App\Livewire\Forms\LoginForm;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Login da interface por sessão (a API usa token: POST /api/login). Os dois usam a mesma AuthenticateUser.
 */
#[Layout('layouts::guest')]
#[Title('Entrar')]
final class LoginPage extends Component
{
    public LoginForm $form;

    public function login(AuthenticateUser $authenticate): void
    {
        $user = $this->form->authenticate($authenticate, (string) request()->ip());

        Auth::login($user, $this->form->remember);
        // Nova sessão a cada login: impede fixação de sessão
        session()->regenerate();

        $this->redirectIntended(route('reports.index'));
    }

    public function render(): View
    {
        return view('livewire.auth.login-page');
    }
}
