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
        Schema::create('lua_errors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->string('error_key', 32)->unique(); // MD5 hash unique
            $table->string('level')->default('ERROR');
            $table->text('message');
            $table->string('addon')->nullable();
            $table->text('stack_trace')->nullable();
            $table->integer('count')->default(1);
            $table->timestamp('first_seen');
            $table->timestamp('last_seen');
            $table->enum('status', ['open', 'resolved', 'closed'])->default('open'); // Statut explicite
            $table->boolean('resolved')->default(false); // Garder pour compatibilité
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable(); // Quand l'erreur a été fermée
            $table->text('resolution_notes')->nullable(); // Notes sur la résolution
            $table->timestamps();
            
            // Index pour les performances
            $table->index(['server_id', 'status']);
            $table->index(['server_id', 'resolved']);
            $table->index(['error_key']);
            $table->index(['first_seen']);
            $table->index(['last_seen']);
            
            // Pas de contrainte de clé étrangère pour éviter les problèmes de compatibilité
            // La relation sera gérée au niveau de l'application
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lua_errors');
    }
};
