<?php

namespace App\Notifications;

use App\Models\LuaError;
use App\Models\Server;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LuaErrorDetected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Server $server,
        public LuaError $luaError
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $errorMessage = substr($this->luaError->message, 0, 100);
        
        return (new MailMessage)
            ->subject("🚨 Erreur Lua détectée sur {$this->server->name}")
            ->greeting("Bonjour {$notifiable->username},")
            ->line("Une nouvelle erreur Lua a été détectée sur votre serveur **{$this->server->name}**.")
            ->line("**Erreur:** {$errorMessage}")
            ->line("**Addon:** {$this->luaError->addon}")
            ->line("**Première occurrence:** " . $this->luaError->first_seen->format('d/m/Y H:i:s'))
            ->action('Voir les détails', url("/server/{$this->server->id}/lua-error-logger"))
            ->line('Cette erreur a été détectée automatiquement par notre système de surveillance.')
            ->salutation('Cordialement, l\'équipe CastPanel');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'server_id' => $this->server->id,
            'server_name' => $this->server->name,
            'error_id' => $this->luaError->id,
            'error_message' => $this->luaError->message,
            'addon' => $this->luaError->addon,
            'first_seen' => $this->luaError->first_seen,
            'count' => $this->luaError->count,
        ];
    }
}
