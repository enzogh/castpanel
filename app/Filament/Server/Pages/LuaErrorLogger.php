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
    public int $pollingInterval = 5; // 5 secondes au lieu de 30
    public bool $isMonitoring = false;
    public ?string $lastConsoleCheck = null;

    protected ?LuaLogService $luaLogService = null;

    public function mount(): void
    {
        Log::info('Livewire: LuaErrorLogger page mounted', [
            'server_id' => $this->getServer()->id
        ]);
        
        // Démarrer la surveillance automatique de la console de manière sécurisée
        try {
            $this->startConsoleMonitoring();
        } catch (\Exception $e) {
            Log::error('Livewire: Failed to start console monitoring during mount', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage()
            ]);
        }
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

    public static function getNavigationGroup(): ?string
    {
        return 'Outils et surveillance';
    }

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationIcon = 'tabler-bug';

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
                'logs_count' => count($logs),
                'show_resolved' => $this->showResolved,
                'filters' => [
                    'search' => $this->search,
                    'level' => $this->levelFilter,
                    'time' => $this->timeFilter
                ]
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
        if ($this->logsPaused) {
            Log::info('Livewire: Console monitoring skipped - logs paused', [
                'server_id' => $this->getServer()->id
            ]);
            return;
        }

        try {
            $this->isMonitoring = true;
            $this->lastConsoleCheck = now()->toISOString();
            
            $monitorService = app(LuaConsoleMonitorService::class);
            $newErrors = $monitorService->monitorConsole($this->getServer());
            
            if (is_array($newErrors) && count($newErrors) > 0) {
                Log::info('Livewire: Console monitoring completed, new errors found', [
                    'server_id' => $this->getServer()->id,
                    'new_errors_count' => count($newErrors),
                    'timestamp' => $this->lastConsoleCheck
                ]);
                
                // Forcer le refresh de l'interface
                $this->dispatch('$refresh');
            } else {
                Log::info('Livewire: Console monitoring completed, no new errors', [
                    'server_id' => $this->getServer()->id,
                    'new_errors_type' => gettype($newErrors),
                    'new_errors_count' => is_array($newErrors) ? count($newErrors) : 'not array',
                    'timestamp' => $this->lastConsoleCheck
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Livewire: Failed to start console monitoring', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isMonitoring = false;
        }
    }

    /**
     * Méthode appelée pour la surveillance en temps réel
     */
    public function monitorConsole(): void
    {
        Log::info('Livewire: Polling triggered - monitoring console', [
            'server_id' => $this->getServer()->id,
            'timestamp' => now()->toISOString()
        ]);
        
        $this->startConsoleMonitoring();
    }

    /**
     * Bascule l'affichage des erreurs résolues
     */
    public function toggleShowResolved(): void
    {
        $this->showResolved = !$this->showResolved;
        
        Log::info('Livewire: Toggle show resolved', [
            'server_id' => $this->getServer()->id,
            'show_resolved' => $this->showResolved
        ]);
        
        // Forcer le refresh de l'interface
        $this->dispatch('$refresh');
    }

    /**
     * Marque une erreur comme non résolue
     */
    public function markAsUnresolved(string $errorKey): void
    {
        try {
            $service = app(LuaLogService::class);
            $service->markAsUnresolved($errorKey, $this->getServer()->id);
            
            Log::info('Livewire: Error marked as unresolved', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey
            ]);

            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to mark error as unresolved', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Bascule la pause de la surveillance
     */
    public function togglePause(): void
    {
        $this->logsPaused = !$this->logsPaused;
        
        Log::info('Livewire: Toggle pause', [
            'server_id' => $this->getServer()->id,
            'logs_paused' => $this->logsPaused
        ]);
        
        // Forcer le refresh de l'interface
        $this->dispatch('$refresh');
    }

    /**
     * Ajuste l'intervalle de polling
     */
    public function setPollingInterval(int $interval): void
    {
        $this->pollingInterval = max(1, min(60, $interval)); // Entre 1 et 60 secondes
        
        Log::info('Livewire: Polling interval updated', [
            'server_id' => $this->getServer()->id,
            'new_interval' => $this->pollingInterval
        ]);
        
        $this->dispatch('$refresh');
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
