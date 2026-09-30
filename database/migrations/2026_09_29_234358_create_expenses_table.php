<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('supplier');
            $table->date('date')->index();
            // Valores monetários sempre em centavos: nunca float
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3);
            // Nulos enquanto a conversão para BRL não acontece (despesas em moeda estrangeira)
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->unsignedBigInteger('amount_brl_cents')->nullable();
            $table->string('conversion_status', 20)->index();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
