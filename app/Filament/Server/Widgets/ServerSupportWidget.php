<?php

namespace App\Filament\Server\Widgets;

use Filament\Widgets\Widget;

class ServerSupportWidget extends Widget
{
    protected static string $view = 'filament.server.widgets.server-support-widget';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    public function getViewData(): array
    {
        $serverId = request()->route('tenant');
        $server = \App\Models\Server::find($serverId);
        
        return [
            'server' => $server,
            'serverId' => $serverId,
        ];
    }
}