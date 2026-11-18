
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cette migration corrective est réservée à PostgreSQL.
        // Elle est ignorée pendant les tests SQLite.
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ARTICLE
        Schema::table('article', function (Blueprint $table) {
            $table->dropForeign('article_consultation_id_foreign');

            $table->foreign('consultation_id')
                ->references('id')->on('consultation')
                ->cascadeOnDelete();
        });

        // CONSULTATION FOURNISSEUR
        Schema::table('consultation_fournisseur', function (Blueprint $table) {
            $table->dropForeign('consultation_fournisseur_consultation_id_foreign');
            $table->dropForeign('consultation_fournisseur_fournisseurs_id_foreign');

            $table->foreign('consultation_id')
                ->references('id')->on('consultation')
                ->cascadeOnDelete();

            $table->foreign('fournisseurs_id')
                ->references('id')->on('fournisseurs')
                ->cascadeOnDelete();
        });

        // DOCUMENTS
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign('documents_consultation_id_foreign');
            $table->dropForeign('documents_fournisseur_id_foreign');
            $table->dropForeign('documents_proces_verbal_id_foreign');

            $table->foreign('consultation_id')
                ->references('id')->on('consultation')
                ->cascadeOnDelete();

            $table->foreign('fournisseur_id')
                ->references('id')->on('fournisseurs')
                ->nullOnDelete();

            $table->foreign('proces_verbal_id')
                ->references('id')->on('procesverbal')
                ->nullOnDelete();
        });

        // OFFRE
        Schema::table('offre', function (Blueprint $table) {
            $table->dropForeign('offre_consultation_id_foreign');
            $table->dropForeign('offre_article_id_foreign');
            $table->dropForeign('offre_fournisseur_id_foreign');

            $table->foreign('consultation_id')
                ->references('id')->on('consultation')
                ->cascadeOnDelete();

            $table->foreign('article_id')
                ->references('id')->on('article')
                ->cascadeOnDelete();

            $table->foreign('fournisseur_id')
                ->references('id')->on('fournisseurs')
                ->cascadeOnDelete();
        });

        // PROCES VERBAL
        Schema::table('procesverbal', function (Blueprint $table) {
            $table->dropForeign('procesverbal_consultation_id_foreign');

            $table->foreign('consultation_id')
                ->references('id')->on('consultation')
                ->cascadeOnDelete();
        });

        // TABLE PIVOT PROCES VERBAL / SIGNATAIRE
        Schema::table('proces_verbal_signataire', function (Blueprint $table) {
            $table->foreign('proces_verbal_id')
                ->references('id')->on('procesverbal')
                ->cascadeOnDelete();

            $table->foreign('signataire_id')
                ->references('id')->on('signataires')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Cette migration corrective est réservée à PostgreSQL.
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        // ARTICLE
        Schema::table('article', function (Blueprint $table) {
            $table->dropForeign('article_consultation_id_foreign');

            $table->foreign('consultation_id')
                ->references('id')->on('consultation');
        });

        // CONSULTATION FOURNISSEUR
        Schema::table('consultation_fournisseur', function (Blueprint $table) {
            $table->dropForeign('consultation_fournisseur_consultation_id_foreign');
            $table->dropForeign('consultation_fournisseur_fournisseurs_id_foreign');

            $table->foreign('consultation_id')->references('id')->on('consultation');
            $table->foreign('fournisseurs_id')->references('id')->on('fournisseurs');
        });

        // DOCUMENTS
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign('documents_consultation_id_foreign');
            $table->dropForeign('documents_fournisseur_id_foreign');
            $table->dropForeign('documents_proces_verbal_id_foreign');

            $table->foreign('consultation_id')->references('id')->on('consultation');
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs');
            $table->foreign('proces_verbal_id')->references('id')->on('procesverbal');
        });

        // OFFRE
        Schema::table('offre', function (Blueprint $table) {
            $table->dropForeign('offre_consultation_id_foreign');
            $table->dropForeign('offre_article_id_foreign');
            $table->dropForeign('offre_fournisseur_id_foreign');

            $table->foreign('consultation_id')->references('id')->on('consultation');
            $table->foreign('article_id')->references('id')->on('article');
            $table->foreign('fournisseur_id')->references('id')->on('fournisseurs');
        });

        // PROCES VERBAL
        Schema::table('procesverbal', function (Blueprint $table) {
            $table->dropForeign('procesverbal_consultation_id_foreign');

            $table->foreign('consultation_id')->references('id')->on('consultation');
        });

        // TABLE PIVOT
        Schema::table('proces_verbal_signataire', function (Blueprint $table) {
            $table->dropForeign('proces_verbal_signataire_proces_verbal_id_foreign');
            $table->dropForeign('proces_verbal_signataire_signataire_id_foreign');
        });
    }
};
