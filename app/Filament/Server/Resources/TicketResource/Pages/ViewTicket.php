<?php

namespace App\Filament\Server\Resources\TicketResource\Pages;

use App\Filament\Server\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return "Ticket #{$this->record->id} - {$this->record->title}";
    }

    public function getSubheading(): ?string
    {
        return "Serveur : {$this->record->server->name}";
    }
}