<?php

namespace App\Filament\Server\Widgets;

use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServerTicketsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $serverId = request()->route('tenant');
        $userId = auth()->id();
        
        if (!$serverId) {
            return [];
        }
        
        $totalTickets = Ticket::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->count();
            
        $openTickets = Ticket::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING])
            ->count();
            
        $urgentTickets = Ticket::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->where('priority', Ticket::PRIORITY_URGENT)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS])
            ->count();

        $resolvedTickets = Ticket::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->count();

        $server = \App\Models\Server::find($serverId);
        $serverName = $server?->name ?? 'ce serveur';

        return [
            Stat::make('Tickets ouverts', $openTickets)
                ->description("Tickets en cours pour {$serverName}")
                ->descriptionIcon('heroicon-m-clock')
                ->color($openTickets > 0 ? ($urgentTickets > 0 ? 'danger' : 'warning') : 'success')
                ->url(\App\Filament\Server\Resources\TicketResource::getUrl('index', ['tenant' => $serverId])),
            
            Stat::make('Tickets urgents', $urgentTickets)
                ->description('Nécessitent une attention immédiate')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($urgentTickets > 0 ? 'danger' : 'success'),
            
            Stat::make('Total des tickets', $totalTickets)
                ->description('Tous vos tickets pour ce serveur')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),
            
            Stat::make('Tickets résolus', $resolvedTickets)
                ->description('Problèmes résolus')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }

    public static function canView(): bool
    {
        $serverId = request()->route('tenant');
        $userId = auth()->id();
        
        if (!$serverId) {
            return false;
        }
        
        // Afficher le widget s'il y a des tickets ou pour encourager la création
        return Ticket::where('user_id', $userId)
            ->where('server_id', $serverId)
            ->exists() || true; // Toujours afficher pour inciter à créer des tickets
    }
}