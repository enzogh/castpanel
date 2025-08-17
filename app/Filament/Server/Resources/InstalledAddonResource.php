<?php

namespace App\Filament\Server\Resources;

use App\Filament\Server\Resources\InstalledAddonResource\Pages;
use App\Models\ServerAddon;
use App\Services\Addons\GmodAddonScannerService;
use App\Services\Servers\AddonManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class InstalledAddonResource extends Resource
{
    protected static ?string $model = ServerAddon::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Addons installés';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuration de l\'addon')
                    ->schema([
                        Forms\Components\KeyValue::make('configuration')
                            ->label('Configuration')
                            ->keyLabel('Paramètre')
                            ->valueLabel('Valeur')
                            ->reorderable(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('author')
                    ->label('Auteur')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('version')
                    ->label('Version'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => ServerAddon::getStatuses()[$state] ?? $state)
                    ->colors([
                        'success' => ServerAddon::STATUS_INSTALLED,
                        'warning' => ServerAddon::STATUS_UPDATING,
                        'danger' => ServerAddon::STATUS_FAILED,
                        'gray' => ServerAddon::STATUS_DISABLED,
                    ]),
                
                Tables\Columns\TextColumn::make('installation_date')
                    ->label('Installé le')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('last_update')
                    ->label('Dernière MAJ')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(ServerAddon::getStatuses()),
                
                Tables\Filters\TernaryFilter::make('is_local')
                    ->label('Type d\'addon')
                    ->placeholder('Tous les addons')
                    ->trueLabel('Addons locaux (détectés)')
                    ->falseLabel('Addons du catalogue')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('addon_id'),
                        false: fn (Builder $query) => $query->whereNotNull('addon_id'),
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('scan_gmod_addons')
                    ->label('Scanner addons Garry\'s Mod')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->visible(function () {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return false;
                            
                            $scanner = app(GmodAddonScannerService::class);
                            return $scanner->isGmodServer($server);
                        } catch (\Exception $e) {
                            return false;
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Scanner les addons installés')
                    ->modalDescription('Cette action va scanner le répertoire garrysmod/addons pour détecter automatiquement les addons installés sur ce serveur Garry\'s Mod.')
                    ->modalSubmitActionLabel('Scanner')
                    ->action(function () {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return;
                            
                            $scanner = app(GmodAddonScannerService::class);
                            
                            // Scanner les addons installés
                            $detectedAddons = $scanner->scanInstalledAddons($server);
                            
                            // Synchroniser avec la base de données
                            $syncResults = $scanner->syncDetectedAddons($server, $detectedAddons);
                            
                            $message = sprintf(
                                'Scan terminé : %d addons ajoutés, %d mis à jour, %d supprimés.',
                                $syncResults['added'],
                                $syncResults['updated'],
                                $syncResults['removed']
                            );
                            
                            if (!empty($syncResults['errors'])) {
                                $message .= ' Erreurs : ' . implode(', ', $syncResults['errors']);
                            }
                            
                            Notification::make()
                                ->title('Scan des addons terminé')
                                ->body($message)
                                ->success()
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur lors du scan')
                                ->body('Impossible de scanner les addons : ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('enable')
                    ->label('Activer')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (ServerAddon $record) => $record->isDisabled())
                    ->action(function (ServerAddon $record) {
                        $record->update(['status' => ServerAddon::STATUS_INSTALLED]);
                        
                        Notification::make()
                            ->title('Addon activé')
                            ->body("L'addon {$record->name} a été activé.")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('disable')
                    ->label('Désactiver')
                    ->icon('heroicon-o-pause')
                    ->color('warning')
                    ->visible(fn (ServerAddon $record) => $record->isInstalled())
                    ->action(function (ServerAddon $record) {
                        $record->update(['status' => ServerAddon::STATUS_DISABLED]);
                        
                        Notification::make()
                            ->title('Addon désactivé')
                            ->body("L'addon {$record->name} a été désactivé.")
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('update')
                    ->label('Mettre à jour')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->visible(function (ServerAddon $record) {
                        return $record->addon && $record->version !== $record->addon->version;
                    })
                    ->action(function (ServerAddon $record) {
                        $serverId = request()->route('tenant');
                        if (!$serverId) return;
                        
                        $server = \App\Models\Server::find($serverId);
                        if (!$server) return;
                        
                        $addonService = app(AddonManagementService::class);
                        
                        try {
                            $addonService->updateAddon($server, $record->addon);
                            
                            Notification::make()
                                ->title('Addon mis à jour')
                                ->body("L'addon {$record->name} a été mis à jour.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur de mise à jour')
                                ->body("Impossible de mettre à jour l'addon : {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('uninstall')
                    ->label('Désinstaller')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Désinstaller l\'addon')
                    ->modalDescription(fn (ServerAddon $record) => "Êtes-vous sûr de vouloir désinstaller {$record->name} ?")
                    ->action(function (ServerAddon $record) {
                        $serverId = request()->route('tenant');
                        if (!$serverId) return;
                        
                        $server = \App\Models\Server::find($serverId);
                        if (!$server) return;
                        
                        $addonService = app(AddonManagementService::class);
                        
                        try {
                            $addonService->uninstallAddon($server, $record->addon);
                            
                            Notification::make()
                                ->title('Addon désinstallé')
                                ->body("L'addon {$record->name} a été désinstallé.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur de désinstallation')
                                ->body("Impossible de désinstaller l'addon : {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\EditAction::make()
                    ->label('Configurer')
                    ->visible(fn (ServerAddon $record) => $record->addon && $record->addon->requires_config),
            ])
            ->bulkActions([])
            ->defaultSort('installation_date', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        try {
            $server = filament()->getTenant();
            if (!$server) {
                return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty result
            }
            
            return parent::getEloquentQuery()
                ->where('server_id', $server->id)
                ->with('addon');
        } catch (\Exception $e) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty result on error
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInstalledAddons::route('/'),
            'edit' => Pages\EditInstalledAddon::route('/{record}/edit'),
        ];
    }
}