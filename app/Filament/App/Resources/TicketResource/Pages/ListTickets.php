<?php

namespace App\Filament\App\Resources\TicketResource\Pages;

use App\Filament\App\Resources\TicketResource;
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
                ->modalHeading('Créer un nouveau ticket')
                ->modalDescription('Décrivez votre problème ou votre demande, notre équipe vous répondra rapidement.')
                ->modalWidth('2xl'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TicketResource\Widgets\TicketStatsWidget::class,
        ];
    }
}