<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Actions\Auth\AuthenticateUser;
use App\Exceptions\TooManyLoginAttemptsException;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

final class LoginForm extends Form
{
    #[Validate(['required', 'string', 'email', 'max:255'])]
    public string $email = '';

    #[Validate(['required', 'string'])]
    public string $password = '';

    public bool $remember = false;

    /**
     * Mesma regra de credencial e de tentativas da API (POST /api/login).
     *
     * @throws ValidationException
     */
    public function authenticate(AuthenticateUser $authenticate, string $ip): User
    {
        $this->validate();

        try {
            return $authenticate->execute($this->email, $this->password, $ip);
        } catch (TooManyLoginAttemptsException $exception) {
            throw ValidationException::withMessages(['form.email' => $exception->getMessage()]);
        } catch (ValidationException $exception) {
            // A Action nomeia o campo "email"; na tela o campo é form.email
            throw ValidationException::withMessages(['form.email' => $exception->errors()['email'] ?? []]);
        } finally {
            // Propriedade pública volta ao navegador a cada resposta: a senha não fica no estado do componente
            $this->reset('password');
        }
    }
}
