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
        Schema::create('profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('dni', 20);
            $table->string('phone_extension', 5);
            $table->string('phone', 20);
            $table->string('country', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->date('birthdate');
            $table->enum('type', ['owner', 'coverage_beneficiary'])->default('owner');
            $table->json('photos_dni')->nullable();
            $table->string('photo_id_type')->nullable();
            $table->string('signature_digital')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });
        //
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');

        //
    }
};
