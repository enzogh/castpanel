<?php

namespace App\Filament\Server\Pages;

use App\Models\Permission;
use App\Models\Server;
use App\Services\Servers\LuaLogService;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Support\Enums\ActionSize;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class LuaErrorLogger extends Page
{
    protected static ?string $navigationIcon = 'tabler-file-text';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.server.pages.lua-error-logger';

    protected static ?string $pollingInterval = '5s';

    public $search = '';
    public $levelFilter = '';
    public $timeFilter = '24h';
    public $logsPaused = false;
    public $autoScroll = true;
    public $consoleErrors = [];

    protected ?LuaLogService $luaLogService = null;

    public function mount(): void
    {
        $this->luaLogService = app(LuaLogService::class);
    }

    protected function getLuaLogService(): LuaLogService
    {
        if (!$this->luaLogService) {
            $this->luaLogService = app(LuaLogService::class);
        }
        return $this->luaLogService;
    }

    public static function canAccess(): bool
    {
        /** @var Server $server */
        $server = Filament::getTenant();
        
        // Vérifier si c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return false;
        }
        
        // Vérifier les permissions
        return auth()->user()->can(Permission::ACTION_FILE_READ, $server);
    }

    public static function getNavigationLabel(): string
    {
        return 'Logger d\'erreur Lua';
    }

    public function getTitle(): string
    {
        return 'Logger d\'erreur Lua';
    }

    public function getSubheading(): string
    {
        return 'Surveillez et analysez les erreurs Lua de votre serveur Garry\'s Mod';
    }

    #[Computed]
    public function getServer(): Server
    {
        return Filament::getTenant();
    }

    #[Computed]
    public function getLogs(): array
    {
        $filters = [
            'level' => $this->levelFilter,
            'search' => $this->search,
            'time' => $this->timeFilter,
        ];

        $storedLogs = $this->getLuaLogService()->getLogs($this->getServer(), $filters);
        
        // Combiner avec les erreurs de console en temps réel
        $allLogs = array_merge($storedLogs, $this->consoleErrors);
        
        // Trier par timestamp (plus récent en premier)
        usort($allLogs, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return $allLogs;
    }

    #[Computed]
    public function getStats(): array
    {
        return $this->getLuaLogService()->getLogStats($this->getServer());
    }

    #[Computed]
    public function getTopAddonErrors(): array
    {
        return $this->getLuaLogService()->getTopAddonErrors($this->getServer(), 10);
    }

    #[Computed]
    public function getTopErrorTypes(): array
    {
        return $this->getLuaLogService()->getTopErrorTypes($this->getServer(), 10);
    }

    public function refreshLogs(): void
    {
        // Surveiller la console pour de nouvelles erreurs
        $this->monitorConsole();
        $this->dispatch('logs-refreshed');
    }

    public function monitorConsole(): void
    {
        if (!$this->logsPaused) {
            $newErrors = $this->getLuaLogService()->monitorConsole($this->getServer());
            
            foreach ($newErrors as $error) {
                // Vérifier si l'erreur n'existe pas déjà
                $exists = false;
                foreach ($this->consoleErrors as $existingError) {
                    if ($existingError['message'] === $error['message'] && 
                        $existingError['timestamp'] === $error['timestamp']) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $this->consoleErrors[] = $error;
                    
                    // Sauvegarder l'erreur dans le fichier de log
                    $this->getLuaLogService()->addLog(
                        $this->getServer(),
                        $error['level'],
                        $error['message'],
                        $error['addon'],
                        $error['stack_trace']
                    );
                }
            }
            
            // Limiter le nombre d'erreurs en mémoire
            if (count($this->consoleErrors) > 100) {
                $this->consoleErrors = array_slice($this->consoleErrors, -100);
            }
        }
    }

    public function clearLogs(): void
    {
        $success = $this->getLuaLogService()->clearLogs($this->getServer());
        
        if ($success) {
            $this->dispatch('logs-cleared');
        }
    }

    public function exportLogs(string $format = 'json'): void
    {
        $exportData = $this->getLuaLogService()->exportLogs($this->getServer(), $format);
        
        if (!empty($exportData)) {
            $filename = "lua_logs_server_{$this->getServer()->id}_" . now()->format('Y-m-d_H-i-s') . ".{$format}";
            
            $this->dispatch('download-file', [
                'content' => $exportData,
                'filename' => $filename,
                'contentType' => $this->getContentType($format)
            ]);
        }
    }

    public function toggleAutoScroll(): void
    {
        $this->autoScroll = !$this->autoScroll;
    }

    public function togglePauseLogs(): void
    {
        $this->logsPaused = !$this->logsPaused;
    }

    public function updatedSearch(): void
    {
        // La recherche se met à jour automatiquement grâce aux computed properties
    }

    public function updatedLevelFilter(): void
    {
        // Le filtre se met à jour automatiquement grâce aux computed properties
    }

    public function updatedTimeFilter(): void
    {
        // Le filtre de temps se met à jour automatiquement grâce aux computed properties
    }

    private function getContentType(string $format): string
    {
        return match($format) {
            'csv' => 'text/csv',
            'txt' => 'text/plain',
            default => 'application/json',
        };
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
