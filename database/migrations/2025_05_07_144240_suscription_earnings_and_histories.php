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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('plan_type_id')->constrained('plan_types');
            $table->string('unique_code',50)->nullable();
            $table->foreignId('fund_id')->nullable()->constrained('funds');
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('investment_earnings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->decimal('initial_amount',14,2);
            $table->decimal('current_amount',14,2);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('investment_earnings_history', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('earning_id')->constrained('investment_earnings')->onDelete('cascade');
            $table->decimal('fluctuation_percent',6,4);
            $table->timestamp('recorded_at')->useCurrent();
            $table->json('metadata')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_earnings_history');
        Schema::dropIfExists('investment_earnings');
        Schema::dropIfExists('subscriptions');
    }
};
