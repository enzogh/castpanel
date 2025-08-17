<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Resources\ServerResource\Pages\ListServers;
use App\Models\Server;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Enums\Alignment;
use App\Filament\Components\Tables\Columns\ServerEntryColumn;

class ServersListWidget extends BaseWidget
{
    protected static ?string $heading = 'Mes serveurs';

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    public function table(Table $table): Table
    {
        $user = auth()->user();
        $servers = $user->accessibleServers()->limit(6);

        return $table
            ->query($servers)
            ->columns([
                Stack::make([
                    ServerEntryColumn::make('server_entry')
                        ->searchable(['name']),
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->actions([
                Action::make('view')
                    ->label('Gérer')
                    ->icon('tabler-settings')
                    ->color('primary')
                    ->url(fn (Server $record) => \App\Filament\Server\Pages\Console::getUrl(panel: 'server', tenant: $record)),
            ])
            ->headerActions([
                Action::make('view_all')
                    ->label('Voir tous les serveurs')
                    ->icon('tabler-arrow-right')
                    ->color('gray')
                    ->url('/app'),
            ])
            ->paginated(false)
            ->poll('15s')
            ->emptyStateIcon('tabler-server-off')
            ->emptyStateHeading('Aucun serveur')
            ->emptyStateDescription('Vous n\'avez accès à aucun serveur pour le moment.');
    }
}