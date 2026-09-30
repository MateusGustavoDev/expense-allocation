<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

// Rede de segurança da conversão de moeda: pendentes cuja cotação já existe voltam para a fila
Schedule::command('expenses:convert-pending')->everyTenMinutes()->withoutOverlapping();

// Tokens da API vencidos há mais de um dia (expiração em config/sanctum.php)
Schedule::command('sanctum:prune-expired --hours=24')->daily();
