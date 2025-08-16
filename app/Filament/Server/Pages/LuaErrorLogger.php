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
        $serverId = $this->getServer()->id;
        $query = LuaError::query()->where('server_id', $serverId);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->grow(false),

                TextColumn::make('first_seen')
                    ->label('Première fois')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->grow(false),

                TextColumn::make('level')
                    ->label('Niveau')
                    ->badge()
                    ->color(fn ($state) => match($state) {
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
                    ->grow(),

                TextColumn::make('resolved')
                    ->label('Statut')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'warning')
                    ->label(fn ($state) => $state ? 'Résolu' : 'Ouvert')
                    ->grow(false),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->label('Niveau')
                    ->options([
                        'ERROR' => 'Erreur',
                        'WARNING' => 'Avertissement',
                        'INFO' => 'Information'
                    ]),

                SelectFilter::make('resolved')
                    ->label('Statut')
                    ->options([
                        '0' => 'Non résolues',
                        '1' => 'Résolues'
                    ])
                    ->query(function ($query, array $data) {
                        if (isset($data['value'])) {
                            $query->where('resolved', $data['value'] === '1');
                        }
                    }),

                Filter::make('search')
                    ->form([
                        TextInput::make('search')
                            ->label('Rechercher')
                            ->placeholder('Rechercher dans les messages...')
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['search'])) {
                            $query->where('message', 'like', '%' . $data['search'] . '%');
                        }
                    }),
            ])
            ->actions([
                TableAction::make('resolve')
                    ->label('Résoudre')
                    ->icon('tabler-check')
                    ->color('success')
                    ->visible(fn (LuaError $record) => $record && !$record->resolved)
                    ->action(fn (LuaError $record) => $this->markAsResolved($record->error_key))
                    ->requiresConfirmation()
                    ->modalHeading('Marquer comme résolu')
                    ->modalDescription('Êtes-vous sûr de vouloir marquer cette erreur comme résolue ?')
                    ->modalSubmitActionLabel('Résoudre'),

                TableAction::make('unresolve')
                    ->label('Rouvrir')
                    ->icon('tabler-x')
                    ->color('warning')
                    ->visible(fn (LuaError $record) => $record && $record->resolved)
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
                            if ($record) {
                                $this->markAsResolved($record->error_key);
                            }
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
                            if ($record) {
                                $this->deleteError($record->error_key);
                            }
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
            
            // Requête de base
            $query = LuaError::where('server_id', $serverId);

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

            $this->logs = $query->orderBy('first_seen', 'desc')->get()->toArray();

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

    /**
     * Affiche une erreur
     */
    public function viewError(LuaError $record): void
    {
        Log::info('LuaErrorLogger: Viewing error', [
            'error_id' => $record->id,
            'message' => $record->message
        ]);
    }

    /**
     * Marque une erreur comme résolue
     */
    public function markAsResolved(string $errorKey): void
    {
        try {
            $error = LuaError::where('error_key', $errorKey)->first();
            if ($error) {
                $error->update([
                    'resolved' => true,
                    'resolved_at' => now(),
                ]);
                
                $this->notify('success', 'Erreur marquée comme résolue');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la résolution', ['error' => $e->getMessage()]);
            $this->notify('danger', 'Erreur lors de la résolution');
        }
    }

    /**
     * Marque une erreur comme non résolue
     */
    public function markAsUnresolved(string $errorKey): void
    {
        try {
            $error = LuaError::where('error_key', $errorKey)->first();
            if ($error) {
                $error->update([
                    'resolved' => false,
                    'resolved_at' => null,
                ]);
                
                $this->notify('success', 'Erreur rouverte');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la réouverture', ['error' => $e->getMessage()]);
            $this->notify('danger', 'Erreur lors de la réouverture');
        }
    }

    /**
     * Supprime une erreur
     */
    public function deleteError(string $errorKey): void
    {
        try {
            $error = LuaError::where('error_key', $errorKey)->first();
            if ($error) {
                $error->delete();
                $this->notify('success', 'Erreur supprimée');
            }
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression', ['error' => $e->getMessage()]);
            $this->notify('danger', 'Erreur lors de la suppression');
        }
    }

    /**
     * Vide tous les logs
     */
    public function clearLogs(): void
    {
        try {
            $serverId = $this->getServer()->id;
            $deleted = LuaError::where('server_id', $serverId)->delete();
            
            $this->notify('success', "{$deleted} erreurs supprimées");
        } catch (\Exception $e) {
            Log::error('Erreur lors du nettoyage', ['error' => $e->getMessage()]);
            $this->notify('danger', 'Erreur lors du nettoyage');
        }
    }

    /**
     * Exporte les logs
     */
    public function exportLogs(): void
    {
        try {
            $serverId = $this->getServer()->id;
            $errors = LuaError::where('server_id', $serverId)
                ->orderBy('first_seen', 'desc')
                ->get();

            $content = "ID,Date première détection,Niveau,Message,Addon,Statut\n";
            foreach ($errors as $error) {
                $content .= sprintf(
                    "%s,%s,%s,%s,%s,%s\n",
                    $error->id,
                    $error->first_seen,
                    $error->level,
                    str_replace(',', ';', $error->message),
                    $error->addon,
                    $error->resolved ? 'Résolu' : 'Ouvert'
                );
            }

            $filename = "lua-errors-server-{$serverId}-" . now()->format('Y-m-d-H-i-s') . ".csv";
            
            $this->dispatch('download-file', [
                'filename' => $filename,
                'content' => $content,
                'mime' => 'text/csv'
            ]);

            $this->notify('success', 'Export terminé');
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'export', ['error' => $e->getMessage()]);
            $this->notify('danger', 'Erreur lors de l\'export');
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
        
        // Forcer le refresh du tableau
        $this->refreshTable();
    }

    /**
     * Affiche les erreurs sans filtres
     */
    public function showErrorsWithoutFilters(): void
    {
        try {
            $serverId = $this->getServer()->id;
            
            // Récupérer toutes les erreurs sans filtres
            $allErrors = LuaError::where('server_id', $serverId)->get();
            
            Log::info('Livewire: Showing errors without filters', [
                'server_id' => $serverId,
                'total_errors' => $allErrors->count(),
                'errors' => $allErrors->toArray()
            ]);
            
            // Afficher dans la session pour debug
            session()->flash('debug_info', [
                'server_id' => $serverId,
                'total_errors_for_server' => $allErrors->count(),
                'errors_without_filters' => $allErrors->toArray()
            ]);
            
            // Forcer le refresh du tableau
            $this->refreshTable();
            
        } catch (\Exception $e) {
            Log::error('Livewire: Failed to show errors without filters', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('debug_error', $e->getMessage());
        }
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
     * Rafraîchit le tableau
     */
    public function refreshTable(): void
    {
        $this->resetTable();
        Log::info('LuaErrorLogger: Table refreshed');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('clear_logs')
                ->label('Vider tous les logs')
                ->icon('tabler-trash')
                ->color('danger')
                ->action(fn () => $this->clearLogs())
                ->requiresConfirmation()
                ->modalHeading('Vider tous les logs')
                ->modalDescription('Êtes-vous sûr de vouloir supprimer toutes les erreurs de ce serveur ? Cette action est irréversible.')
                ->modalSubmitActionLabel('Vider'),

            Action::make('export_logs')
                ->label('Exporter')
                ->icon('tabler-download')
                ->color('success')
                ->action(fn () => $this->exportLogs()),
        ];
    }
}
