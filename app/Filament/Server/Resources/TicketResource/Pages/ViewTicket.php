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
            Actions\Action::make('back_to_list')
                ->label('Retour aux tickets')
                ->icon('heroicon-o-arrow-left')
                ->url($this->getResource()::getUrl('index', ['tenant' => request()->route('tenant')]))
                ->color('gray'),
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