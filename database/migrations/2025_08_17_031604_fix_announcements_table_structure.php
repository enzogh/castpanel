<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Vérifier si la table announcements existe
            if (Schema::hasTable('announcements')) {
                // Supprimer toutes les contraintes de clé étrangère existantes
                Schema::table('announcements', function (Blueprint $table) {
                    // Supprimer les contraintes de clé étrangère si elles existent
                    try {
                        $table->dropForeign(['author_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur si la contrainte n'existe pas
                    }
                });

                // Modifier la colonne author_id pour utiliser BIGINT UNSIGNED
                Schema::table('announcements', function (Blueprint $table) {
                    if (Schema::hasColumn('announcements', 'author_id')) {
                        $table->unsignedBigInteger('author_id')->change();
                    }
                });

                // Ajouter la contrainte de clé étrangère correcte
                Schema::table('announcements', function (Blueprint $table) {
                    $table->foreign('author_id')->references('id')->on('users')->onDelete('cascade');
                });

                // Ajouter l'index sur author_id
                Schema::table('announcements', function (Blueprint $table) {
                    $table->index('author_id');
                });
            }
        } catch (Exception $e) {
            // Log l'erreur mais ne pas faire échouer la migration
            \Log::error('Erreur lors de la correction de la table announcements: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('announcements')) {
            Schema::table('announcements', function (Blueprint $table) {
                // Supprimer l'index
                try {
                    $table->dropIndex(['author_id']);
                } catch (Exception $e) {
                    // Ignorer l'erreur si l'index n'existe pas
                }

                // Supprimer la contrainte de clé étrangère
                try {
                    $table->dropForeign(['author_id']);
                } catch (Exception $e) {
                    // Ignorer l'erreur si la contrainte n'existe pas
                }
            });
        }
    }
};
