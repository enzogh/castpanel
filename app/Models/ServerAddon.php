<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerAddon extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'addon_id',
        'name',
        'description',
        'version',
        'author',
        'url',
        'file_path',
        'status',
        'installation_date',
        'last_update',
        'configuration',
    ];

    protected $casts = [
        'installation_date' => 'datetime',
        'last_update' => 'datetime',
        'configuration' => 'array',
    ];

    public const STATUS_INSTALLED = 'installed';
    public const STATUS_UPDATING = 'updating';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DISABLED = 'disabled';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_INSTALLED => 'Installé',
            self::STATUS_UPDATING => 'Mise à jour',
            self::STATUS_FAILED => 'Échec',
            self::STATUS_DISABLED => 'Désactivé',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class);
    }

    public function isInstalled(): bool
    {
        return $this->status === self::STATUS_INSTALLED;
    }

    public function isUpdating(): bool
    {
        return $this->status === self::STATUS_UPDATING;
    }

    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isDisabled(): bool
    {
        return $this->status === self::STATUS_DISABLED;
    }
}