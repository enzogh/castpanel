<?php

namespace App\Filament\Server\Pages;

use App\Filament\Server\Widgets\ServerTicketsWidget;
use App\Filament\Server\Widgets\ServerSupportWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'Tableau de bord';

    protected static ?string $title = 'Tableau de bord';

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            ServerTicketsWidget::class,
            ServerSupportWidget::class,
        ];
    }

    public function getTitle(): string
    {
        $server = request()->route('tenant');
        $serverName = $server ? \App\Models\Server::find($server)?->name : 'Serveur';
        
        return "Dashboard - {$serverName}";
    }

    public function getHeading(): string
    {
        return $this->getTitle();
    }

    public function getSubheading(): ?string
    {
        $server = request()->route('tenant');
        $serverObj = $server ? \App\Models\Server::find($server) : null;
        
        if ($serverObj) {
            $status = $serverObj->status?->value ?? 'unknown';
            $statusText = match($status) {
                'running' => '🟢 En ligne',
                'stopped' => '🔴 Arrêté',
                'starting' => '🟡 Démarrage...',
                'stopping' => '🟡 Arrêt...',
                default => '⚫ Statut inconnu'
            };
            
            return "Statut : {$statusText} • Gérez votre serveur et obtenez de l'aide";
        }
        
        return 'Gérez votre serveur et obtenez de l\'aide';
    }
}