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
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('subscriber_id')->constrained('users');
            $table->foreignId('subscription_id')->constrained('subscriptions');
            $table->decimal('percentage',5,2);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('profile_id')->constrained('profiles');
            $table->foreignId('event_type_id')->nullable()->constrained('event_types');
            $table->enum('contract_type',['payment','declaration','coverage_responsibility']);
            $table->timestamp('signed_at')->useCurrent();
            $table->json('metadata')->nullable();
        });

        Schema::create('client_deposits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('earning_id')->constrained('investment_earnings');
            $table->decimal('amount',14,2);
            $table->char('currency',3);
            $table->timestamp('deposit_date')->useCurrent();
            $table->json('metadata')->nullable();
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
