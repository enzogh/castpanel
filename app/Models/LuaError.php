<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LuaError extends Model
{
    use HasFactory;

    protected $fillable = [
        'server_id',
        'error_key',
        'level',
        'message',
        'addon',
        'stack_trace',
        'count',
        'first_seen',
        'last_seen',
        'resolved',
        'resolved_at',
    ];

    protected $casts = [
        'resolved' => 'boolean',
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
        'resolved_at' => 'datetime',
        'count' => 'integer',
    ];

    /**
     * Relation avec le serveur
     */
    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    /**
     * Scope pour les erreurs non résolues
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * Scope pour les erreurs résolues
     */
    public function scopeResolved($query)
    {
        return $query->where('resolved', true);
    }

    /**
     * Scope pour un serveur spécifique
     */
    public function scopeForServer($query, $serverId)
    {
        return $query->where('server_id', $serverId);
    }

    /**
     * Marquer comme résolu
     */
    public function markAsResolved(): void
    {
        $this->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
    }

    /**
     * Marquer comme non résolu
     */
    public function markAsUnresolved(): void
    {
        $this->update([
            'resolved' => false,
            'resolved_at' => null,
        ]);
    }

    /**
     * Incrémenter le compteur et mettre à jour last_seen
     */
    public function incrementCount(): void
    {
        $this->increment('count');
        $this->update(['last_seen' => now()]);
    }

    /**
     * Créer ou mettre à jour une erreur
     */
    public static function createOrUpdate(
        int $serverId,
        string $errorKey,
        string $message,
        ?string $addon = null,
        ?string $stackTrace = null,
        string $level = 'ERROR'
    ): self {
        return static::updateOrCreate(
            ['error_key' => $errorKey],
            [
                'server_id' => $serverId,
                'level' => $level,
                'message' => $message,
                'addon' => $addon,
                'stack_trace' => $stackTrace,
                'first_seen' => now(),
                'last_seen' => now(),
                'count' => 1,
                'resolved' => false,
                'resolved_at' => null,
            ]
        );
    }
}
