<?php

namespace App\Filament\Pages;

use App\Services\Servers\LuaConsoleHookService;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LuaConsoleDaemonControl extends Page
{
    protected static ?string $navigationIcon = 'tabler-server';
    protected static ?string $navigationGroup = 'Surveillance Lua';
    protected static ?string $title = 'Contrôle du Daemon Lua Console';
    protected static ?string $slug = 'lua-console-daemon';
    protected static ?int $navigationSort = 1;

    public bool $isDaemonRunning = false;
    public ?string $daemonPid = null;
    public ?string $daemonStatus = 'stopped';
    public int $pollingInterval = 5;
    public bool $autoRefresh = true;

    protected ?LuaConsoleHookService $hookService = null;

    public function mount(): void
    {
        $this->checkDaemonStatus();
    }

    public function getTitle(): string
    {
        return 'Contrôle du Daemon Lua Console';
    }

    public function getSubheading(): string
    {
        return 'Gérez le service de surveillance automatique des erreurs Lua';
    }

    public function checkDaemonStatus(): void
    {
        $pidFile = storage_path('lua-console-hook.pid');
        
        if (file_exists($pidFile)) {
            $this->daemonPid = file_get_contents($pidFile);
            $this->isDaemonRunning = $this->daemonPid && posix_kill($this->daemonPid, 0);
            $this->daemonStatus = $this->isDaemonRunning ? 'running' : 'dead';
        } else {
            $this->daemonPid = null;
            $this->isDaemonRunning = false;
            $this->daemonStatus = 'stopped';
        }
    }

    public function startDaemon(): void
    {
        try {
            $this->hookService = app(LuaConsoleHookService::class);
            $this->hookService->setCheckInterval($this->pollingInterval);
            $this->hookService->startHooking();
            
            $this->checkDaemonStatus();
            
            Notification::make()
                ->title('Daemon démarré')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to start daemon', ['error' => $e->getMessage()]);
            
            Notification::make()
                ->title('Erreur lors du démarrage')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function stopDaemon(): void
    {
        try {
            $this->hookService = app(LuaConsoleHookService::class);
            $this->hookService->stopDaemon();
            
            $this->checkDaemonStatus();
            
            Notification::make()
                ->title('Daemon arrêté')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to stop daemon', ['error' => $e->getMessage()]);
            
            Notification::make()
                ->title('Erreur lors de l\'arrêt')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function restartDaemon(): void
    {
        try {
            $this->stopDaemon();
            sleep(2);
            $this->startDaemon();
            
            Notification::make()
                ->title('Daemon redémarré')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Log::error('Failed to restart daemon', ['error' => $e->getMessage()]);
            
            Notification::make()
                ->title('Erreur lors du redémarrage')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function setPollingInterval(int $interval): void
    {
        $this->pollingInterval = max(1, min(60, $interval));
        
        if ($this->isDaemonRunning) {
            $this->restartDaemon();
        }
    }

    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('tabler-refresh')
                ->color('primary')
                ->action(fn () => $this->checkDaemonStatus()),
        ];
    }

    protected function getViewData(): array
    {
        return [
            'isDaemonRunning' => $this->isDaemonRunning,
            'daemonPid' => $this->daemonPid,
            'daemonStatus' => $this->daemonStatus,
            'pollingInterval' => $this->pollingInterval,
            'autoRefresh' => $this->autoRefresh,
        ];
    }
}
