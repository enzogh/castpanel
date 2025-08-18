<?php

namespace App\Filament\Server\Resources\TicketResource\Pages;

use App\Filament\Server\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function resolveRecord(string|int $key): \Illuminate\Database\Eloquent\Model
    {
        try {
            // Récupérer le ticket normalement
            $record = Ticket::where('id', $key)
                ->with(['server', 'assignedTo', 'messages'])
                ->first();
            
            if (!$record) {
                Log::warning('Ticket not found', ['key' => $key]);
                // Retourner un ticket vide
                $record = new Ticket();
                $record->id = $key;
                $record->exists = false;
            }
            
            return $record;
            
        } catch (\Exception $e) {
            Log::error('Error in resolveRecord', [
                'error' => $e->getMessage(),
                'key' => $key,
            ]);
            
            // Retourner un ticket vide en cas d'erreur
            $record = new Ticket();
            $record->id = $key;
            $record->exists = false;
            return $record;
        }
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