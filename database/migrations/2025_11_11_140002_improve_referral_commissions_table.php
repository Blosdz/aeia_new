<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mejoras a la tabla referral_commissions para cierre de proyecto
     */
    public function up(): void
    {
        Schema::table('referral_commissions', function (Blueprint $table) {
            // Agregar campos si no existen
            if (!Schema::hasColumn('referral_commissions', 'staff_user_id')) {
                $table->foreignId('staff_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('id')
                    ->comment('Staff que realizó la referencia');
            }
            
            if (!Schema::hasColumn('referral_commissions', 'referred_user_id')) {
                $table->foreignId('referred_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->onDelete('set null')
                    ->after('staff_user_id')
                    ->comment('Usuario referido');
            }
            
            if (!Schema::hasColumn('referral_commissions', 'reward_id')) {
                $table->foreignId('reward_id')
                    ->nullable()
                    ->constrained('rewards')
                    ->onDelete('set null')
                    ->comment('Vinculado a Reward en cierre');
            }
            
            if (!Schema::hasColumn('referral_commissions', 'deposit_amount')) {
                $table->decimal('deposit_amount', 15, 2)
                    ->nullable()
                    ->comment('Monto del depósito original');
            }
            
            if (!Schema::hasColumn('referral_commissions', 'is_first_deposit')) {
                $table->boolean('is_first_deposit')
                    ->default(true)
                    ->comment('¿Es el primer depósito del cliente?');
            }
            
            if (!Schema::hasColumn('referral_commissions', 'deposit_count')) {
                $table->integer('deposit_count')
                    ->default(1)
                    ->comment('Número de depósito de este cliente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('referral_commissions', function (Blueprint $table) {
            // Eliminar las columnas agregadas
            $columns = [
                'staff_user_id',
                'referred_user_id',
                'reward_id',
                'deposit_amount',
                'is_first_deposit',
                'deposit_count'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('referral_commissions', $column)) {
                    // Eliminar foreign key correspondiente si existe (envolvemos en try para evitar errores en rollback)
                    if (in_array($column, ['staff_user_id', 'referred_user_id', 'reward_id'])) {
                        try {
                            $table->dropForeign([$column]);
                        } catch (\Exception $e) {
                            // La constraint puede no existir en algunas instalaciones/DBs (por eso se ignora)
                        }
                    }

                    $table->dropColumn($column);
                }
            }
        });
    }
};
