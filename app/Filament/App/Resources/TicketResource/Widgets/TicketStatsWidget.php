<?php

namespace App\Filament\App\Resources\TicketResource\Widgets;

use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TicketStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = auth()->id();
        
        $totalTickets = Ticket::where('user_id', $userId)->count();
        $openTickets = Ticket::where('user_id', $userId)
            ->whereIn('status', [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING])
            ->count();
        $resolvedTickets = Ticket::where('user_id', $userId)
            ->where('status', Ticket::STATUS_RESOLVED)
            ->count();
        
        return [
            Stat::make('Total des tickets', $totalTickets)
                ->description('Tous vos tickets')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('primary'),
            
            Stat::make('Tickets ouverts', $openTickets)
                ->description('En cours de traitement')
                ->descriptionIcon('heroicon-m-clock')
                ->color($openTickets > 0 ? 'warning' : 'success'),
            
            Stat::make('Tickets résolus', $resolvedTickets)
                ->description('Problèmes résolus')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}