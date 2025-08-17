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
        if (!Schema::hasTable('tickets')) {
            Schema::create('tickets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('server_id')->nullable();
                $table->string('title');
                $table->text('description');
                $table->enum('status', ['open', 'in_progress', 'pending', 'resolved', 'closed'])->default('open');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->enum('category', ['technical', 'billing', 'general', 'feature_request'])->default('general');
                $table->unsignedBigInteger('assigned_to')->nullable();
                $table->timestamp('closed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index(['assigned_to', 'status']);
                $table->index('server_id');
            });
        } else {
            // Si la table existe déjà, ajoutons seulement les colonnes manquantes
            Schema::table('tickets', function (Blueprint $table) {
                // Vérifier et ajouter les colonnes une par une
                if (!Schema::hasColumn('tickets', 'id')) {
                    $table->id();
                }
                if (!Schema::hasColumn('tickets', 'user_id')) {
                    $table->unsignedBigInteger('user_id');
                }
                if (!Schema::hasColumn('tickets', 'server_id')) {
                    $table->unsignedBigInteger('server_id')->nullable();
                }
                if (!Schema::hasColumn('tickets', 'title')) {
                    $table->string('title');
                }
                if (!Schema::hasColumn('tickets', 'description')) {
                    $table->text('description');
                }
                if (!Schema::hasColumn('tickets', 'status')) {
                    $table->enum('status', ['open', 'in_progress', 'pending', 'resolved', 'closed'])->default('open');
                }
                if (!Schema::hasColumn('tickets', 'priority')) {
                    $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                }
                if (!Schema::hasColumn('tickets', 'category')) {
                    $table->enum('category', ['technical', 'billing', 'general', 'feature_request'])->default('general');
                }
                if (!Schema::hasColumn('tickets', 'assigned_to')) {
                    $table->unsignedBigInteger('assigned_to')->nullable();
                }
                if (!Schema::hasColumn('tickets', 'closed_at')) {
                    $table->timestamp('closed_at')->nullable();
                }
                if (!Schema::hasColumn('tickets', 'created_at')) {
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
        Schema::dropIfExists('tickets');
    }
};
