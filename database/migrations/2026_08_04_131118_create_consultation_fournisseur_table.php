<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_fournisseur', function (Blueprint $table) {

            $table->id();

            $table->foreignId('consultation_id')
                  ->constrained('consultation')
                  ->onDelete('cascade');

            $table->foreignId('fournisseurs_id')
                  ->constrained('fournisseurs')
                  ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_fournisseur');
    }
};

