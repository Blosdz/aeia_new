<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investment_earnings', function (Blueprint $table) {
            // Eliminar la constraint existente
            $table->dropForeign(['fund_id']);

            // Hacer la columna nullable
            $table->unsignedBigInteger('fund_id')->nullable()->change();

            // Re-agregar la foreign key con nullable
            $table->foreign('fund_id')->references('id')->on('funds')->nullOnDelete();
        });

        // Actualizar el índice único para permitir múltiples registros con fund_id null
        Schema::table('investment_earnings', function (Blueprint $table) {
            $table->dropUnique(['subscription_id', 'fund_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investment_earnings', function (Blueprint $table) {
            // Revertir los cambios
            $table->dropForeign(['fund_id']);
            $table->unsignedBigInteger('fund_id')->nullable(false)->change();
            $table->foreign('fund_id')->references('id')->on('funds')->cascadeOnDelete();
            $table->unique(['subscription_id', 'fund_id']);
        });
    }
};
