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
    if (!Schema::hasColumn('offre', 'statut_prix')) {
        Schema::table('offre', function (Blueprint $table) {
            $table->string('statut_prix', 2)
                ->nullable()
                ->after('prix_unitaire_ht');
        });
    }
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('offre', function (Blueprint $table) {
            $table->dropColumn('statut_prix');
        });
    }
};