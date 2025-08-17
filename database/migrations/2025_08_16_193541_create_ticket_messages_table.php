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
        if (!Schema::hasTable('ticket_messages')) {
            Schema::create('ticket_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ticket_id');
                $table->unsignedBigInteger('user_id');
                $table->text('message');
                $table->boolean('is_internal')->default(false);
                $table->json('attachments')->nullable();
                $table->timestamps();

                $table->index(['ticket_id', 'created_at']);
                $table->index('user_id');
            });
        } else {
            // Si la table existe déjà, ajoutons seulement les colonnes manquantes
            Schema::table('ticket_messages', function (Blueprint $table) {
                // Vérifier et ajouter les colonnes une par une
                if (!Schema::hasColumn('ticket_messages', 'id')) {
                    $table->id();
                }
                if (!Schema::hasColumn('ticket_messages', 'ticket_id')) {
                    $table->unsignedBigInteger('ticket_id');
                }
                if (!Schema::hasColumn('ticket_messages', 'user_id')) {
                    $table->unsignedBigInteger('user_id');
                }
                if (!Schema::hasColumn('ticket_messages', 'message')) {
                    $table->text('message');
                }
                if (!Schema::hasColumn('ticket_messages', 'is_internal')) {
                    $table->boolean('is_internal')->default(false);
                }
                if (!Schema::hasColumn('ticket_messages', 'attachments')) {
                    $table->json('attachments')->nullable();
                }
                if (!Schema::hasColumn('ticket_messages', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
    }
};
