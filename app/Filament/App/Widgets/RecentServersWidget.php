<?php

namespace App\Filament\App\Widgets;

use App\Models\Server;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentServersWidget extends BaseWidget
{
    protected static ?string $heading = 'Serveurs récents';

    protected int | string | array $columnSpan = 2;

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $servers = $user->accessibleServers()->orderBy('updated_at', 'desc')->limit(5);

        return $table
            ->query($servers)
            ->columns([
                TextColumn::make('name')
                    ->label('Nom du serveur')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Server $record) => $record->description)
                    ->icon(fn (Server $record) => $record->condition->getIcon())
                    ->iconColor(fn (Server $record) => $record->condition->getColor()),

                TextColumn::make('allocation.address')
                    ->label('Adresse')
                    ->badge()
                    ->copyable(request()->isSecure())
                    ->state(fn (Server $record) => $record->allocation->address ?? 'Non définie'),

                TextColumn::make('condition')
                    ->label('État')
                    ->badge()
                    ->color(fn (Server $record) => $record->condition->getColor())
                    ->state(fn (Server $record) => $record->condition->getLabel()),
            ])
            ->actions([
                Action::make('manage')
                    ->label('Gérer')
                    ->icon('tabler-settings')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (Server $record) => \App\Filament\Server\Pages\Console::getUrl(panel: 'server', tenant: $record)),
            ])
            ->headerActions([
                Action::make('view_all')
                    ->label('Voir tous')
                    ->icon('tabler-arrow-right')
                    ->color('gray')
                    ->url('/app'),
            ])
            ->paginated(false)
            ->poll('30s')
            ->emptyStateIcon('tabler-server-off')
            ->emptyStateHeading('Aucun serveur')
            ->emptyStateDescription('Vous n\'avez accès à aucun serveur.');
    }
}