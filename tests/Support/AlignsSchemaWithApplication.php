<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aligne la base de TEST (SQLite en mémoire) sur la vraie base MySQL de
 * l'application.
 *
 * Pourquoi ? Les fichiers de database/migrations ne décrivent pas
 * exactement la base que l'application utilise : certaines colonnes ont été
 * ajoutées ou assouplies à la main. Sans ces ajustements, des fonctions qui
 * marchent chez toi échoueraient dans les tests (et sur une installation
 * neuve faite uniquement avec "php artisan migrate").
 *
 * Chaque ligne ci-dessous = un écart connu. Les tests ne touchent JAMAIS ta
 * vraie base : tout se passe en mémoire.
 */
trait AlignsSchemaWithApplication
{
    protected function alignerSchemaAvecApplication(): void
    {
        // demande.reference : utilisée partout (formulaire, import) mais
        // absente des migrations.
        if (! Schema::hasColumn('demande', 'reference')) {
            Schema::table('demande', function (Blueprint $table) {
                $table->string('reference')->nullable();
            });
        }

        // article.description : utilisée par le formulaire des articles.
        if (! Schema::hasColumn('article', 'description')) {
            Schema::table('article', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }

        // documents : le modèle et le formulaire utilisent "fk_consultation_id",
        // alors que la migration crée "consultation_id" (obligatoire).
        if (! Schema::hasColumn('documents', 'fk_consultation_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->unsignedBigInteger('fk_consultation_id')->nullable();
            });
        }

        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedBigInteger('consultation_id')->nullable()->change();
        });

        // consultation.date_lancement / date_limite : le formulaire de
        // modification et l'import Excel acceptent une date non renseignée.
        Schema::table('consultation', function (Blueprint $table) {
            $table->date('date_lancement')->nullable()->change();
            $table->date('date_limite')->nullable()->change();
        });

        // offre.prix_unitaire_ht doit accepter NULL : un fournisseur peut
        // répondre "ND" (non disponible) ou "NC" (non concerné).
        Schema::table('offre', function (Blueprint $table) {
            $table->decimal('prix_unitaire_ht', 15, 3)->nullable()->change();
        });

        // fournisseurs.matricule_fiscal : facultatif (le formulaire et l'import
        // créent des fournisseurs sans matricule), mais toujours unique.
        Schema::table('fournisseurs', function (Blueprint $table) {
            $table->string('matricule_fiscal')->nullable()->change();
        });

        // procesverbal.commentaire : le commentaire est facultatif.
        Schema::table('procesverbal', function (Blueprint $table) {
            $table->text('commentaire')->nullable()->change();
        });
    }
}
