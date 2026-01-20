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
        //
        Schema::create('documents', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->morphs('documentable'); 

            $table->string('name')->nullable(); // nombre legible del archivo
            $table->string('file_path'); // URL o ruta de almacenamiento
            $table->string('file_extension', 10)->nullable();
            $table->bigInteger('file_size')->nullable(); // en bytes
            $table->enum('file_type', ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx','other'])->default('other');

            $table->json('metadata')->nullable(); // opcional para tags, observaciones, etc.
            $table->foreignId('uploaded_by')->constrained('users'); // quién lo subió
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
        Schema::dropIfExists('documents');
    }
};
