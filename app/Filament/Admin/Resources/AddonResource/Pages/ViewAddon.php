<?php

namespace App\Filament\Admin\Resources\AddonResource\Pages;

use App\Filament\Admin\Resources\AddonResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAddon extends ViewRecord
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}