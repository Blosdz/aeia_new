<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_closures', function (Blueprint $table) {
            $table->id();
            
            // Proyecto
            $table->foreignId('fund_id')
                ->constrained('funds')
                ->onDelete('cascade')
                ->comment('Fondo cerrado');
            
            // Período
            $table->date('period_start')
                ->nullable()
                ->comment('Inicio del período de inversión');
            $table->date('period_end')
                ->nullable()
                ->comment('Fin del período de inversión');
            
            // Totales
            $table->decimal('total_investment', 15, 2)
                ->comment('Sumatoria de todas las inversiones');
            $table->decimal('total_earnings', 15, 2)
                ->comment('Ganancia total del proyecto');
            $table->integer('total_clients')
                ->comment('Cantidad de clientes que invirtieron');
            
            // Desglose
            $table->decimal('company_total', 15, 2)
                ->comment('20% del total de inversión para la empresa');
            $table->decimal('referrals_total', 15, 2)
                ->default(0)
                ->comment('Total de comisiones para referidos');
            $table->decimal('clients_earnings', 15, 2)
                ->comment('Total de ganancias para distribuir entre clientes');
            
            // Referidos info
            $table->integer('total_referrals')
                ->default(0)
                ->comment('Cantidad total de referrals');
            $table->integer('first_deposits_referred')
                ->default(0)
                ->comment('Cantidad de primeros depósitos referidos');
            
            // Estado
            $table->enum('status', ['pending', 'calculated', 'distributed', 'closed'])
                ->default('pending')
                ->comment('Estado del cierre');
            $table->timestamp('calculated_at')
                ->nullable()
                ->comment('Fecha en que se calculó');
            $table->timestamp('distributed_at')
                ->nullable()
                ->comment('Fecha en que se distribuyó');
            $table->timestamp('closed_at')
                ->nullable()
                ->comment('Fecha en que se cerró el proyecto');
            
            $table->timestamps();
            
            // Índices
            $table->index('fund_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_closures');
    }
};
