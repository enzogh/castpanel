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
        if (!Schema::hasTable('server_addons')) {
            Schema::create('server_addons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('server_id');
            $table->foreignId('addon_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version');
            $table->string('author')->nullable();
            $table->string('url')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['installed', 'updating', 'failed', 'disabled'])->default('installed');
            $table->timestamp('installation_date')->nullable();
            $table->timestamp('last_update')->nullable();
            $table->json('configuration')->nullable();
            $table->timestamps();

            $table->unique(['server_id', 'addon_id']);
            $table->index(['server_id', 'status']);
            $table->index('addon_id');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_addons');
    }
};
