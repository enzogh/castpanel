<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Added this import for DB facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lua_errors', function (Blueprint $table) {
            // Ajouter le champ status avec une valeur par défaut
            $table->enum('status', ['open', 'resolved', 'closed'])->default('open')->after('last_seen');
            
            // Ajouter le champ closed_at
            $table->timestamp('closed_at')->nullable()->after('resolved_at');
            
            // Ajouter le champ resolution_notes
            $table->text('resolution_notes')->nullable()->after('closed_at');
            
            // Ajouter un index sur le champ status
            $table->index(['server_id', 'status']);
        });
        
        // Mettre à jour les erreurs existantes pour avoir le bon statut
        DB::statement("UPDATE lua_errors SET status = CASE WHEN resolved = 1 THEN 'resolved' ELSE 'open' END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lua_errors', function (Blueprint $table) {
            // Supprimer l'index
            $table->dropIndex(['server_id', 'status']);
            
            // Supprimer les nouveaux champs
            $table->dropColumn(['status', 'closed_at', 'resolution_notes']);
        });
    }
};
