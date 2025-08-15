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
        'status',
        'resolved',
        'resolved_at',
        'closed_at',
        'resolution_notes',
    ];

    protected $casts = [
        'status' => 'string',
        'resolved' => 'boolean',
        'first_seen' => 'datetime',
        'last_seen' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'count' => 'integer',
    ];

    // Constantes pour les statuts
    const STATUS_OPEN = 'open';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

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
     * Scope pour les erreurs ouvertes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope pour les erreurs résolues
     */
    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    /**
     * Scope pour les erreurs fermées
     */
    public function scopeClosed($query)
    {
        return $query->where('status', self::STATUS_CLOSED);
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
            'status' => self::STATUS_RESOLVED,
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
            'status' => self::STATUS_OPEN,
            'resolved' => false,
            'resolved_at' => null,
        ]);
    }

    /**
     * Marquer comme fermé
     */
    public function markAsClosed(?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_CLOSED,
            'resolved' => true,
            'closed_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Rouvrir une erreur fermée
     */
    public function reopen(): void
    {
        $this->update([
            'status' => self::STATUS_OPEN,
            'resolved' => false,
            'resolved_at' => null,
            'closed_at' => null,
            'resolution_notes' => null,
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
                'status' => self::STATUS_OPEN,
                'resolved' => false,
                'resolved_at' => null,
                'closed_at' => null,
                'resolution_notes' => null,
            ]
        );
    }
}
