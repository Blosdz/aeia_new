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
        Schema::table('subscription_participants', function (Blueprint $table) {
            $table->foreignId('investment_earnings_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_participants', function (Blueprint $table) {
            $table->foreignId('investment_earnings_id')->nullable(false)->change();
        });
    }
};
