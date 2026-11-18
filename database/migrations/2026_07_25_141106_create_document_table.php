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
        Schema::create('documents', function (Blueprint $table) {
    $table->id();

    $table->foreignId('consultation_id')
          ->constrained('consultation')
          ->cascadeOnDelete();

    $table->foreignId('fournisseur_id')
          ->nullable()
          ->constrained('fournisseurs')
          ->nullOnDelete();

    $table->foreignId('proces_verbal_id')
          ->nullable()
          ->constrained('procesverbal')
          ->nullOnDelete();

    $table->string('type_document');
    $table->string('chemin_fichier');
    $table->date('date_ajout');

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document');
    }
};
