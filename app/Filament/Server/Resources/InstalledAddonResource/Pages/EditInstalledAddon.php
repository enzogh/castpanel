<?php

namespace App\Filament\Server\Resources\InstalledAddonResource\Pages;

use App\Filament\Server\Resources\InstalledAddonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstalledAddon extends EditRecord
{
    protected static string $resource = InstalledAddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}