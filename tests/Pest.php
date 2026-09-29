<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Testes de Feature sobem a aplicação Laravel e rodam cada teste numa transação revertida ao final.
// Testes de Unit não usam o framework: cobrem apenas lógica pura.
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
