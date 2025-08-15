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
    protected static ?string $navigationIcon = 'tabler-bug';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.server.pages.lua-error-logger';

    protected static ?string $pollingInterval = '5s';

    public static function getNavigationGroup(): ?string
    {
        return 'Outils et surveillance';
    }

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
    public $showResolved = true;
    public $logsPaused = false;
    public $autoScroll = true;
    public $processingError = null; // Pour afficher un indicateur de chargement sur le bouton cliqué
    public $consoleErrors = []; // Structure: ['error_key' => ['error' => {...}, 'count' => X, 'last_seen' => timestamp]
    public $lastConsoleCheck = null; // Timestamp de la dernière vérification de la console
    
    // Propriétés publiques pour la vue
    public array $stats = [
        'critical_errors' => 0,
        'warnings' => 0,
        'info' => 0,
        'total' => 0
    ];
    public $logs = [];
    
    protected ?LuaLogService $luaLogService = null;

    public function mount(): void
    {
        \Log::info('Livewire: Page mounted', [
            'server_id' => $this->getServer()->id,
            'polling_interval' => static::$pollingInterval
        ]);
        
        $this->luaLogService = app(LuaLogService::class);
        
        // Tester la connexion à la base de données
        $dbConnected = $this->testDatabaseConnection();
        if (!$dbConnected) {
            \Log::error('Livewire: Database connection failed during mount', [
                'server_id' => $this->getServer()->id
            ]);
            
            // Afficher un message d'erreur dans l'interface
            $this->dispatch('database-error', [
                'message' => 'Connexion à la base de données échouée. Vérifiez la connectivité du serveur.'
            ]);
        }
        
        // Démarrer immédiatement la surveillance
        $this->startMonitoring();
        
        // Initialiser les propriétés publiques avec des valeurs par défaut
        try {
            if ($dbConnected) {
                $this->stats = $this->getStats();
                $this->logs = $this->getLogs();
            } else {
                // Utiliser des valeurs par défaut si la DB n'est pas accessible
                $this->stats = ['critical_errors' => 0, 'warnings' => 0, 'info' => 0, 'total' => 0, 'resolved' => 0];
                $this->logs = [];
            }
        } catch (\Exception $e) {
            \Log::error('Error getting data', ['error' => $e->getMessage()]);
            $this->stats = ['critical_errors' => 0, 'warnings' => 0, 'info' => 0, 'total' => 0, 'resolved' => 0];
            $this->logs = [];
        }
        
        \Log::info('Livewire: Page mount completed', [
            'server_id' => $this->getServer()->id,
            'console_errors_count' => count($this->consoleErrors),
            'stats' => $this->stats,
            'db_connected' => $dbConnected
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
            
            \Log::info('Livewire: Logs retrieved from service', [
                'server_id' => $this->getServer()->id,
                'stored_logs_count' => count($storedLogs),
                'console_errors_count' => count($this->consoleErrors),
                'stored_logs_details' => array_map(function($log) {
                    return [
                        'id' => $log['id'] ?? 'unknown',
                        'status' => $log['status'] ?? 'unknown',
                        'message' => substr($log['message'] ?? 'unknown', 0, 50)
                    ];
                }, $storedLogs)
            ]);
            
        } catch (\Exception $e) {
            \Log::warning('Livewire: Cannot access database, using only console errors', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage()
            ]);
            
            $storedLogs = [];
        }
            
        // Convertir les erreurs de console en format de log standard
        $consoleLogs = [];
        foreach ($this->consoleErrors as $errorKey => $errorData) {
            // Filtrer strictement les erreurs de console avec status = 'open' et closed_at = null
            if (($errorData['status'] ?? 'open') !== 'open' || ($errorData['closed_at'] ?? null) !== null) {
                \Log::debug('Livewire: Skipping non-open console error', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey,
                    'status' => $errorData['status'] ?? 'unknown',
                    'closed_at' => $errorData['closed_at'] ?? 'NULL',
                    'reason' => 'Status not open or closed_at not null'
                ]);
                continue;
            }
            
            $error = $errorData['error'];
            $error['count'] = $errorData['count'];
            $error['first_seen'] = $errorData['first_seen'];
            $error['last_seen'] = $errorData['last_seen'];
            $error['resolved'] = $errorData['resolved'] ?? false;
            $error['status'] = $errorData['status'] ?? 'open';
            $error['closed_at'] = $errorData['closed_at'] ?? null;
            $error['error_key'] = $errorKey; // Ajouter la clé pour les actions
            $consoleLogs[] = $error;
        }
        
        \Log::info('Livewire: Console errors processed (open only)', [
            'server_id' => $this->getServer()->id,
            'console_errors_total' => count($this->consoleErrors),
            'console_logs_open_count' => count($consoleLogs),
            'console_logs_filtered_out' => count($this->consoleErrors) - count($consoleLogs),
            'console_logs_details' => array_map(function($log) {
                return [
                    'status' => $log['status'] ?? 'unknown',
                    'message' => substr($log['message'] ?? 'unknown', 0, 50)
                ];
            }, $consoleLogs)
        ]);
        
        // Combiner les logs stockés avec les erreurs de console
        $allLogs = array_merge($storedLogs, $consoleLogs);
        
        // S'assurer que tous les logs ont les propriétés nécessaires
        foreach ($allLogs as &$log) {
            if (!isset($log['resolved'])) {
                $log['resolved'] = false;
            }
            if (!isset($log['status'])) {
                $log['status'] = 'open';
            }
            if (!isset($log['closed_at'])) {
                $log['closed_at'] = null;
            }
        }
        unset($log);
        
        // Filtrer strictement les erreurs avec status = 'open' et closed_at = null
        $openLogs = array_filter($allLogs, function($log) {
            return ($log['status'] ?? 'open') === 'open' && ($log['closed_at'] ?? null) === null;
        });
        
        \Log::info('Livewire: Logs filtered for open status only', [
            'server_id' => $this->getServer()->id,
            'stored_logs_count' => count($storedLogs),
            'console_logs_count' => count($consoleLogs),
            'total_logs' => count($allLogs),
            'open_logs_count' => count($openLogs),
            'note' => 'Frontend filtering: only status = open AND closed_at = null'
        ]);
        
        // Utiliser uniquement les erreurs ouvertes
        $allLogs = $openLogs;
        
        // Regrouper les erreurs identiques par message et addon pour éviter les doublons
        $groupedLogs = [];
        $processedKeys = []; // Pour éviter de traiter plusieurs fois la même erreur
        
        foreach ($allLogs as $log) {
            $key = $this->createErrorKey($log);
            
            // Si cette clé a déjà été traitée, passer à la suivante
            if (in_array($key, $processedKeys)) {
                \Log::debug('Livewire: Skipping duplicate key in grouping', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $key,
                    'error_message' => $log['message'] ?? 'unknown'
                ]);
                continue;
            }
            
            if (isset($groupedLogs[$key])) {
                // Erreur déjà existante, incrémenter le compteur
                $groupedLogs[$key]['count'] = ($groupedLogs[$key]['count'] ?? 1) + ($log['count'] ?? 1);
                $groupedLogs[$key]['last_seen'] = $log['last_seen'] ?? $log['timestamp'];
                // Garder le timestamp le plus récent
                if (isset($log['timestamp']) && (!isset($groupedLogs[$key]['timestamp']) || strtotime($log['timestamp']) > strtotime($groupedLogs[$key]['timestamp']))) {
                    $groupedLogs[$key]['timestamp'] = $log['timestamp'];
                }
                // Préserver l'error_key si disponible
                if (isset($log['error_key']) && !isset($groupedLogs[$key]['error_key'])) {
                    $groupedLogs[$key]['error_key'] = $log['error_key'];
                }
            } else {
                // Nouvelle erreur, l'ajouter
                $log['count'] = $log['count'] ?? 1;
                $log['first_seen'] = $log['first_seen'] ?? $log['timestamp'];
                // S'assurer que tous les logs ont une clé d'action et une propriété resolved
                if (!isset($log['error_key'])) {
                    $log['error_key'] = $key;
                }
                if (!isset($log['resolved'])) {
                    $log['resolved'] = false;
                }
                $groupedLogs[$key] = $log;
            }
            
            // Marquer cette clé comme traitée
            $processedKeys[] = $key;
        }
        
        // Filtrer les erreurs résolues si nécessaire
        if (!$this->showResolved) {
            $groupedLogs = array_filter($groupedLogs, function($log) {
                return !($log['resolved'] ?? false);
            });
        }
        
        // Convertir en tableau indexé et trier par timestamp (plus récent en premier)
        $sortedLogs = array_values($groupedLogs);
        usort($sortedLogs, function($a, $b) {
            $timestampA = $a['last_seen'] ?? $a['timestamp'] ?? '1970-01-01';
            $timestampB = $b['last_seen'] ?? $b['timestamp'] ?? '1970-01-01';
            return strtotime($timestampB) - strtotime($timestampA);
        });
        
        \Log::info('Livewire: getLogs final result', [
            'server_id' => $this->getServer()->id,
            'grouped_logs_count' => count($groupedLogs),
            'sorted_logs_count' => count($sortedLogs),
            'final_logs_details' => array_map(function($log) {
                return [
                    'id' => $log['id'] ?? 'unknown',
                    'status' => $log['status'] ?? 'unknown',
                    'message' => substr($log['message'] ?? 'unknown', 0, 50)
                ];
            }, $sortedLogs)
        ]);
        
        return $sortedLogs;
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
                'total' => 0,
                'resolved' => 0
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
                
                // Compter les erreurs résolues
                if ($errorData['resolved'] ?? false) {
                    $consoleStats['resolved'] += $errorData['count'];
                }
            }
            
            // Combiner les stats avec vérification des clés
            $combinedStats = [
                'critical_errors' => ($storedStats['critical_errors'] ?? 0) + $consoleStats['critical_errors'],
                'warnings' => ($storedStats['warnings'] ?? 0) + $consoleStats['warnings'],
                'info' => ($storedStats['info'] ?? 0) + $consoleStats['info'],
                'total' => ($storedStats['total'] ?? 0) + $consoleStats['total'],
                'resolved' => $consoleStats['resolved']
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
        
        // Mettre à jour les logs
        $this->logs = $this->getLogs();
        
        \Log::info('Livewire: refreshLogs completed', [
            'server_id' => $this->getServer()->id,
            'logs_count' => count($this->logs)
        ]);
    }

    /**
     * Rafraîchit les données pour forcer la mise à jour de l'interface
     */
    public function refreshData(): void
    {
        try {
            \Log::info('Livewire: refreshData called', [
                'server_id' => $this->getServer()->id
            ]);
            
            $this->stats = $this->getStats();
            
            \Log::info('Livewire: refreshData completed', [
                'server_id' => $this->getServer()->id,
                'stats' => $this->stats
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Livewire: Error in refreshData', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // En cas d'erreur, essayer de récupérer au moins les stats de base
            $this->stats = [
                'critical_errors' => 0,
                'warnings' => 0,
                'info' => 0,
                'total' => 0,
                'resolved' => 0
            ];
            // Livewire va automatiquement appeler getLogs() grâce à #[Computed]
        }
    }

    public function toggleShowResolved(): void
    {
        $this->showResolved = !$this->showResolved;
        
        \Log::info('Livewire: toggleShowResolved called', [
            'server_id' => $this->getServer()->id,
            'show_resolved' => $this->showResolved
        ]);

        // Actualiser les logs avec le nouveau filtre
        // Livewire va automatiquement appeler getLogs() grâce à #[Computed]
    }
    
    public function toggleHideClosed(): void
    {
        // This method is no longer needed as hideClosed is removed.
        // Keeping it for now to avoid breaking existing calls, but it will do nothing.
        \Log::debug('Livewire: toggleHideClosed called, but hideClosed is removed. Doing nothing.');
    }

    public function monitorConsole(): void
    {
        if (!$this->logsPaused) {
            \Log::info('Livewire: Starting console monitoring', [
                'server_id' => $this->getServer()->id,
                'logs_paused' => $this->logsPaused,
                'last_check_time' => $this->lastConsoleCheck
            ]);

            try {
                // Récupérer les nouvelles erreurs depuis la console
                $newErrors = $this->getLuaLogService()->monitorConsole(
                    $this->getServer(),
                    $this->lastConsoleCheck
                );

                // Mettre à jour le timestamp de la dernière vérification
                $this->lastConsoleCheck = now()->toISOString();

                // Traiter chaque nouvelle erreur
                foreach ($newErrors as $error) {
                    // Créer une clé unique pour cette erreur (basée sur le message et l'addon)
                    $errorKey = $this->createErrorKey($error);
                    
                    // Vérifier si cette erreur existe déjà en mémoire (consoleErrors)
                    if (isset($this->consoleErrors[$errorKey])) {
                        // Erreur déjà existante en mémoire, incrémenter le compteur seulement si elle n'est pas résolue
                        if (!($this->consoleErrors[$errorKey]['resolved'] ?? false)) {
                            $this->consoleErrors[$errorKey]['count']++;
                            $this->consoleErrors[$errorKey]['last_seen'] = now()->toISOString();
                            
                            \Log::info('Livewire: Incrementing error count in memory', [
                                'server_id' => $this->getServer()->id,
                                'error_message' => $error['message'],
                                'error_addon' => $error['addon'] ?? 'unknown',
                                'new_count' => $this->consoleErrors[$errorKey]['count']
                            ]);
                        } else {
                            \Log::debug('Livewire: Skipping resolved error in memory', [
                                'server_id' => $this->getServer()->id,
                                'error_key' => $errorKey,
                                'error_message' => $error['message']
                            ]);
                        }
                    } else {
                        // Nouvelle erreur, l'ajouter avec un compteur initial
                        $this->consoleErrors[$errorKey] = [
                            'error' => $error,
                            'count' => 1,
                            'first_seen' => now()->toISOString(),
                            'last_seen' => now()->toISOString(),
                            'resolved' => false,
                            'status' => 'open',
                            'closed_at' => null
                        ];
                        
                        \Log::info('Livewire: Adding new error to memory', [
                            'server_id' => $this->getServer()->id,
                            'error_message' => $error['message'],
                            'error_addon' => $error['addon'] ?? 'unknown'
                        ]);
                        
                        // Sauvegarder l'erreur dans la base de données
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

                // Nettoyer les erreurs résolues anciennes
                $this->cleanupResolvedErrors();

            } catch (\Exception $e) {
                \Log::error('Livewire: Error in monitorConsole', [
                    'server_id' => $this->getServer()->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
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

    public function markAsResolved(string $errorKey): void
    {
        \Log::info('Livewire: markAsResolved called', [
            'server_id' => $this->getServer()->id,
            'error_key' => $errorKey,
            'console_errors_keys' => array_keys($this->consoleErrors)
        ]);
        
        $this->processingError = $errorKey;
        
        if (isset($this->consoleErrors[$errorKey])) {
            $this->consoleErrors[$errorKey]['resolved'] = true;
            // Réinitialiser le compteur quand l'erreur est résolue
            $this->consoleErrors[$errorKey]['count'] = 1;
            
            \Log::info('Livewire: Error marked as resolved in consoleErrors', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error_message' => $this->consoleErrors[$errorKey]['error']['message'] ?? 'unknown'
            ]);
            
            // Forcer la mise à jour de l'interface
            $this->dispatch('error-resolved', ['error_key' => $errorKey]);
            
            // Rafraîchir les données immédiatement
            $this->refreshData();
        } else {
            // L'erreur n'est pas dans consoleErrors, essayer de la marquer comme résolue dans les logs stockés
            \Log::info('Livewire: Error not in consoleErrors, trying to mark as resolved in stored logs', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey
            ]);
            
            // Marquer l'erreur comme résolue dans les logs stockés
            $success = $this->getLuaLogService()->markAsResolved($errorKey, $this->getServer()->id);
            
            if ($success) {
                \Log::info('Livewire: Error marked as resolved in stored logs', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);
                
                // Forcer la mise à jour de l'interface
                $this->dispatch('error-resolved', ['error_key' => $errorKey]);
                
                // Rafraîchir les données immédiatement
                $this->refreshData();
            } else {
                \Log::warning('Livewire: Failed to mark error as resolved in stored logs', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);
            }
        }
        
        $this->processingError = null;
    }

    public function markAsUnresolved(string $errorKey): void
    {
        \Log::info('Livewire: markAsUnresolved called', [
            'server_id' => $this->getServer()->id,
            'error_key' => $errorKey,
            'console_errors_keys' => array_keys($this->consoleErrors)
        ]);
        
        $this->processingError = $errorKey;
        
        if (isset($this->consoleErrors[$errorKey])) {
            $this->consoleErrors[$errorKey]['resolved'] = false;
            
            \Log::info('Livewire: Error marked as unresolved in consoleErrors', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error_message' => $this->consoleErrors[$errorKey]['error']['message'] ?? 'unknown'
            ]);
            
            // Forcer la mise à jour de l'interface
            $this->dispatch('error-unresolved', ['error_key' => $errorKey]);
            
            // Rafraîchir les données immédiatement
            $this->refreshData();
        } else {
            // L'erreur n'est pas dans consoleErrors, essayer de la marquer comme non résolue dans les logs stockés
            \Log::info('Livewire: Error not in consoleErrors, trying to mark as unresolved in stored logs', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey
            ]);
            
            // Marquer l'erreur comme non résolue dans les logs stockés
            $success = $this->getLuaLogService()->markAsUnresolved($errorKey, $this->getServer()->id);
            
            if ($success) {
                \Log::info('Livewire: Error marked as unresolved in stored logs', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);
                
                // Forcer la mise à jour de l'interface
                $this->dispatch('error-unresolved', ['error_key' => $errorKey]);
                
                // Rafraîchir les données immédiatement
                $this->refreshData();
            } else {
                \Log::warning('Livewire: Failed to mark error as unresolved in stored logs', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);
            }
        }
        
        $this->processingError = null;
    }

    public function deleteError(string $errorKey): void
    {
        \Log::info('Livewire: deleteError called', [
            'server_id' => $this->getServer()->id,
            'error_key' => $errorKey
        ]);
        
        $deletedFromConsole = false;
        $deletedFromStored = false;
        
        // 1. Supprimer de la mémoire (consoleErrors) si elle existe
        if (isset($this->consoleErrors[$errorKey])) {
            $errorMessage = $this->consoleErrors[$errorKey]['error']['message'] ?? 'unknown';
            
            // Supprimer l'erreur de la liste
            unset($this->consoleErrors[$errorKey]);
            $deletedFromConsole = true;
            
            \Log::info('Livewire: Error deleted from consoleErrors', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error_message' => $errorMessage
            ]);
        }
        
        // 2. Fermer dans la base de données (suppression soft)
        try {
            $success = $this->getLuaLogService()->deleteLog($errorKey, $this->getServer()->id);
            $deletedFromStored = $success;
            
            if ($success) {
                \Log::info('Livewire: Error closed in database', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);
            } else {
                \Log::warning('Livewire: Failed to close error in database', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Livewire: Exception while closing error in database', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);
        }
        
        // 3. Forcer la mise à jour de l'interface si au moins une suppression a réussi
        if ($deletedFromConsole || $deletedFromStored) {
            $this->dispatch('error-deleted', ['error_key' => $errorKey]);
            
            // Rafraîchir les données immédiatement
            $this->refreshData();
            
            \Log::info('Livewire: deleteError completed successfully', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'deleted_from_console' => $deletedFromConsole,
                'deleted_from_stored' => $deletedFromStored
            ]);
        } else {
            \Log::warning('Livewire: deleteError failed - error not found anywhere', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey
            ]);
        }
    }
    
    /**
     * Nettoie les erreurs résolues anciennes pour éviter l'accumulation
     */
    public function cleanupResolvedErrors(): void
    {
        $cutoffTime = now()->subHours(24); // Supprimer les erreurs résolues de plus de 24h
        $cleanedCount = 0;
        
        foreach ($this->consoleErrors as $errorKey => $errorData) {
            if (($errorData['resolved'] ?? false) && 
                isset($errorData['last_seen']) && 
                strtotime($errorData['last_seen']) < $cutoffTime->timestamp()) {
                
                unset($this->consoleErrors[$errorKey]);
                $cleanedCount++;
            }
        }
        
        if ($cleanedCount > 0) {
            \Log::info('Livewire: Cleaned up resolved errors', [
                'server_id' => $this->getServer()->id,
                'cleaned_count' => $cleanedCount
            ]);
        }
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
     * Teste la connexion à la base de données
     */
    public function testDatabaseConnection(): bool
    {
        try {
            $test = \DB::connection()->getPdo();
            \Log::info('Livewire: Database connection test successful', [
                'server_id' => $this->getServer()->id
            ]);
            return true;
        } catch (\Exception $e) {
            \Log::error('Livewire: Database connection test failed', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
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
