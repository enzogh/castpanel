<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'is_active',
        'is_pinned',
        'author_id',
        'target_users',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_pinned' => 'boolean',
        'target_users' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public const TYPE_INFO = 'info';
    public const TYPE_WARNING = 'warning';
    public const TYPE_SUCCESS = 'success';
    public const TYPE_DANGER = 'danger';
    public const TYPE_MAINTENANCE = 'maintenance';

    public const TARGET_ALL = 'all';
    public const TARGET_ADMINS = 'admins';
    public const TARGET_USERS = 'users';
    public const TARGET_SPECIFIC = 'specific';

    public static function getTypes(): array
    {
        return [
            self::TYPE_INFO => 'Information',
            self::TYPE_WARNING => 'Avertissement',
            self::TYPE_SUCCESS => 'Succès',
            self::TYPE_DANGER => 'Danger',
            self::TYPE_MAINTENANCE => 'Maintenance',
        ];
    }

    public static function getTargets(): array
    {
        return [
            self::TARGET_ALL => 'Tous les utilisateurs',
            self::TARGET_ADMINS => 'Administrateurs uniquement',
            self::TARGET_USERS => 'Utilisateurs uniquement',
            self::TARGET_SPECIFIC => 'Utilisateurs spécifiques',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function isVisible(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->start_at && $now->isBefore($this->start_at)) {
            return false;
        }

        if ($this->end_at && $now->isAfter($this->end_at)) {
            return false;
        }

        return true;
    }

    public function isVisibleForUser(User $user): bool
    {
        if (!$this->isVisible()) {
            return false;
        }

        return match ($this->target_users) {
            self::TARGET_ALL => true,
            self::TARGET_ADMINS => $user->hasAnyRole(['admin', 'super-admin']),
            self::TARGET_USERS => !$user->hasAnyRole(['admin', 'super-admin']),
            self::TARGET_SPECIFIC => is_array($this->target_users) && in_array($user->id, $this->target_users),
            default => false,
        };
    }

    public function scopeVisible($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}