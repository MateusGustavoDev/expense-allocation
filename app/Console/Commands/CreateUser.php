<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Cria um usuário com acesso à interface e à API. Não há cadastro público: é assim que staging e produção
 * ganham usuários (railway ssh -s web -- php artisan users:create).
 *
 * O que não vier por opção é perguntado; a senha é pedida sem eco, para não ficar no histórico do terminal.
 */
#[Signature('users:create {--name= : Nome} {--email= : E-mail de acesso} {--password= : Senha (prefira digitar quando perguntado)}')]
#[Description('Cria um usuário com acesso à interface e à API')]
final class CreateUser extends Command
{
    public function handle(): int
    {
        $data = [
            'name' => $this->stringOption('name') ?? text('Nome', required: true),
            'email' => $this->stringOption('email') ?? text('E-mail', required: true),
            'password' => $this->stringOption('password') ?? password('Senha (mínimo de 8 caracteres)', required: true),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::min(8)],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $validator->validated();
        $user = User::create($validated);

        $this->info("Usuário {$user->email} criado.");

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
