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
        Schema::create('consultation', function (Blueprint $table) {
    $table->id();

    $table->foreignId('demande_achat_id')
          ->constrained('demande')
          ->cascadeOnDelete();

    $table->string('objet');
    $table->date('date_lancement');
    $table->date('date_limite');
    $table->string('reference');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation');
    }
};
