<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'server_id',
        'title',
        'description',
        'status',
        'priority',
        'category',
        'assigned_to',
        'closed_at',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_PENDING = 'pending';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    public const CATEGORY_TECHNICAL = 'technical';
    public const CATEGORY_BILLING = 'billing';
    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_FEATURE_REQUEST = 'feature_request';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Ouvert',
            self::STATUS_IN_PROGRESS => 'En cours',
            self::STATUS_PENDING => 'En attente',
            self::STATUS_RESOLVED => 'Résolu',
            self::STATUS_CLOSED => 'Fermé',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Faible',
            self::PRIORITY_MEDIUM => 'Moyen',
            self::PRIORITY_HIGH => 'Élevé',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    public static function getCategories(): array
    {
        return [
            self::CATEGORY_TECHNICAL => 'Technique',
            self::CATEGORY_BILLING => 'Facturation',
            self::CATEGORY_GENERAL => 'Général',
            self::CATEGORY_FEATURE_REQUEST => 'Demande de fonctionnalité',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_PENDING]);
    }

    /**
     * Créer un ticket par défaut si aucun n'existe
     */
    public static function createDefaultTicketIfNeeded(): ?self
    {
        // Vérifier si des tickets existent déjà
        if (self::count() > 0) {
            return null;
        }

        try {
            // Récupérer le premier utilisateur et serveur
            $user = \App\Models\User::first();
            $server = \App\Models\Server::first();

            if (!$user || !$server) {
                return null;
            }

            // Créer un ticket par défaut
            $ticket = self::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'title' => 'Bienvenue dans le système de tickets',
                'description' => 'Ce ticket a été créé automatiquement pour vous permettre de commencer à utiliser le système de support.',
                'status' => self::STATUS_OPEN,
                'priority' => self::PRIORITY_LOW,
                'category' => self::CATEGORY_GENERAL,
            ]);

            // Créer un message initial
            \App\Models\TicketMessage::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'message' => 'Bienvenue ! Ce ticket a été créé automatiquement.',
                'is_internal' => false,
            ]);

            return $ticket;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED]);
    }
}