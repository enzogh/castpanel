<?php

namespace App\Filament\Server\Pages;

use App\Models\Permission;
use App\Models\Server;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\ActionSize;

class LuaErrorLogger extends Page
{
    protected static ?string $navigationIcon = 'tabler-file-text';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.server.pages.lua-error-logger';

    public static function canAccess(): bool
    {
        /** @var Server $server */
        $server = Filament::getTenant();
        
        // Vérifier si c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return false;
        }
        
        // Vérifier les permissions
        return auth()->user()->can(Permission::ACTION_FILE_READ, $server);
    }

    public static function getNavigationLabel(): string
    {
        return 'Logger d\'erreur Lua';
    }

    public function getTitle(): string
    {
        return 'Logger d\'erreur Lua';
    }

    public function getSubheading(): string
    {
        return 'Surveillez et analysez les erreurs Lua de votre serveur Garry\'s Mod';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('tabler-refresh')
                ->color('primary')
                ->action(fn () => $this->refreshLogs()),
            ActionGroup::make([
                Action::make('clear_logs')
                    ->label('Effacer les logs')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => $this->clearLogs()),
                Action::make('export_logs')
                    ->label('Exporter les logs')
                    ->icon('tabler-download')
                    ->color('success')
                    ->action(fn () => $this->exportLogs()),
            ])
                ->label('Actions')
                ->icon('tabler-dots-vertical')
                ->color('gray'),
        ];
    }

    public function refreshLogs(): void
    {
        // Logique pour actualiser les logs
        $this->dispatch('logs-refreshed');
    }

    public function clearLogs(): void
    {
        // Logique pour effacer les logs
        $this->dispatch('logs-cleared');
    }

    public function exportLogs(): void
    {
        // Logique pour exporter les logs
        $this->dispatch('logs-exported');
    }
}
