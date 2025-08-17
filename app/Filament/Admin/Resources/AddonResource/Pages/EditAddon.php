<?php

namespace App\Filament\Admin\Resources\AddonResource\Pages;

use App\Filament\Admin\Resources\AddonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAddon extends EditRecord
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}