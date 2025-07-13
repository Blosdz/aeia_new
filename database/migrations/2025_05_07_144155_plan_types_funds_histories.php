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
        Schema::create('plan_types', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('category', ['investment','coverage']);
            $table->string('name', 100);
            $table->decimal('amount_min', 12,2);
            $table->decimal('amount_max', 12,2);
            $table->string('img_url')->nullable();
            $table->enum('periodicity',['monthly','annual'])->default('monthly');
        });

        Schema::create('funds', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('plan_type_id')->constrained('plan_types')->onDelete('cascade');
            $table->string('name', 100);
            $table->decimal('initial_amount',14,2);
            $table->decimal('current_amount',14,2);
            $table->json('metadata')->nullable();
            $table->enum('status', ['open','closed','paused'])->default('open');
            $table->timestamps();
        });

        Schema::create('fund_histories', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('fund_id')->constrained('funds')->onDelete('cascade');
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
        Schema::dropIfExists('fund_histories');
        Schema::dropIfExists('funds');
        Schema::dropIfExists('plan_types');
    }
};
