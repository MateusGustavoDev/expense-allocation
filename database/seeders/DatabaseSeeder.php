<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Usuário de desenvolvimento com senha conhecida: só no ambiente local. Staging e produção criam usuários
        // com "php artisan users:create", e a credencial não fica no repositório
        if (app()->environment('local')) {
            User::query()->firstOrCreate(
                ['email' => 'admin@example.com'],
                ['name' => 'Administrador', 'password' => 'password'],
            );
        }
    }
}
