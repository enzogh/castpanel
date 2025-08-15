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
        Schema::table('lua_errors', function (Blueprint $table) {
            // Ajouter le champ deleted_at pour la suppression soft
            $table->timestamp('deleted_at')->nullable()->after('resolution_notes');
            
            // Ajouter un index sur deleted_at pour les performances
            $table->index(['deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lua_errors', function (Blueprint $table) {
            // Supprimer l'index
            $table->dropIndex(['deleted_at']);
            
            // Supprimer le champ
            $table->dropColumn('deleted_at');
        });
    }
};
