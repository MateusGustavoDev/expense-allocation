<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            // Unidade com despesas rateadas não pode ser apagada: o histórico do relatório depende dela
            $table->foreignId('unit_id')->constrained()->restrictOnDelete();
            // Percentual em pontos-base: 10000 = 100%
            $table->unsignedInteger('basis_points');
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('amount_brl_cents')->nullable();
            $table->timestamps();

            $table->unique(['expense_id', 'unit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_allocations');
    }
};
