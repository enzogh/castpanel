<?php

namespace App\Filament\Server\Resources\AddonResource\Pages;

use App\Filament\Server\Resources\AddonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAddons extends ListRecords
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh')
                ->label('Actualiser')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->resetTable();
                }),
            
            Actions\Action::make('browse_workshop')
                ->label('Parcourir Workshop')
                ->icon('heroicon-o-globe-alt')
                ->url('https://steamcommunity.com/workshop/browse/?appid=4000')
                ->openUrlInNewTab(),
        ];
    }
}