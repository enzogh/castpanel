<?php

namespace App\Filament\Server\Pages;

use App\Models\Server;
use App\Models\LuaError;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Component;

class LuaErrorLogger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = Server::class;

    protected static string $view = 'filament.server.pages.lua-error-logger';

    public bool $showResolved = false;
    public string $search = '';
    public string $levelFilter = 'all';
    public string $timeFilter = 'all';
    public array $logs = [];

    public function mount(): void
    {
        Log::info('Livewire: LuaErrorLogger page mounted', [
            'server_id' => $this->getServer()->id
        ]);
        
        // Charger les logs au démarrage
        $this->loadLogs();
    }

    /**
     * Watcher pour la recherche
     */
    public function updatedSearch(): void
    {
        $this->loadLogs();
    }

    /**
     * Watcher pour le filtre de niveau
     */
    public function updatedLevelFilter(): void
    {
        $this->loadLogs();
    }

    /**
     * Watcher pour le filtre de temps
     */
    public function updatedTimeFilter(): void
    {
        $this->loadLogs();
    }

    /**
     * Watcher pour le bascule des erreurs résolues
     */
    public function updatedShowResolved(): void
    {
        $this->loadLogs();
    }

    public function getTitle(): string
    {
        return 'Logger d\'erreur Lua';
    }

    public function getSubheading(): string
    {
        return 'Consultez les erreurs Lua détectées sur votre serveur Garry\'s Mod';
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

    /**
     * Configure le tableau Filament
     */
    public function table(Table $table): Table
    {
        return $table
            ->query(
                LuaError::query()->where('server_id', $this->getServer()->id)
            )
            ->columns([
                TextColumn::make('first_seen')
                    ->label('Première fois')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->grow(false)
                    ->icon('tabler-clock'),

                TextColumn::make('level')
                    ->label('Niveau')
                    ->badge()
                    ->color(fn (string $state) => match($state) {
                        'ERROR' => 'danger',
                        'WARNING' => 'warning',
                        'INFO' => 'info',
                        default => 'gray'
                    })
                    ->grow(false),

                TextColumn::make('message')
                    ->label('Message d\'erreur')
                    ->limit(80)
                    ->searchable()
                    ->description(fn (LuaError $record) => $record->stack_trace ? 'Stack trace disponible' : null)
                    ->icon('tabler-alert-circle'),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Niveau')
                    ->options([
                        'all' => 'Tous les niveaux',
                        'ERROR' => 'Erreur',
                        'WARNING' => 'Avertissement',
                        'INFO' => 'Information'
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] !== 'all') {
                            $query->where('level', $data['value']);
                        }
                    }),

                SelectFilter::make('resolved')
                    ->label('Statut')
                    ->options([
                        'false' => 'Non résolues',
                        'true' => 'Résolues',
                        'all' => 'Toutes'
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] !== 'all') {
                            $query->where('resolved', $data['value'] === 'true');
                        }
                    }),

                Filter::make('search')
                    ->form([
                        TextInput::make('search')
                            ->label('Rechercher')
                            ->placeholder('Rechercher dans les messages ou addons...')
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['search'])) {
                            $query->where(function($q) use ($data) {
                                $q->where('message', 'like', '%' . $data['search'] . '%')
                                  ->orWhere('addon', 'like', '%' . $data['search'] . '%');
                            });
                        }
                    }),

                Filter::make('time')
                    ->form([
                        Select::make('time_filter')
                            ->label('Période')
                            ->options([
                                'all' => 'Toute la période',
                                'today' => 'Aujourd\'hui',
                                'week' => 'Cette semaine',
                                'month' => 'Ce mois'
                            ])
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['time_filter'] !== 'all') {
                            $timeRanges = [
                                'today' => now()->startOfDay(),
                                'week' => now()->subWeek(),
                                'month' => now()->subMonth(),
                            ];
                            
                            if (isset($timeRanges[$data['time_filter']])) {
                                $query->where('first_seen', '>=', $timeRanges[$data['time_filter']]);
                            }
                        }
                    }),
            ])
            ->actions([
                TableAction::make('resolve')
                    ->label('Résoudre')
                    ->icon('tabler-check')
                    ->color('success')
                    ->visible(fn (LuaError $record) => !$record->resolved)
                    ->action(fn (LuaError $record) => $this->markAsResolved($record->error_key))
                    ->requiresConfirmation()
                    ->modalHeading('Marquer comme résolu')
                    ->modalDescription('Êtes-vous sûr de vouloir marquer cette erreur comme résolue ?')
                    ->modalSubmitActionLabel('Résoudre'),

                TableAction::make('unresolve')
                    ->label('Rouvrir')
                    ->icon('tabler-x')
                    ->color('warning')
                    ->visible(fn (LuaError $record) => $record->resolved)
                    ->action(fn (LuaError $record) => $this->markAsUnresolved($record->error_key))
                    ->requiresConfirmation()
                    ->modalHeading('Rouvrir l\'erreur')
                    ->modalDescription('Êtes-vous sûr de vouloir rouvrir cette erreur ?')
                    ->modalSubmitActionLabel('Rouvrir'),

                TableAction::make('delete')
                    ->label('Supprimer')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->action(fn (LuaError $record) => $this->deleteError($record->error_key))
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer l\'erreur')
                    ->modalDescription('Êtes-vous sûr de vouloir supprimer cette erreur ? Cette action est irréversible.')
                    ->modalSubmitActionLabel('Supprimer'),

                TableAction::make('view')
                    ->label('Voir détails')
                    ->icon('tabler-eye')
                    ->color('info')
                    ->modalContent(fn (LuaError $record) => view('filament.server.modals.lua-error-details', ['error' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
            ])
            ->bulkActions([
                BulkAction::make('resolve_selected')
                    ->label('Résoudre la sélection')
                    ->icon('tabler-check')
                    ->color('success')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $this->markAsResolved($record->error_key);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Résoudre les erreurs sélectionnées')
                    ->modalDescription('Êtes-vous sûr de vouloir marquer ces erreurs comme résolues ?')
                    ->modalSubmitActionLabel('Résoudre'),

                BulkAction::make('delete_selected')
                    ->label('Supprimer la sélection')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            $this->deleteError($record->error_key);
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Supprimer les erreurs sélectionnées')
                    ->modalDescription('Êtes-vous sûr de vouloir supprimer ces erreurs ? Cette action est irréversible.')
                    ->modalSubmitActionLabel('Supprimer'),
            ])
            ->defaultSort('first_seen', 'desc')
            ->paginated([25, 50, 100])
            ->defaultPaginationPageOption(25);
    }

    /**
     * Charge les logs depuis la base de données
     */
    public function loadLogs(): void
    {
        try {
            $serverId = $this->getServer()->id;
            
            // Debug: afficher l'ID du serveur
            Log::info('Livewire: Loading logs for server', ['server_id' => $serverId]);
            
            // Requête de base
            $query = LuaError::where('server_id', $serverId);

            // Debug: compter le total avant filtres
            $totalBeforeFilters = $query->count();
            Log::info('Livewire: Total errors before filters', ['count' => $totalBeforeFilters]);

            // Si pas d'erreurs, retourner vide
            if ($totalBeforeFilters === 0) {
                Log::warning('Livewire: No errors found for server', ['server_id' => $serverId]);
                $this->logs = [];
                return;
            }

            // Filtre de recherche
            if (!empty($this->search)) {
                $query->where(function($q) {
                    $q->where('message', 'like', '%' . $this->search . '%')
                      ->orWhere('addon', 'like', '%' . $this->search . '%');
                });
            }

            // Filtre de niveau
            if ($this->levelFilter !== 'all') {
                $query->where('level', $this->levelFilter);
            }

            // Filtre de temps
            if ($this->timeFilter !== 'all') {
                $timeRanges = [
                    'today' => now()->startOfDay(),
                    'week' => now()->subWeek(),
                    'month' => now()->subMonth(),
                ];
                
                if (isset($timeRanges[$this->timeFilter])) {
                    $query->where('first_seen', '>=', $timeRanges[$this->timeFilter]);
                }
            }

            // Filtre des erreurs résolues - IMPORTANT: par défaut on affiche TOUTES les erreurs
            if (!$this->showResolved) {
                // On affiche les erreurs non résolues ET les erreurs sans statut résolu
                $query->where(function($q) {
                    $q->where('resolved', false)
                      ->orWhereNull('resolved');
                });
            }

            // Debug: afficher la requête SQL
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            Log::info('Livewire: SQL query', ['sql' => $sql, 'bindings' => $bindings]);

            $this->logs = $query->orderBy('first_seen', 'desc')->get()->toArray();

            // Debug: afficher le résultat final
            Log::info('Livewire: Logs loaded successfully', [
                'server_id' => $serverId,
                'total_before_filters' => $totalBeforeFilters,
                'logs_count' => count($this->logs),
                'show_resolved' => $this->showResolved,
                'filters' => [
                    'search' => $this->search,
                    'level' => $this->levelFilter,
                    'time' => $this->timeFilter
                ],
                'first_log' => $this->logs[0] ?? 'no logs',
                'sql_query' => $sql,
                'sql_bindings' => $bindings
            ]);

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to load logs', [
                'server_id' => $this->getServer()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->logs = [];
        }
    }

    #[Computed]
    public function getLogs(): array
    {
        // Si les logs ne sont pas chargés, les charger
        if (empty($this->logs)) {
            $this->loadLogs();
        }
        
        return $this->logs;
    }

    public function markAsResolved(string $errorKey): void
    {
        try {
            $error = LuaError::where('error_key', $errorKey)
                ->where('server_id', $this->getServer()->id)
                ->first();

            if ($error) {
                $error->markAsResolved();
                
                Log::info('Livewire: Error marked as resolved', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);

                // Recharger les logs
                $this->loadLogs();
            }

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
            $error = LuaError::where('error_key', $errorKey)
                ->where('server_id', $this->getServer()->id)
                ->first();

            if ($error) {
                $error->delete();
                
                Log::info('Livewire: Error deleted', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);

                // Recharger les logs
                $this->loadLogs();
            }

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
        $this->loadLogs(); // Utiliser loadLogs pour rafraîchir les logs
    }

    public function clearLogs(): void
    {
        try {
            LuaError::where('server_id', $this->getServer()->id)->delete();
            
            Log::info('Livewire: Logs cleared', [
                'server_id' => $this->getServer()->id
            ]);

            // Recharger les logs (sera vide)
            $this->loadLogs();

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
            $logs = $this->getLogs();
            
            if (empty($logs)) {
                return;
            }

            $content = '';
            $filename = 'lua-errors-' . $this->getServer()->id . '-' . now()->format('Y-m-d') . '.' . $format;
            $contentType = '';

            switch ($format) {
                case 'json':
                    $content = json_encode($logs, JSON_PRETTY_PRINT);
                    $contentType = 'application/json';
                    break;
                    
                case 'csv':
                    $content = $this->toCsv($logs);
                    $contentType = 'text/csv';
                    break;
                    
                case 'txt':
                    $content = $this->toText($logs);
                    $contentType = 'text/plain';
                    break;
            }

            // Déclencher le téléchargement via JavaScript
            $this->dispatch('download-file', [
                'content' => $content,
                'filename' => $filename,
                'contentType' => $contentType
            ]);

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
     * Bascule l'affichage des erreurs résolues
     */
    public function toggleShowResolved(): void
    {
        $this->showResolved = !$this->showResolved;
        
        Log::info('Livewire: Toggle show resolved', [
            'server_id' => $this->getServer()->id,
            'show_resolved' => $this->showResolved
        ]);
        
        // Recharger les logs avec le nouveau filtre
        $this->loadLogs();
    }

    /**
     * Marque une erreur comme non résolue
     */
    public function markAsUnresolved(string $errorKey): void
    {
        try {
            $error = LuaError::where('error_key', $errorKey)
                ->where('server_id', $this->getServer()->id)
                ->first();

            if ($error) {
                $error->markAsUnresolved();
                
                Log::info('Livewire: Error marked as unresolved', [
                    'server_id' => $this->getServer()->id,
                    'error_key' => $errorKey
                ]);

                // Recharger les logs
                $this->loadLogs();
            }

        } catch (\Exception $e) {
            Log::error('Livewire: Failed to mark error as unresolved', [
                'server_id' => $this->getServer()->id,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Force l'affichage de toutes les erreurs (même résolues)
     */
    public function showAllErrors(): void
    {
        $this->showResolved = true;
        $this->search = '';
        $this->levelFilter = 'all';
        $this->timeFilter = 'all';
        
        Log::info('Livewire: Showing all errors', [
            'server_id' => $this->getServer()->id
        ]);
        
        // Recharger les logs avec tous les filtres désactivés
        $this->loadLogs();
    }

    /**
     * Convertit les logs en CSV
     */
    private function toCsv(array $logs): string
    {
        if (empty($logs)) {
            return '';
        }

        $headers = array_keys($logs[0]);
        $csv = implode(',', $headers) . "\n";

        foreach ($logs as $log) {
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $log)) . "\n";
        }

        return $csv;
    }

    /**
     * Convertit les logs en texte
     */
    private function toText(array $logs): string
    {
        if (empty($logs)) {
            return 'Aucun log trouvé.';
        }

        $text = "Logs d'erreurs Lua\n";
        $text .= str_repeat('=', 50) . "\n\n";

        foreach ($logs as $log) {
            $text .= "Erreur #{$log['id']}\n";
            $text .= "Message: {$log['message']}\n";
            $text .= "Addon: {$log['addon']}\n";
            $text .= "Première fois: {$log['first_seen']}\n";
            $text .= "Dernière fois: {$log['last_seen']}\n";
            $text .= "Compteur: {$log['count']}x\n";
            $text .= str_repeat('-', 30) . "\n\n";
        }

        return $text;
    }

    /**
     * Méthode de test pour vérifier la base de données
     */
    public function testDatabase(): void
    {
        try {
            $serverId = $this->getServer()->id;
            
            // Vérifier le total d'erreurs pour ce serveur
            $totalErrors = LuaError::where('server_id', $serverId)->count();
            
            // Vérifier quelques erreurs
            $sampleErrors = LuaError::where('server_id', $serverId)
                ->limit(5)
                ->get(['id', 'message', 'addon', 'first_seen', 'resolved', 'status']);
            
            // Vérifier toutes les erreurs (sans filtres)
            $allErrors = LuaError::all(['id', 'server_id', 'message', 'resolved', 'status']);
            
            // Vérifier les erreurs résolues vs non résolues
            $resolvedErrors = LuaError::where('server_id', $serverId)->where('resolved', true)->count();
            $unresolvedErrors = LuaError::where('server_id', $serverId)->where('resolved', false)->count();
            $nullResolvedErrors = LuaError::where('server_id', $serverId)->whereNull('resolved')->count();
            
            Log::info('Livewire: Database test results', [
                'server_id' => $serverId,
                'total_errors_for_server' => $totalErrors,
                'resolved_errors' => $resolvedErrors,
                'unresolved_errors' => $unresolvedErrors,
                'null_resolved_errors' => $nullResolvedErrors,
                'sample_errors' => $sampleErrors->toArray(),
                'total_errors_in_table' => $allErrors->count(),
                'all_errors_server_ids' => $allErrors->pluck('server_id')->unique()->toArray()
            ]);
            
            // Afficher dans la session pour debug
            session()->flash('debug_info', [
                'server_id' => $serverId,
                'total_errors_for_server' => $totalErrors,
                'resolved_errors' => $resolvedErrors,
                'unresolved_errors' => $unresolvedErrors,
                'null_resolved_errors' => $nullResolvedErrors,
                'total_errors_in_table' => $allErrors->count(),
                'sample_errors' => $sampleErrors->toArray()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Livewire: Database test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('debug_error', $e->getMessage());
        }
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
