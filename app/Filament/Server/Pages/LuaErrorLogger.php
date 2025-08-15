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

    // Méthode appelée automatiquement par Livewire pour le polling
    public function updated($property): void
    {
        \Log::info('Livewire: Property updated', [
            'server_id' => $this->getServer()->id,
            'property' => $property,
            'timestamp' => now()->toISOString()
        ]);
    }

    public $search = '';
    public $levelFilter = '';
    public $timeFilter = '24h';
    public $logsPaused = false;
    public $autoScroll = true;
    public $consoleErrors = []; // Structure: ['error_key' => ['error' => {...}, 'count' => X, 'last_seen' => timestamp]]
    public $lastConsoleCheck = null; // Timestamp de la dernière vérification de la console
    
    // Propriétés publiques pour la vue
    public array $stats = [
        'critical_errors' => 0,
        'warnings' => 0,
        'info' => 0,
        'total' => 0
    ];
    public array $logs = [];

    protected ?LuaLogService $luaLogService = null;

    public function mount(): void
    {
        \Log::info('Livewire: Page mounted', [
            'server_id' => $this->getServer()->id,
            'polling_interval' => static::$pollingInterval
        ]);
        
        $this->luaLogService = app(LuaLogService::class);
        
        // Démarrer immédiatement la surveillance
        $this->startMonitoring();
        
        // Initialiser les propriétés publiques avec des valeurs par défaut
        try {
            $this->stats = $this->getStats();
        } catch (\Exception $e) {
            \Log::error('Error getting stats', ['error' => $e->getMessage()]);
            $this->stats = ['critical_errors' => 0, 'warnings' => 0, 'info' => 0, 'total' => 0];
        }
        
        try {
            $this->logs = $this->getLogs();
        } catch (\Exception $e) {
            \Log::error('Error getting logs', ['error' => $e->getMessage()]);
            $this->logs = [];
        }
        

        
        \Log::info('Livewire: Page mount completed', [
            'server_id' => $this->getServer()->id,
            'console_errors_count' => count($this->consoleErrors),
            'stats' => $this->stats
        ]);
    }

    public function startMonitoring(): void
    {
        \Log::info('Livewire: Starting initial monitoring', [
            'server_id' => $this->getServer()->id
        ]);
        
        // Initialiser le timestamp de la première vérification
        $this->lastConsoleCheck = now()->toISOString();
        
        // Première surveillance immédiate (sans historique)
        $this->monitorConsole();
        
        // Programmer la surveillance toutes les 5 secondes
        $this->dispatch('start-polling');
    }

    protected function getLuaLogService(): LuaLogService
    {
        \Log::debug('Livewire: getLuaLogService called', [
            'server_id' => $this->getServer()->id,
            'service_exists' => $this->luaLogService ? 'yes' : 'no'
        ]);
        
        if (!$this->luaLogService) {
            $this->luaLogService = app(LuaLogService::class);
            \Log::debug('Livewire: LuaLogService created', [
                'server_id' => $this->getServer()->id
            ]);
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
        $server = Filament::getTenant();
        \Log::debug('Livewire: getServer called', [
            'server_id' => $server->id,
            'egg_name' => $server->egg->name ?? 'no egg'
        ]);
        return $server;
    }

    public function getLogs(): array
    {
        \Log::debug('Livewire: getLogs computed property called', [
            'server_id' => $this->getServer()->id,
            'filters' => [
                'level' => $this->levelFilter,
                'search' => $this->search,
                'time' => $this->timeFilter,
            ],
            'console_errors_count' => count($this->consoleErrors)
        ]);

        $filters = [
            'level' => $this->levelFilter,
            'search' => $this->search,
            'time' => $this->timeFilter,
        ];

        try {
            $storedLogs = $this->getLuaLogService()->getLogs($this->getServer(), $filters);
            
            // Convertir les erreurs de console en format de log standard
            $consoleLogs = [];
            foreach ($this->consoleErrors as $errorKey => $errorData) {
                $error = $errorData['error'];
                $error['count'] = $errorData['count'];
                $error['first_seen'] = $errorData['first_seen'];
                $error['last_seen'] = $errorData['last_seen'];
                $consoleLogs[] = $error;
            }
            
            // Combiner avec les erreurs de console en temps réel
            $allLogs = array_merge($storedLogs, $consoleLogs);
            
            // Regrouper les erreurs identiques par message et addon
            $groupedLogs = [];
            foreach ($allLogs as $log) {
                $key = $this->createErrorKey($log);
                
                if (isset($groupedLogs[$key])) {
                    // Erreur déjà existante, incrémenter le compteur
                    $groupedLogs[$key]['count']++;
                    $groupedLogs[$key]['last_seen'] = $log['last_seen'] ?? $log['timestamp'];
                    // Garder le timestamp le plus récent
                    if (isset($log['timestamp']) && (!isset($groupedLogs[$key]['timestamp']) || strtotime($log['timestamp']) > strtotime($groupedLogs[$key]['timestamp']))) {
                        $groupedLogs[$key]['timestamp'] = $log['timestamp'];
                    }
                } else {
                    // Nouvelle erreur, l'ajouter
                    $log['count'] = 1;
                    $log['first_seen'] = $log['timestamp'];
                    $groupedLogs[$key] = $log;
                }
            }
            
            // Convertir en tableau indexé et trier par timestamp (plus récent en premier)
            $finalLogs = array_values($groupedLogs);
            usort($finalLogs, function($a, $b) {
                $timestampA = $a['last_seen'] ?? $a['timestamp'] ?? '';
                $timestampB = $b['last_seen'] ?? $b['timestamp'] ?? '';
                return strtotime($timestampB) - strtotime($timestampA);
            });
            
            \Log::debug('Livewire: getLogs computed property completed', [
                'server_id' => $this->getServer()->id,
                'stored_logs_count' => count($storedLogs),
                'console_logs_count' => count($consoleLogs),
                'grouped_logs_count' => count($finalLogs)
            ]);
            
            return $finalLogs;
        } catch (\Exception $e) {
            \Log::error('Livewire: Error in getLogs', [
                'server_id' => $this->getServer()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function getStats(): array
    {
        try {
            // Récupérer les stats des logs stockés
            $storedStats = $this->getLuaLogService()->getLogStats($this->getServer());
            
            // Vérifier que storedStats est un tableau
            if (!is_array($storedStats)) {
                \Log::warning('Livewire: getLogStats returned non-array', ['returned' => $storedStats]);
                $storedStats = ['critical_errors' => 0, 'warnings' => 0, 'info' => 0, 'total' => 0];
            }
            
            // Calculer les stats des erreurs de console en temps réel
            $consoleStats = [
                'critical_errors' => 0,
                'warnings' => 0,
                'info' => 0,
                'total' => 0
            ];
            
            foreach ($this->consoleErrors as $errorKey => $errorData) {
                $error = $errorData['error'];
                $level = $error['level'] ?? 'error';
                
                switch ($level) {
                    case 'error':
                        $consoleStats['critical_errors'] += $errorData['count'];
                        break;
                    case 'warning':
                        $consoleStats['warnings'] += $errorData['count'];
                        break;
                    case 'info':
                        $consoleStats['info'] += $errorData['count'];
                        break;
                }
                
                $consoleStats['total'] += $errorData['count'];
            }
            
            // Combiner les stats avec vérification des clés
            $combinedStats = [
                'critical_errors' => ($storedStats['critical_errors'] ?? 0) + $consoleStats['critical_errors'],
                'warnings' => ($storedStats['warnings'] ?? 0) + $consoleStats['warnings'],
                'info' => ($storedStats['info'] ?? 0) + $consoleStats['info'],
                'total' => ($storedStats['total'] ?? 0) + $consoleStats['total']
            ];
            
            \Log::debug('Livewire: getStats called', [
                'server_id' => $this->getServer()->id,
                'stored_stats' => $storedStats,
                'console_stats' => $consoleStats,
                'combined_stats' => $combinedStats
            ]);
            
            return $combinedStats;
        } catch (\Exception $e) {
            \Log::error('Livewire: Error in getStats', [
                'server_id' => $this->getServer()->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            return [
                'critical_errors' => 0,
                'warnings' => 0,
                'info' => 0,
                'total' => 0
            ];
        }
    }



    public function refreshLogs(): void
    {
        \Log::info('Livewire: refreshLogs called', [
            'server_id' => $this->getServer()->id,
            'timestamp' => now()->toISOString(),
            'last_check_time' => $this->lastConsoleCheck
        ]);
        
        // Surveiller la console pour de nouvelles erreurs (depuis la dernière vérification)
        $this->monitorConsole();
        $this->dispatch('logs-refreshed');
        
        \Log::info('Livewire: refreshLogs completed', [
            'server_id' => $this->getServer()->id
        ]);
    }

    public function monitorConsole(): void
    {
        \Log::info('Livewire: monitorConsole called', [
            'server_id' => $this->getServer()->id,
            'logs_paused' => $this->logsPaused,
            'timestamp' => now()->toISOString()
        ]);
        
        if (!$this->logsPaused) {
            \Log::info('Livewire: Starting console monitoring', [
                'server_id' => $this->getServer()->id,
                'logs_paused' => $this->logsPaused,
                'last_check_time' => $this->lastConsoleCheck
            ]);
            
            $newErrors = $this->getLuaLogService()->monitorConsole($this->getServer(), $this->lastConsoleCheck);
            
            // Mettre à jour le timestamp de la dernière vérification
            $this->lastConsoleCheck = now()->toISOString();
            
            \Log::info('Livewire: Console monitoring completed', [
                'server_id' => $this->getServer()->id,
                'new_errors_count' => count($newErrors),
                'new_check_time' => $this->lastConsoleCheck
            ]);
            
            foreach ($newErrors as $error) {
                // Créer une clé unique pour cette erreur (basée sur le message et l'addon)
                $errorKey = $this->createErrorKey($error);
                
                if (isset($this->consoleErrors[$errorKey])) {
                    // Erreur déjà existante, incrémenter le compteur
                    $this->consoleErrors[$errorKey]['count']++;
                    $this->consoleErrors[$errorKey]['last_seen'] = now()->toISOString();
                    
                    \Log::info('Livewire: Incrementing error count', [
                        'server_id' => $this->getServer()->id,
                        'error_message' => $error['message'],
                        'error_addon' => $error['addon'] ?? 'unknown',
                        'new_count' => $this->consoleErrors[$errorKey]['count']
                    ]);
                } else {
                    // Nouvelle erreur, l'ajouter avec un compteur initial
                    $this->consoleErrors[$errorKey] = [
                        'error' => $error,
                        'count' => 1,
                        'first_seen' => now()->toISOString(),
                        'last_seen' => now()->toISOString()
                    ];
                    
                    \Log::info('Livewire: Adding new error', [
                        'server_id' => $this->getServer()->id,
                        'error_message' => $error['message'],
                        'error_addon' => $error['addon'] ?? 'unknown'
                    ]);
                    
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
        } else {
            \Log::debug('Livewire: Console monitoring paused', [
                'server_id' => $this->getServer()->id
            ]);
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

    public function togglePause(): void
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
            'default' => 'application/json',
        };
    }

    /**
     * Crée une clé unique pour une erreur basée sur son message et son addon
     */
    private function createErrorKey(array $error): string
    {
        $message = $error['message'] ?? '';
        $addon = $error['addon'] ?? 'unknown';
        
        // Créer une clé unique en combinant le message et l'addon
        return md5($message . '|' . $addon);
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
