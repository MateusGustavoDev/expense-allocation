<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Exceptions\TooManyLoginAttemptsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Confere e-mail e senha, limitando as tentativas por e-mail + IP.
 *
 * Usada pelos dois pontos de entrada: a API troca o usuário por um token (Sanctum) e a tela de login abre uma
 * sessão. A regra de credencial e de tentativas fica num lugar só.
 */
final class AuthenticateUser
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    /**
     * @throws ValidationException credenciais inválidas (erro no campo e-mail, sem dizer qual dos dois errou)
     * @throws TooManyLoginAttemptsException tentativas esgotadas para este e-mail e IP
     */
    public function execute(string $email, string $password, string $ip): User
    {
        $throttleKey = Str::transliterate(Str::lower($email)).'|'.$ip;

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            throw new TooManyLoginAttemptsException(RateLimiter::availableIn($throttleKey));
        }

        $user = User::query()->where('email', $email)->first();

        // Hash::check roda mesmo sem usuário: o tempo de resposta não revela se o e-mail existe
        if (! Hash::check($password, $user->password ?? Hash::make(Str::random()))) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($throttleKey);

        /** @var User $user */
        return $user;
    }
}
