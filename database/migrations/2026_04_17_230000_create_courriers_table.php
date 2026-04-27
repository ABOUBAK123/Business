<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courriers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numero', 120)->unique();
            $table->enum('type', ['arrive', 'depart']);
            $table->string('objet', 500);
            $table->text('expediteur')->nullable();
            $table->text('destinataire')->nullable();
            $table->string('numero_emission', 100)->nullable();
            $table->enum('urgence', ['normale', 'urgent', 'tres_urgent'])->default('normale');
            $table->date('date_emission');
            $table->text('observations')->nullable();
            $table->enum('statut', ['en_attente', 'en_traitement', 'traite'])->default('en_attente')->index();

            // Utilisateur qui a enregistré le courrier
            $table->uuid('enregistre_par')->nullable()->index();
            $table->foreign('enregistre_par')->references('id')->on('users')->onDelete('set null');

            // Administration (entité) à laquelle appartient l'enregistreur
            $table->uuid('administration_id')->nullable()->index();
            $table->foreign('administration_id')->references('id')->on('issuing_administrations')->onDelete('set null');

            // Imputation
            $table->string('impute_a', 255)->nullable();       // nom/code de la sous-entité
            $table->uuid('impute_par')->nullable();             // user qui a imputé
            $table->foreign('impute_par')->references('id')->on('users')->onDelete('set null');
            $table->timestamp('impute_le')->nullable();
            $table->string('instruction_nom', 255)->nullable(); // instruction choisie
            $table->text('instruction_desc')->nullable();

            // Fichiers
            $table->json('pieces_jointes')->nullable();
            $table->string('accuse_reception', 1000)->nullable();
            $table->string('fichier_reponse', 1000)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courriers');
    }
};
