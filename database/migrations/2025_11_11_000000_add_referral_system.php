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
        // Agregar columnas de referral a users
        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code', 50)->unique()->nullable()->after('unique_code');
            $table->foreignId('referred_by_user_id')->nullable()->constrained('users')->nullOnDelete()->after('referral_code');
            $table->timestamp('referral_accepted_at')->nullable()->after('referred_by_user_id');
        });

        // Tabla de configuración de comisiones por referido
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('advisor_profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('subscription_participant_id')->constrained('subscription_participants')->cascadeOnDelete();
            $table->decimal('commission_percentage', 5, 2); // Porcentaje de comisión
            $table->decimal('commission_amount', 18, 2)->default(0); // Monto comisionado
            $table->enum('status', ['pending', 'calculated', 'paid'])->default('pending');
            $table->timestamp('calculated_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['advisor_profile_id', 'subscription_participant_id']);
            $table->index(['advisor_profile_id', 'status']);
        });

        // Tabla de historial de comisiones
        Schema::create('referral_commission_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('commission_id')->constrained('referral_commissions')->cascadeOnDelete();
            $table->decimal('previous_amount', 18, 2)->nullable();
            $table->decimal('new_amount', 18, 2)->nullable();
            $table->string('event'); // 'calculated', 'updated', 'paid'
            $table->string('reason')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->json('metadata')->nullable();

            $table->index(['commission_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referral_commission_histories');
        Schema::dropIfExists('referral_commissions');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['referred_by_user_id']);
            $table->dropColumn(['referral_code', 'referred_by_user_id', 'referral_accepted_at']);
        });
    }
};
