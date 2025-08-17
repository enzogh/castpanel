<?php

namespace App\Filament\App\Pages;

use App\Filament\App\Widgets\AnnouncementsWidget;
use App\Filament\App\Widgets\QuickTicketWidget;
use App\Filament\App\Resources\TicketResource\Widgets\TicketStatsWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'tabler-layout-dashboard';

    protected static ?string $title = 'Tableau de bord';
    
    protected static string $routePath = '/dashboard';

    public function getColumns(): int | string | array
    {
        return 2;
    }

    public function getWidgets(): array
    {
        return [
            AnnouncementsWidget::class,
            TicketStatsWidget::class,
            QuickTicketWidget::class,
        ];
    }

    public function getHeading(): string
    {
        return trans('app/dashboard.heading', ['default' => 'Tableau de bord']);
    }

    public function getSubheading(): string
    {
        return trans('app/dashboard.subheading', ['default' => 'Bienvenue dans votre espace personnel']);
    }
}
