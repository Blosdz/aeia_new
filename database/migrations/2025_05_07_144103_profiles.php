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
            $table->enum('type',['user','boss','staff']);
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
            $table->json('photos_dni')->nullable();
            $table->string('photo_id_type')->nullable();
            $table->string('signature_digital')->nullable();
            $table->integer('verified')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_boss',function(Blueprint $table){
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organization')->onDelete('cascade');
        });

        Schema::create('profile_staff',function(Blueprint $table){
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->onDelete('cascade');
            $table->foreignId('boss_id')->constrained('profile_boss')->onDelete('cascade');
        });

        Schema::create('profile_beneficiary',function(Blueprint $table){
            $table->id();
            $table->foreignId('profile_id')->constrained('profiles')->onDelete('cascade');
            $table->string('sex')->nullable();
            $table->string('first_name', 50)->nullable();
            $table->enum('type',['user','boss','staff']);
            $table->string('last_name', 50)->nullable();
            $table->string('type_document')->nullable();
            $table->string('dni', 50)->nullable();  // documento of identification for users it may be dni or passport number
            $table->string('phone_extension', 5)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('nacionality')->nullable(); //country residence
            $table->string('city')->nullable(); //country residence
            $table->json('photos_dni')->nullable();
            $table->string('photo_beneficiary')->nullable();
            $table->json('beneficiary_params')->nullable();
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending')->after('phone_extension');
            $table->text('verification_notes')->nullable()->after('verification_status');
            $table->timestamp('verified_at')->nullable()->after('verification_notes');
            $table->timestamps();
        });
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
