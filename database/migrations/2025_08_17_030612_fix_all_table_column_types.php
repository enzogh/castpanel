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
            // Étape 1: Modifier la colonne id de la table users pour utiliser BIGINT
            if (Schema::hasTable('users')) {
                Schema::table('users', function (Blueprint $table) {
                    // Modifier la colonne id de INTEGER UNSIGNED à BIGINT UNSIGNED
                    $table->bigIncrements('id')->change();
                });
            }

            // Étape 2: Modifier les colonnes de la table tickets si elle existe
            if (Schema::hasTable('tickets')) {
                Schema::table('tickets', function (Blueprint $table) {
                    // Modifier les colonnes pour utiliser BIGINT UNSIGNED
                    if (Schema::hasColumn('tickets', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                    if (Schema::hasColumn('tickets', 'server_id')) {
                        $table->unsignedBigInteger('server_id')->nullable()->change();
                    }
                    if (Schema::hasColumn('tickets', 'assigned_to')) {
                        $table->unsignedBigInteger('assigned_to')->nullable()->change();
                    }
                });
            }

            // Étape 3: Modifier les colonnes de la table ticket_messages si elle existe
            if (Schema::hasTable('ticket_messages')) {
                Schema::table('ticket_messages', function (Blueprint $table) {
                    // Modifier les colonnes pour utiliser BIGINT UNSIGNED
                    if (Schema::hasColumn('ticket_messages', 'ticket_id')) {
                        $table->unsignedBigInteger('ticket_id')->change();
                    }
                    if (Schema::hasColumn('ticket_messages', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                });
            }

            // Étape 4: Ajouter les contraintes de clé étrangère à la table tickets
            if (Schema::hasTable('tickets')) {
                Schema::table('tickets', function (Blueprint $table) {
                    // Supprimer d'abord les contraintes existantes si elles existent
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur si la contrainte n'existe pas
                    }
                    
                    try {
                        $table->dropForeign(['server_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur si la contrainte n'existe pas
                    }
                    
                    try {
                        $table->dropForeign(['assigned_to']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur si la contrainte n'existe pas
                    }
                    
                    // Ajouter les nouvelles contraintes
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
                    $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
                });
            }

            // Étape 5: Ajouter les contraintes de clé étrangère à la table ticket_messages
            if (Schema::hasTable('ticket_messages')) {
                Schema::table('ticket_messages', function (Blueprint $table) {
                    // Supprimer d'abord les contraintes existantes si elles existent
                    try {
                        $table->dropForeign(['ticket_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur si la contrainte n'existe pas
                    }
                    
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur si la contrainte n'existe pas
                    }
                    
                    // Ajouter les nouvelles contraintes
                    $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
                    $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                });
            }

        } catch (Exception $e) {
            // Log l'erreur mais ne pas faire échouer la migration
            \Log::warning('Migration fix_all_table_column_types failed: ' . $e->getMessage());
            throw $e; // Relancer l'erreur pour que la migration échoue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            // Supprimer les contraintes de clé étrangère
            if (Schema::hasTable('tickets')) {
                Schema::table('tickets', function (Blueprint $table) {
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur
                    }
                    
                    try {
                        $table->dropForeign(['server_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur
                    }
                    
                    try {
                        $table->dropForeign(['assigned_to']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur
                    }
                });
            }

            if (Schema::hasTable('ticket_messages')) {
                Schema::table('ticket_messages', function (Blueprint $table) {
                    try {
                        $table->dropForeign(['ticket_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur
                    }
                    
                    try {
                        $table->dropForeign(['user_id']);
                    } catch (Exception $e) {
                        // Ignorer l'erreur
                    }
                });
            }

            // Revenir aux types de colonnes originaux
            if (Schema::hasTable('users')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->increments('id')->change();
                });
            }

        } catch (Exception $e) {
            \Log::warning('Migration fix_all_table_column_types rollback failed: ' . $e->getMessage());
        }
    }
};
