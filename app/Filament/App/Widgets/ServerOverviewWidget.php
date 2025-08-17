<?php

namespace App\Filament\App\Widgets;

use App\Models\Server;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $allServers = $user->accessibleServers()->get();
        $myServers = $allServers->where('owner_id', $user->id);
        $onlineServers = $allServers->filter(function ($server) {
            try {
                return $server->retrieveStatus() === \App\Enums\ContainerStatus::Running;
            } catch (\Exception $e) {
                return false;
            }
        });

        return [
            Stat::make('Mes serveurs', $myServers->count())
                ->description('Serveurs que vous possédez')
                ->descriptionIcon('tabler-server')
                ->color('primary')
                ->url('/app'),

            Stat::make('Serveurs accessibles', $allServers->count())
                ->description('Total des serveurs accessibles')
                ->descriptionIcon('tabler-database')
                ->color('info')
                ->url('/app'),

            Stat::make('Serveurs en ligne', $onlineServers->count())
                ->description('Serveurs actuellement actifs')
                ->descriptionIcon('tabler-power')
                ->color('success'),

            Stat::make('Tickets ouverts', $user->tickets()->where('status', 'open')->count())
                ->description('Tickets en cours de traitement')
                ->descriptionIcon('tabler-ticket')
                ->color('warning')
                ->url('/app/tickets'),
        ];
    }

    protected function getColumns(): int
    {
        return 4;
    }
}