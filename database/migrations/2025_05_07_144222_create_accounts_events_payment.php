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
            $table->id();
            // $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('bank_name',100);
            $table->string('address_wallet')->nullable(); //si en caso hubiera
            /* $table->char('card_number',16); */
            $table->string('last4',4)->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->string('card_token')->nullable();    // token seguro del PSP
            /* $table->char('cvv',4); */
            $table->string('holder_name',100);
            /* $table->date('expiration_date'); */
            $table->timestamps();
        });

        Schema::create('relation_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->cascadeOnDelete();
            $table->foreignId('client_account_id')->constrained('client_accounts')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['profile_id','client_account_id']);
        });


        //para leads
        Schema::create('quotation',function(Blueprint $table){
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->onDelete('cascade');
            $table->string('transaction_id',100)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->char('currency',3);
            $table->enum('status',['pending','completed','failed','refunded'])->default('pending');
            $table->foreignId('plan_type_id')->constrained('plan_types');
            $table->string('url_payment')->nullable(0);
            $table->timestamps();
        });


        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id',100)->unique();
            $table->decimal('amount', 18, 2);
            $table->char('currency',3);
            $table->enum('status',['pending','completed','failed','refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->foreignId('client_account_id')->constrained('client_accounts')->restrictOnDelete();
            $table->foreignId('payer_profile_id')->constrained('profiles')->restrictOnDelete();
            $table->timestamps();
            $table->index(['payer_profile_id','client_account_id']);
            $table->index('created_at');
            $table->foreign(['payer_profile_id','client_account_id'])
                  ->references(['profile_id','client_account_id'])
                  ->on('relation_accounts')
                  ->onUpdate('cascade')
                  ->onDelete('restrict');
        });

        Schema::create('payment_leads', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id',100)->unique();
            $table->decimal('amount', 18, 2);
            $table->char('currency',3);
            $table->enum('status',['pending','completed','failed','refunded'])->default('pending');
            $table->json('metadata')->nullable();
            $table->foreignId('quotation_id')->constrained('quotation')->restrictOnDelete();
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
        Schema::dropIfExists('pay_user');
    }
};
