<?php

namespace App\Filament\Server\Resources\TicketResource\Pages;

use App\Filament\Server\Resources\TicketResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau ticket')
                ->icon('heroicon-o-plus')
                ->modalHeading('Créer un ticket de support')
                ->modalDescription('Décrivez votre problème concernant ce serveur, notre équipe vous aidera rapidement.')
                ->modalWidth('2xl'),
        ];
    }

    public function getTitle(): string
    {
        $server = request()->route('tenant');
        $serverName = $server ? \App\Models\Server::find($server)?->name : 'Serveur';
        
        return "Support - {$serverName}";
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        return 'Gérez vos tickets de support pour ce serveur';
    }
}