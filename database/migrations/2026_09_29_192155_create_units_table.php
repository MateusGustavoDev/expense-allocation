<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            // Empresa com unidades não pode ser apagada: evita perder o vínculo das despesas rateadas
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            // Identificador estável usado na importação por CSV (ex.: unidade-a)
            $table->string('slug', 120)->unique();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
