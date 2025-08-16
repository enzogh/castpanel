<?php

namespace App\Filament\Server\Pages;

use App\Models\LuaError;
use App\Models\Server;
use Filament\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Collection;

class LuaErrorLogger extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'tabler-bug';
    protected static ?string $navigationLabel = 'Lua Error Logger';
    protected static ?string $title = 'Lua Error Logger';
    protected static ?string $slug = 'lua-error-logger';
    protected static ?string $navigationGroup = 'Server Management';

    public Server $server;
    public ?array $data = [];

    public function mount(): void
    {
        // Récupérer le serveur depuis l'URL
        $serverId = request()->route('server');
        $this->server = Server::findOrFail($serverId);
        
        // Initialiser le formulaire avec les valeurs actuelles
        $this->form->fill([
            'lua_error_logging_enabled' => $this->server->lua_error_logging_enabled ?? true,
            'lua_error_logging_reason' => $this->server->lua_error_logging_reason,
            'lua_error_control_enabled' => $this->server->lua_error_control_enabled ?? true,
            'lua_error_control_reason' => $this->server->lua_error_control_reason,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuration des erreurs Lua')
                    ->description('Configurez la collecte et le contrôle des erreurs Lua pour ce serveur')
                    ->schema([
                        Toggle::make('lua_error_logging_enabled')
                            ->label('Collecte des erreurs Lua')
                            ->helperText('Active la collecte automatique des erreurs Lua depuis la console du serveur')
                            ->reactive(),
                        
                        Textarea::make('lua_error_logging_reason')
                            ->label('Raison de la désactivation')
                            ->helperText('Optionnel : expliquez pourquoi la collecte est désactivée')
                            ->visible(fn ($get) => !$get('lua_error_logging_enabled'))
                            ->rows(2),
                        
                        Toggle::make('lua_error_control_enabled')
                            ->label('Contrôle des erreurs Lua')
                            ->helperText('Permet de résoudre et gérer les erreurs collectées')
                            ->disabled(fn ($get) => !$get('lua_error_logging_enabled'))
                            ->reactive(),
                        
                        Textarea::make('lua_error_control_reason')
                            ->label('Raison de la désactivation')
                            ->helperText('Optionnel : expliquez pourquoi le contrôle est désactivé')
                            ->visible(fn ($get) => !$get('lua_error_control_enabled'))
                            ->rows(2),
                    ])
                    ->columns(2),
            ]);
    }

    public function saveSettings(): void
    {
        $data = $this->form->getState();
        
        $this->server->update([
            'lua_error_logging_enabled' => $data['lua_error_logging_enabled'],
            'lua_error_logging_reason' => $data['lua_error_logging_enabled'] ? null : $data['lua_error_logging_reason'],
            'lua_error_control_enabled' => $data['lua_error_control_enabled'],
            'lua_error_control_reason' => $data['lua_error_control_enabled'] ? null : $data['lua_error_control_reason'],
        ]);

        $this->dispatch('notify', [
            'status' => 'success',
            'message' => 'Configuration sauvegardée avec succès'
        ]);
    }

    public function table(Table $table): Table
    {
        // Vérifier si la collecte est désactivée
        if (!$this->server->lua_error_logging_enabled) {
            return $table
                ->query(LuaError::query()->where('id', 0)) // Requête vide
                ->columns([
                    TextColumn::make('id')
                        ->label('Statut')
                        ->getStateUsing(fn () => 'Collecte désactivée')
                        ->html()
                        ->getStateUsing(fn () => '<div class="text-center py-8 text-muted-foreground">
                            <div class="text-lg font-medium">Collecte des erreurs Lua désactivée</div>
                            <div class="text-sm">' . ($this->server->lua_error_logging_reason ?: 'Aucune raison spécifiée') . '</div>
                        </div>'),
                ])
                ->paginated(false);
        }

        // Vérifier si le contrôle est désactivé
        if (!$this->server->lua_error_control_enabled) {
            return $table
                ->query(LuaError::query()->where('id', 0)) // Requête vide
                ->columns([
                    TextColumn::make('id')
                        ->label('Statut')
                        ->getStateUsing(fn () => 'Contrôle désactivé')
                        ->html()
                        ->getStateUsing(fn () => '<div class="text-center py-8 text-muted-foreground">
                            <div class="text-lg font-medium">Contrôle des erreurs Lua désactivé</div>
                            <div class="text-sm">' . ($this->server->lua_error_control_reason ?: 'Aucune raison spécifiée') . '</div>
                        </div>'),
                ])
                ->paginated(false);
        }

        return $table
            ->query(LuaError::query()->where('server_id', $this->server->id)->where('resolved', false))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                
                TextColumn::make('first_seen')
                    ->label('Première fois')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                TextColumn::make('level')
                    ->label('Niveau')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'error' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    }),
                
                TextColumn::make('message')
                    ->label('Message')
                    ->limit(50)
                    ->searchable(),
                
                ToggleColumn::make('resolved')
                    ->label('Résolu')
                    ->disabled(),
            ])
            ->filters([
                SelectFilter::make('level')
                    ->options([
                        'error' => 'Erreur',
                        'warning' => 'Avertissement',
                        'info' => 'Information',
                    ]),
                
                SelectFilter::make('resolved')
                    ->options([
                        '0' => 'Non résolu',
                        '1' => 'Résolu',
                    ])
                    ->default('0'),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('Voir')
                    ->icon('tabler-eye')
                    ->modalContent(fn (LuaError $record) => view('filament.server.modals.lua-error-details', ['error' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Fermer'),
                
                TableAction::make('resolve')
                    ->label('Résoudre')
                    ->icon('tabler-check')
                    ->color('success')
                    ->action(fn (LuaError $record) => $this->markAsResolved($record))
                    ->visible(fn (LuaError $record) => !$record->resolved),
                
                TableAction::make('unresolve')
                    ->label('Marquer non résolu')
                    ->icon('tabler-x')
                    ->color('warning')
                    ->action(fn (LuaError $record) => $this->markAsUnresolved($record))
                    ->visible(fn (LuaError $record) => $record->resolved),
                
                TableAction::make('delete')
                    ->label('Supprimer')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (LuaError $record) => $this->deleteError($record)),
            ])
            ->bulkActions([
                BulkAction::make('resolve')
                    ->label('Marquer comme résolu')
                    ->icon('tabler-check')
                    ->color('success')
                    ->action(fn (Collection $records) => $records->each(fn (LuaError $record) => $this->markAsResolved($record))),
                
                BulkAction::make('delete')
                    ->label('Supprimer')
                    ->icon('tabler-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Collection $records) => $records->each(fn (LuaError $record) => $this->deleteError($record))),
            ])
            ->defaultSort('first_seen', 'desc');
    }

    public function clearLogs(): void
    {
        $count = LuaError::where('server_id', $this->server->id)->count();
        
        LuaError::where('server_id', $this->server->id)->delete();
        
        $this->dispatch('notify', [
            'status' => 'success',
            'message' => "{$count} erreurs supprimées avec succès"
        ]);
        
        $this->dispatch('$refresh');
    }

    public function markAsResolved(LuaError $error): void
    {
        $error->update([
            'resolved' => true,
            'resolved_at' => now(),
        ]);
        
        $this->dispatch('notify', [
            'status' => 'success',
            'message' => 'Erreur marquée comme résolue'
        ]);
        
        $this->dispatch('$refresh');
    }

    public function markAsUnresolved(LuaError $error): void
    {
        $error->update([
            'resolved' => false,
            'resolved_at' => null,
        ]);
        
        $this->dispatch('notify', [
            'status' => 'success',
            'message' => 'Erreur marquée comme non résolue'
        ]);
        
        $this->dispatch('$refresh');
    }

    public function deleteError(LuaError $error): void
    {
        $error->delete();
        
        $this->dispatch('notify', [
            'status' => 'success',
            'message' => 'Erreur supprimée avec succès'
        ]);
        
        $this->dispatch('$refresh');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveSettings')
                ->label('Sauvegarder la configuration')
                ->submit('saveSettings')
                ->color('success')
                ->icon('tabler-settings'),
        ];
    }
}
