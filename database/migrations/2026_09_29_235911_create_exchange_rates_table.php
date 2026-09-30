<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cache das cotações consultadas: cotação de data passada não muda, então nunca expira
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('currency', 3);
            // Data pedida (a data da despesa)
            $table->date('date');
            $table->decimal('rate', 12, 6);
            // Data da cotação efetivamente usada: difere de `date` em fins de semana e feriados
            $table->date('quoted_on');
            $table->timestamps();

            $table->unique(['currency', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
