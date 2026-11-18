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
        Schema::create('offre', function (Blueprint $table) {

    $table->id();

    $table->foreignId('consultation_id')
          ->constrained('consultation')
          ->cascadeOnDelete();

    $table->foreignId('article_id')
          ->constrained('article')
          ->cascadeOnDelete();

    $table->foreignId('fournisseur_id')
          ->constrained('fournisseurs')
          ->cascadeOnDelete();

    $table->decimal('prix_unitaire_ht',10,3);

    $table->timestamps();

});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offre');
    }
};
