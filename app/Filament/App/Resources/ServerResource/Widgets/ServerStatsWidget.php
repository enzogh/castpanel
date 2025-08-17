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
        $allServers = $user->accessibleServers()->get();
        
        $runningServers = $allServers->filter(function ($server) {
            try {
                return $server->retrieveStatus() === \App\Enums\ContainerStatus::Running;
            } catch (\Exception $e) {
                return false;
            }
        });
        
        $stoppedServers = $allServers->filter(function ($server) {
            try {
                $status = $server->retrieveStatus();
                return in_array($status, [
                    \App\Enums\ContainerStatus::Exited,
                    \App\Enums\ContainerStatus::Offline,
                    \App\Enums\ContainerStatus::Dead
                ]);
            } catch (\Exception $e) {
                return false;
            }
        });

        $totalMemoryUsage = $allServers->sum(function ($server) {
            try {
                $resources = $server->retrieveResources();
                return $resources['memory_bytes'] ?? 0;
            } catch (\Exception $e) {
                return 0;
            }
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