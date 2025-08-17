<?php

namespace App\Filament\Server\Resources\InstalledAddonResource\Pages;

use App\Filament\Server\Resources\InstalledAddonResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListInstalledAddons extends ListRecords
{
    protected static string $resource = InstalledAddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('browse_addons')
                ->label('Parcourir les addons')
                ->icon('heroicon-o-magnifying-glass')
                ->url(fn () => route('filament.server.resources.addons.index', ['tenant' => filament()->getTenant()]))
                ->color('primary'),
        ];
    }
}