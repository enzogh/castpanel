<?php

namespace App\Filament\Pages;

use App\Models\LuaError;
use App\Models\Server;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class LuaErrorDashboard extends Page
{
    protected static ?string $navigationIcon = 'tabler-chart-line';
    protected static ?string $navigationGroup = 'Surveillance Lua';
    protected static ?string $title = 'Dashboard des Erreurs Lua';
    protected static ?string $slug = 'lua-error-dashboard';
    protected static ?int $navigationSort = 2;

    public function getTitle(): string
    {
        return 'Dashboard des Erreurs Lua';
    }

    public function getSubheading(): string
    {
        return 'Vue d\'ensemble de toutes les erreurs Lua de vos serveurs GMod';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LuaErrorStats::class,
        ];
    }

    protected function getViewData(): array
    {
        return [
            'totalErrors' => $this->getTotalErrors(),
            'openErrors' => $this->getOpenErrors(),
            'resolvedErrors' => $this->getResolvedErrors(),
            'recentErrors' => $this->getRecentErrors(),
            'serverErrors' => $this->getServerErrors(),
        ];
    }

    private function getTotalErrors(): int
    {
        return LuaError::count();
    }

    private function getOpenErrors(): int
    {
        return LuaError::where('status', 'open')
            ->orWhere(function($query) {
                $query->where('status', '!=', 'closed')
                      ->where('resolved', false);
            })
            ->count();
    }

    private function getResolvedErrors(): int
    {
        return LuaError::where('resolved', true)->count();
    }

    private function getRecentErrors(): array
    {
        return LuaError::with('server')
            ->orderBy('last_seen', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getServerErrors(): array
    {
        return DB::table('lua_errors')
            ->join('servers', 'lua_errors.server_id', '=', 'servers.id')
            ->select('servers.name', 'servers.id', DB::raw('COUNT(*) as error_count'))
            ->groupBy('servers.id', 'servers.name')
            ->orderBy('error_count', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}

class LuaErrorStats extends BaseWidget
{
    protected function getStats(): array
    {
        $totalErrors = LuaError::count();
        $openErrors = LuaError::where('status', 'open')
            ->orWhere(function($query) {
                $query->where('status', '!=', 'closed')
                      ->where('resolved', false);
            })
            ->count();
        $resolvedErrors = LuaError::where('resolved', true)->count();
        $todayErrors = LuaError::where('last_seen', '>=', now()->startOfDay())->count();

        return [
            Stat::make('Total des erreurs', $totalErrors)
                ->description('Toutes les erreurs détectées')
                ->descriptionIcon('tabler-bug')
                ->color('gray'),

            Stat::make('Erreurs ouvertes', $openErrors)
                ->description('Erreurs non résolues')
                ->descriptionIcon('tabler-alert-circle')
                ->color('danger'),

            Stat::make('Erreurs résolues', $resolvedErrors)
                ->description('Erreurs corrigées')
                ->descriptionIcon('tabler-check-circle')
                ->color('success'),

            Stat::make('Erreurs aujourd\'hui', $todayErrors)
                ->description('Nouvelles erreurs du jour')
                ->descriptionIcon('tabler-clock')
                ->color('warning'),
        ];
    }
}
