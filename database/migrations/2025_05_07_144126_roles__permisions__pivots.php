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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->unique(['user_id', 'role_id']);
        });
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->foreignId('permission_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['role_id', 'permission_id']);
        });




    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('role_permissions', function (Blueprint $table) {
        $table->dropForeign(['role_id']);
        $table->dropForeign(['permission_id']);
    });

    Schema::table('user_roles', function (Blueprint $table) {
        $table->dropForeign(['user_id']);
        $table->dropForeign(['role_id']);
    });

    Schema::dropIfExists('role_permissions');
    Schema::dropIfExists('user_roles');
    Schema::dropIfExists('roles');
    Schema::dropIfExists('permissions');
    }
};
