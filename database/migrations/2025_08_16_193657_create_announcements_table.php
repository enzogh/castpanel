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
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('type', ['info', 'warning', 'success', 'danger', 'maintenance'])->default('info');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pinned')->default(false);
            $table->unsignedBigInteger('author_id');
            $table->string('target_users')->default('all');
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'is_pinned']);
            $table->index(['start_at', 'end_at']);
            // Index et contrainte de clé étrangère seront ajoutés par la migration de correction
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
