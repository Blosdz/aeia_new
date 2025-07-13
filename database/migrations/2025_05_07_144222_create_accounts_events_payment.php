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
        Schema::create('client_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('bank_name',100);
            $table->char('card_number',16);
            $table->string('cci',20);
            $table->char('cvv',4);
            $table->string('holder_name',100);
            $table->date('expiration_date');
            $table->timestamps();
        });

        Schema::create('event_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',50)->unique();
            $table->text('description')->nullable();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('event_type_id')->constrained('event_types');
            $table->string('transaction_id',100)->unique();
            $table->foreignId('client_account_id')->constrained('client_accounts');
            $table->decimal('amount',14,2);
            $table->char('currency',3);
            $table->enum('status',['pending','completed','failed','refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('event_types');
        Schema::dropIfExists('client_accounts');
    }
};
