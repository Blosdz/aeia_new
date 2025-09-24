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
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscriber_profile_id')->nullable()->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->cascadeOnDelete();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignId('fund_id')->nullable()->constrained('funds')->nullOnDelete();
            $table->foreignId('asesor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('earning_history_id')->nullable()->constrained('investment_earnings_history')->nullOnDelete();
            $table->enum('reason', ['performance_return','closure','referral'])->default('performance_return');
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('amount', 18, 2);
            $table->char('currency', 3);
            $table->date('period_at');
            $table->enum('status', ['accrued','pending_payment','paid','cancelled'])->default('accrued');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->unique(['subscriber_profile_id','subscription_id','fund_id','period_at','reason'], 'uniq_reward_period');
        });
 
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles');
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->enum('contract_type',['payment','declaration','coverage_responsibility']);
            $table->timestamp('signed_at')->useCurrent();
            $table->json('metadata')->nullable();
        });

        Schema::create('client_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete(); // quién recibe
            $table->foreignId('reward_id')->constrained('rewards')->cascadeOnDelete(); // qué reward estás pagando
            $table->foreignId('earning_id')->constrained('investment_earnings')->cascadeOnDelete(); // si pagas contra el saldo agregado

            $table->decimal('amount', 14, 2);
            $table->char('currency', 3);
            $table->enum('status', ['pending','settled','failed'])->default('pending');
            $table->string('transaction_ref', 120)->nullable(); // id del banco/PSP
            $table->timestamp('deposit_date')->useCurrent();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique('transaction_ref');
            $table->index(['profile_id','deposit_date']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_deposits');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('rewards');
    }
};
