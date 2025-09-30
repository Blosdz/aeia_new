<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('plan_type_id')->constrained('plan_types');
            $table->string('unique_code',50)->nullable()->unique();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('investment_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('fund_id')->constrained('funds')->cascadeOnDelete();
            $table->decimal('initial_amount', 18, 2);
            $table->decimal('current_amount', 18, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['subscription_id','fund_id']);
        });

        Schema::create('investment_earnings_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('earning_id')->constrained('investment_earnings')->cascadeOnDelete();
            $table->decimal('fluctuation_percent', 9, 4); // 50,4 es innecesario
            $table->timestamp('recorded_at')->useCurrent();
            $table->index(['earning_id','recorded_at']);
            $table->json('metadata')->nullable();
        });

        Schema::create('subscription_participants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            //si hay un profile id de staff este es el de un lead
            $table->foreignId('profile_id')->nullable()->constrained('profiles')->cascadeOnDelete();
            $table->enum('role', ['owner','beneficiary','advisor'])->default('owner');
            $table->decimal('share_percent', 5, 2)->nullable();
            $table->boolean('is_primary_owner')->default(false);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            // Útil si no quieres duplicar el mismo participante por rol
            // $table->unique(['subscription_id','profile_id','role']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete(); // <-- plural
            $table->foreignId('fund_id')->constrained('funds')->cascadeOnDelete();
            $table->decimal('amount', 14, 2)->nullable();   // o asignación por monto
            $table->decimal('percent', 5, 2)->nullable();   // o por porcentaje (0–100)
            $table->json('metadata')->nullable();
            $table->enum('status', ['accrued','pending_payment','paid','cancelled'])->default('accrued');
            $table->timestamps();

            $table->unique(['payment_id','subscription_id','fund_id']);
            $table->index(['subscription_id','fund_id']);
        });
    }

    public function down(): void
    {
        // Reversa en orden: primero tablas que dependen de otras
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('subscription_participants');
        Schema::dropIfExists('investment_earnings_history');
        Schema::dropIfExists('investment_earnings');
        Schema::dropIfExists('subscriptions');
    }
};
