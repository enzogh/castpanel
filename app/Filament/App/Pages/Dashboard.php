<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'tabler-layout-dashboard';

    protected static string $view = 'filament.app.pages.dashboard';

    protected static ?string $title = 'Tableau de bord';

    public function getColumns(): int
    {
        return 1;
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
