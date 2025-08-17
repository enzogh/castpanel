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
            // Vérifier si la table tickets existe
            if (Schema::hasTable('tickets')) {
                // Modifier la structure de la table tickets si nécessaire
                Schema::table('tickets', function (Blueprint $table) {
                    // Vérifier et modifier le type de la colonne user_id si nécessaire
                    if (Schema::hasColumn('tickets', 'user_id')) {
                        // Modifier le type de la colonne user_id pour qu'elle soit compatible avec users.id
                        $table->unsignedBigInteger('user_id')->change();
                    }
                    
                    // Vérifier et modifier le type de la colonne server_id si nécessaire
                    if (Schema::hasColumn('tickets', 'server_id')) {
                        $table->unsignedBigInteger('server_id')->nullable()->change();
                    }
                    
                    // Vérifier et modifier le type de la colonne assigned_to si nécessaire
                    if (Schema::hasColumn('tickets', 'assigned_to')) {
                        $table->unsignedBigInteger('assigned_to')->nullable()->change();
                    }
                });
                
                // Ajouter les contraintes de clé étrangère
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
        } catch (Exception $e) {
            // Log l'erreur mais ne pas faire échouer la migration
            \Log::warning('Migration fix_tickets_table_structure failed: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (Schema::hasTable('tickets')) {
                Schema::table('tickets', function (Blueprint $table) {
                    // Supprimer les contraintes de clé étrangère
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
        } catch (Exception $e) {
            \Log::warning('Migration fix_tickets_table_structure rollback failed: ' . $e->getMessage());
        }
    }
};
