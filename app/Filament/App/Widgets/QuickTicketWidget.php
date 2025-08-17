<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Resources\TicketResource;
use Filament\Widgets\Widget;

class QuickTicketWidget extends Widget
{
    protected static string $view = 'filament.app.widgets.quick-ticket-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;
}