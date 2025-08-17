<?php

namespace App\Filament\App\Resources\ServerResource\Widgets;

use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $allServers = $user->accessibleServers();
        
        $runningServers = $allServers->filter(function ($server) {
            return $server->retrieveStatus()->isRunning();
        });
        
        $stoppedServers = $allServers->filter(function ($server) {
            return $server->retrieveStatus()->isStopped();
        });

        $totalMemoryUsage = $allServers->sum(function ($server) {
            $resources = $server->retrieveResources();
            return $resources['memory_bytes'] ?? 0;
        });

        $totalMemoryLimit = $allServers->sum('memory') * 1024 * 1024; // Convert MB to bytes

        return [
            Stat::make('Serveurs en ligne', $runningServers->count())
                ->description('Sur ' . $allServers->count() . ' total')
                ->descriptionIcon('tabler-power')
                ->color('success'),

            Stat::make('Serveurs arrêtés', $stoppedServers->count())
                ->description('Serveurs actuellement inactifs')
                ->descriptionIcon('tabler-player-stop')
                ->color('gray'),

            Stat::make('Mémoire totale utilisée', convert_bytes_to_readable($totalMemoryUsage))
                ->description('Sur ' . convert_bytes_to_readable($totalMemoryLimit) . ' allouée')
                ->descriptionIcon('tabler-device-desktop-analytics')
                ->color('info'),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }
}