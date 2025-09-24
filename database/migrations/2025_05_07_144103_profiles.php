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
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('type_document')->nullable();
            $table->string('dni', 50)->nullable();  // documento of identification for users it may be dni or passport number
            $table->string('phone_extension', 5)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('nacionality')->nullable(); //country residence
            $table->string('city')->nullable(); //country residence
            $table->string('country')->nullable(); //country residence
            $table->string('job')->nullable();
            $table->string('country_dni', 50)->nullable(); //country document nacionality
            $table->string('state', 50)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('sex')->nullable();
            $table->enum('type', ['owner', 'coverage_beneficiary'])->default('owner');
            $table->json('photos_dni')->nullable();
            $table->string('photo_id_type')->nullable();
            $table->string('signature_digital')->nullable();
            $table->integer('verified')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('type_document')->nullable();
            $table->string('dni', 50)->nullable();  // documento of identification for users it may be dni or passport number
            $table->string('phone_extension', 5)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('nacionality')->nullable(); //country residence
            $table->string('city')->nullable(); //country residence
            $table->string('country')->nullable(); //country residence
            $table->string('country_dni', 50)->nullable(); //country document nacionality
            $table->string('state', 50)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('sex')->nullable();
            $table->json('photos_dni')->nullable();
            $table->string('photo_id_type')->nullable();
            $table->string('signature_digital')->nullable();
            $table->integer('verified')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_boss', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('first_name', 50)->nullable();
            $table->string('last_name', 50)->nullable();
            $table->string('type_document')->nullable();
            $table->string('dni', 50)->nullable();  // documento of identification for users it may be dni or passport number
            $table->string('phone_extension', 5)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('nacionality')->nullable(); //country residence
            $table->string('city')->nullable(); //country residence
            $table->string('country')->nullable(); //country residence
            $table->string('country_dni', 50)->nullable(); //country document nacionality
            $table->string('state', 50)->nullable();
            $table->date('birthdate')->nullable();
            $table->string('sex')->nullable();
            $table->json('photos_dni')->nullable();
            $table->string('photo_id_type')->nullable();
            $table->string('signature_digital')->nullable();
            $table->integer('verified')->default(0);
            $table->timestamps();
        });
        /*
         *
         * photos_dni[]	photo_id_type
         * ["dni_front.jpg", "dni_back.jpg"]	"DNI"
         * ["passport.jpg"]	"Pasaporte"
         * ["license_front.jpg", "license_back.jpg"]	"Licencia de conducir"
         *
         */
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
