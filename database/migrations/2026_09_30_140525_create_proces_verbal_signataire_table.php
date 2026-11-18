<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proces_verbal_signataire', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('proces_verbal_id');
            $table->unsignedBigInteger('signataire_id');

            $table->integer('ordre')->default(1);

            $table->timestamps();

            $table->unique([
                'proces_verbal_id',
                'signataire_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proces_verbal_signataire');
    }
};