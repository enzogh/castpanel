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
        if (!Schema::hasTable('addons')) {
            Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('version');
            $table->string('author');
            $table->enum('category', ['gameplay', 'administration', 'ui', 'api', 'utility', 'cosmetic']);
            $table->json('tags')->nullable();
            $table->string('download_url');
            $table->string('repository_url')->nullable();
            $table->string('documentation_url')->nullable();
            $table->string('image_url')->nullable();
            $table->bigInteger('file_size')->nullable();
            $table->integer('downloads_count')->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('requires_config')->default(false);
            $table->json('supported_games');
            $table->json('requirements')->nullable();
            $table->text('installation_instructions')->nullable();
            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['is_featured', 'is_active']);
            $table->index('downloads_count');
            $table->index('rating');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addons');
    }
};
