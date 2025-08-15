<?php

namespace App\Filament\Server\Widgets;

use App\Models\Server;
use App\Services\Servers\LuaLogService;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LuaLogStatsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected static bool $isLazy = false;

    public function getStats(): array
    {
        /** @var Server $server */
        $server = Filament::getTenant();
        
        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return [];
        }

        $luaLogService = app(LuaLogService::class);
        $stats = $luaLogService->getLogStats($server);

        return [
            Stat::make('Erreurs critiques', $stats['critical_errors'])
                ->description('Erreurs Lua détectées')
                ->descriptionIcon('tabler-alert-circle')
                ->color('danger')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href = "' . route('filament.server.pages.lua-error-logger', ['tenant' => $server]) . '"'
                ]),

            Stat::make('Avertissements', $stats['warnings'])
                ->description('Avertissements détectés')
                ->descriptionIcon('tabler-alert-triangle')
                ->color('warning')
                ->chart([17, 16, 14, 15, 14, 13, 12])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href = "' . route('filament.server.pages.lua-error-logger', ['tenant' => $server]) . '"'
                ]),

            Stat::make('Total des logs', $stats['total'])
                ->description('Tous les logs collectés')
                ->descriptionIcon('tabler-file-text')
                ->color('info')
                ->chart([15, 4, 10, 2, 12, 4, 12])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'onclick' => 'window.location.href = "' . route('filament.server.pages.lua-error-logger', ['tenant' => $server]) . '"'
                ]),
        ];
    }

    public static function canView(): bool
    {
        /** @var Server $server */
        $server = Filament::getTenant();
        
        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return false;
        }
        
        // Vérifier les permissions
        return auth()->user()->can('view server', $server);
    }
}
