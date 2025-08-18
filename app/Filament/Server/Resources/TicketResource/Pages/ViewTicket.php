<?php

namespace App\Filament\Server\Resources\TicketResource\Pages;

use App\Filament\Server\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        $record = parent::resolveRecord($key);
        
        // Log pour débogage
        Log::info('ViewTicket::resolveRecord', [
            'key' => $key,
            'record_found' => $record ? true : false,
            'record_id' => $record ? $record->id : null,
            'user_id' => auth()->id(),
            'server_id' => request()->route('tenant'),
        ]);
        
        // Si le record n'est pas trouvé, essayer de le récupérer directement SANS filtres
        if (!$record) {
            Log::warning('Ticket not found via parent::resolveRecord, trying direct query without filters');
            
            // FORCER la récupération du ticket sans filtres pour déboguer
            $record = Ticket::where('id', $key)
                ->with(['server', 'assignedTo', 'messages'])
                ->first();
                
            Log::info('Direct query result (no filters)', [
                'ticket_found' => $record ? true : false,
                'ticket_id' => $record ? $record->id : null,
                'ticket_data' => $record ? [
                    'id' => $record->id,
                    'title' => $record->title,
                    'user_id' => $record->user_id,
                    'server_id' => $record->server_id,
                ] : null,
            ]);
            
            // Si toujours pas de record, créer un record vide pour éviter l'erreur
            if (!$record) {
                Log::error('Ticket still not found, creating empty record to prevent error');
                $record = new Ticket();
                $record->id = $key;
                $record->exists = false; // Marquer comme non existant
            }
        }
        
        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        if (!$this->record) {
            return "Ticket #{$this->getRecordKey()} - Non trouvé";
        }
        
        return "Ticket #{$this->record->id} - {$this->record->title}";
    }

    public function getSubheading(): ?string
    {
        if (!$this->record || !$this->record->server) {
            return "Serveur : Non trouvé";
        }
        
        return "Serveur : {$this->record->server->name}";
    }
}