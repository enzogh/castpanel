<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'version',
        'author',
        'category',
        'tags',
        'download_url',
        'repository_url',
        'documentation_url',
        'image_url',
        'file_size',
        'downloads_count',
        'rating',
        'is_active',
        'is_featured',
        'requires_config',
        'supported_games',
        'requirements',
        'installation_instructions',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'requires_config' => 'boolean',
        'tags' => 'array',
        'supported_games' => 'array',
        'requirements' => 'array',
    ];

    public const CATEGORY_GAMEPLAY = 'gameplay';
    public const CATEGORY_ADMINISTRATION = 'administration';
    public const CATEGORY_UI = 'ui';
    public const CATEGORY_API = 'api';
    public const CATEGORY_UTILITY = 'utility';
    public const CATEGORY_COSMETIC = 'cosmetic';

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_GAMEPLAY => 'Gameplay',
            self::CATEGORY_ADMINISTRATION => 'Administration',
            self::CATEGORY_UI => 'Interface Utilisateur',
            self::CATEGORY_API => 'API',
            self::CATEGORY_UTILITY => 'Utilitaire',
            self::CATEGORY_COSMETIC => 'Cosmétique',
        ];
    }

    public function serverAddons(): HasMany
    {
        return $this->hasMany(ServerAddon::class);
    }

    public function servers(): BelongsToMany
    {
        return $this->belongsToMany(Server::class, 'server_addons')
            ->withPivot(['status', 'installation_date', 'configuration'])
            ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForGame($query, string $game)
    {
        return $query->whereJsonContains('supported_games', $game);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }
}