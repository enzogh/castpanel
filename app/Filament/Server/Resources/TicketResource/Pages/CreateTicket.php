<?php

namespace App\Filament\Server\Resources\TicketResource\Pages;

use App\Filament\Server\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateTicket extends CreateRecord
{
    protected static string $resource = TicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $serverId = request()->route('tenant');
        
        $data['user_id'] = auth()->id();
        $data['server_id'] = $serverId;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record, 'tenant' => request()->route('tenant')]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        $server = \App\Models\Server::find(request()->route('tenant'));
        $serverName = $server?->name ?? 'ce serveur';
        
        return Notification::make()
            ->success()
            ->title('Ticket créé avec succès')
            ->body("Votre ticket #{$this->record->id} pour {$serverName} a été créé. Notre équipe vous répondra rapidement.")
            ->persistent();
    }

    public function getTitle(): string
    {
        $server = \App\Models\Server::find(request()->route('tenant'));
        $serverName = $server?->name ?? 'Serveur';
        
        return "Nouveau ticket - {$serverName}";
    }
}