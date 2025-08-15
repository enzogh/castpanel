<?php

namespace App\Filament\Server\Pages;

use App\Models\Server;
use App\Services\Servers\LuaLogService;
use App\Services\Servers\LuaConsoleMonitorService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LuaErrorLogger extends Page
{
    protected static string $resource = Server::class;

    protected static string $view = 'filament.server.pages.lua-error-logger';

    public bool $logsPaused = false;
    public bool $showResolved = false;
    public string $search = '';
    public string $levelFilter = 'all';
    public string $timeFilter = 'all';

    protected ?LuaLogService $luaLogService = null;

    public function mount(): void
    {
        Log::info('Livewire: LuaErrorLogger page mounted', [
            'server_id' => $this->getServer()->id
        ]);
        
        // Démarrer la surveillance automatique de la console
        $this->startConsoleMonitoring();
    }

    public function getTitle(): string
    {
        return 'Logger d\'erreur Lua';
    }

    public function getSubheading(): string
    {
        return 'Surveillez et analysez les erreurs Lua de votre serveur Garry\'s Mod';
    }

    public static function canAccess(): bool
    {
        /** @var Server $server */
        $server = \Filament\Facades\Filament::getTenant();
        
        // Vérifier si c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return false;
        }
        
        // Vérifier les permissions
        return auth()->user()->can(\App\Models\Permission::ACTION_FILE_READ, $server);
    }

    public static function getNavigationLabel(): string
    {
        return 'Logger d\'erreur Lua';
    }

    #[Computed]
    public function getServer(): Server
    {
        return \Filament\Facades\Filament::getTenant();
    }

    #[Computed]
    public function getLogs(): array
    {
        try {
            $service = app(LuaLogService::class);
            $logs = $service->getLogs($this->getServer(), [
                'search' => $this->search,
                'level' => $this->levelFilter,
                'time' => $this->timeFilter,
                'show_resolved' => $this->showResolved
            ]);

            Log::info('Livewire: Logs retrieved successfully', [
                'server_id' => $this->getServer()->id,
                'logs_count' => count($logs)
            ]);

            return $logs;

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to retrieve logs', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    public function markAsResolved(string $errorKey): void
    {
        try {
            $service = app(LuaLogService::class);
            $service->markAsResolved($errorKey, $this->getServer()->id);
            
            Log::info('Livewire: Error marked as resolved', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey
            ]);

            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to mark error as resolved', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteError(string $errorKey): void
    {
        try {
            $service = app(LuaLogService::class);
            $service->deleteLog($errorKey, $this->getServer()->id);
            
            Log::info('Livewire: Error deleted', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey
            ]);

            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to delete error', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function refreshLogs(): void
    {
        $this->dispatch('$refresh');
    }

    public function clearLogs(): void
    {
        try {
            $service = app(LuaLogService::class);
            $service->clearLogs($this->getServer());
            
            Log::info('Livewire: Logs cleared', [
                'server_id' => $this->getServer()->id
            ]);

            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to clear logs', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function exportLogs(string $format): void
    {
        try {
            $service = app(LuaLogService::class);
            $service->exportLogs($this->getServer(), $format);
            
            Log::info('Livewire: Logs exported', [
                'server_id' => $this->getServer()->id,
                'format' => $format
            ]);

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to export logs', [
                'server_id' => $this->getServer()->id,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Démarre la surveillance automatique de la console
     */
    public function startConsoleMonitoring(): void
    {
        try {
            $monitorService = app(LuaConsoleMonitorService::class);
            $newErrors = $monitorService->monitorConsole($this->getServer());
            
            if (is_array($newErrors) && count($newErrors) > 0) {
                Log::info('Livewire: Console monitoring started, new errors found', [
                    'server_id' => $this->getServer()->id,
                    'new_errors_count' => count($newErrors)
                ]);
                
                // Forcer le refresh de l'interface
                $this->dispatch('$refresh');
            } else {
                Log::info('Livewire: Console monitoring completed, no new errors', [
                    'server_id' => $this->getServer()->id,
                    'new_errors_type' => gettype($newErrors),
                    'new_errors_count' => is_array($newErrors) ? count($newErrors) : 'not array'
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Livewire: Failed to start console monitoring', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Méthode appelée pour la surveillance en temps réel
     */
    public function monitorConsole(): void
    {
        $this->startConsoleMonitoring();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualiser')
                ->icon('tabler-refresh')
                ->color('primary')
                ->action(fn () => $this->refreshLogs()),
            ActionGroup::make([
                Action::make('clear_logs')
                    ->label('Effacer les logs')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn () => $this->clearLogs()),
                Action::make('export_json')
                    ->label('Exporter en JSON')
                    ->icon('tabler-file-code')
                    ->color('success')
                    ->action(fn () => $this->exportLogs('json')),
                Action::make('export_csv')
                    ->label('Exporter en CSV')
                    ->icon('tabler-file-spreadsheet')
                    ->color('success')
                    ->action(fn () => $this->exportLogs('csv')),
                Action::make('export_txt')
                    ->label('Exporter en TXT')
                    ->icon('tabler-file-text')
                    ->color('success')
                    ->action(fn () => $this->exportLogs('txt')),
            ])
                ->label('Actions')
                ->icon('tabler-dots-vertical')
                ->color('gray'),
        ];
    }
}
