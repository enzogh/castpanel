<?php

namespace App\Filament\Server\Resources;

use App\Filament\Server\Resources\AddonResource\Pages;
use App\Models\Addon;
use App\Models\ServerAddon;
use App\Services\Addons\GmodAddonScannerService;
use App\Services\Servers\AddonManagementService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class AddonResource extends Resource
{
    protected static ?string $model = Addon::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Addons';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?int $navigationSort = 4;

    protected static ?string $tenantOwnershipRelationshipName = null;
    
    protected static bool $isScopedToTenant = false;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->circular()
                    ->defaultImageUrl('/images/addon-default.png'),
                
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('author')
                    ->label('Auteur')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('version')
                    ->label('Version'),
                
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($state) => Addon::getCategories()[$state] ?? $state)
                    ->colors([
                        'primary' => Addon::CATEGORY_GAMEPLAY,
                        'success' => Addon::CATEGORY_ADMINISTRATION,
                        'warning' => Addon::CATEGORY_UI,
                        'info' => Addon::CATEGORY_API,
                        'gray' => Addon::CATEGORY_UTILITY,
                        'purple' => Addon::CATEGORY_COSMETIC,
                    ]),
                
                Tables\Columns\TextColumn::make('formatted_file_size')
                    ->label('Taille'),
                
                Tables\Columns\TextColumn::make('downloads_count')
                    ->label('Téléchargements')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->label('Note')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('installation_status')
                    ->label('Statut')
                    ->getStateUsing(function (Addon $record) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return 'not_installed';
                            
                            $serverAddon = ServerAddon::where('server_id', $server->id)
                                ->where('addon_id', $record->id)
                                ->first();
                            
                            return $serverAddon ? $serverAddon->status : 'not_installed';
                        } catch (\Exception $e) {
                            return 'not_installed';
                        }
                    })
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'installed' => 'Installé',
                            'updating' => 'Mise à jour',
                            'failed' => 'Échec',
                            'disabled' => 'Désactivé',
                            default => 'Non installé',
                        };
                    })
                    ->badge()
                    ->color(function ($state) {
                        return match ($state) {
                            'installed' => 'success',
                            'updating' => 'warning',
                            'failed' => 'danger',
                            'disabled' => 'gray',
                            default => 'primary',
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(Addon::getCategories()),
                
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('En vedette'),
                
                Tables\Filters\Filter::make('installed')
                    ->label('Installés')
                    ->query(function (Builder $query) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return $query;
                            
                            return $query->whereHas('serverAddons', function ($q) use ($server) {
                                $q->where('server_id', $server->id);
                            });
                        } catch (\Exception $e) {
                            return $query;
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('install')
                    ->label('Installer')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(function (Addon $record) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return false;
                            
                            return !ServerAddon::where('server_id', $server->id)
                                ->where('addon_id', $record->id)
                                ->exists();
                        } catch (\Exception $e) {
                            return false;
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Installer l\'addon')
                    ->modalDescription(fn (Addon $record) => "Êtes-vous sûr de vouloir installer {$record->name} ?")
                    ->action(function (Addon $record) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return;
                        
                            $addonService = app(AddonManagementService::class);
                        
                            try {
                                $addonService->installAddon($server, $record);
                                
                                Notification::make()
                                    ->title('Addon installé')
                                    ->body("L'addon {$record->name} a été installé avec succès.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur d\'installation')
                                    ->body("Impossible d'installer l'addon : {$e->getMessage()}")
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur d\'installation')
                                ->body("Impossible d'installer l'addon : {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('uninstall')
                    ->label('Désinstaller')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(function (Addon $record) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return false;
                            
                            return ServerAddon::where('server_id', $server->id)
                                ->where('addon_id', $record->id)
                                ->exists();
                        } catch (\Exception $e) {
                            return false;
                        }
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Désinstaller l\'addon')
                    ->modalDescription(fn (Addon $record) => "Êtes-vous sûr de vouloir désinstaller {$record->name} ?")
                    ->action(function (Addon $record) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return;
                        
                            $addonService = app(AddonManagementService::class);
                        
                            try {
                                $addonService->uninstallAddon($server, $record);
                                
                                Notification::make()
                                    ->title('Addon désinstallé')
                                    ->body("L'addon {$record->name} a été désinstallé avec succès.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de désinstallation')
                                    ->body("Impossible de désinstaller l'addon : {$e->getMessage()}")
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur de désinstallation')
                                ->body("Impossible de désinstaller l'addon : {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('update')
                    ->label('Mettre à jour')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(function (Addon $record) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return false;
                            
                            $serverAddon = ServerAddon::where('server_id', $server->id)
                                ->where('addon_id', $record->id)
                                ->first();
                            
                            return $serverAddon && $serverAddon->version !== $record->version;
                        } catch (\Exception $e) {
                            return false;
                        }
                    })
                    ->action(function (Addon $record) {
                        try {
                            $server = filament()->getTenant();
                            if (!$server) return;
                        
                            $addonService = app(AddonManagementService::class);
                        
                            try {
                                $addonService->updateAddon($server, $record);
                                
                                Notification::make()
                                    ->title('Addon mis à jour')
                                    ->body("L'addon {$record->name} a été mis à jour avec succès.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Erreur de mise à jour')
                                    ->body("Impossible de mettre à jour l'addon : {$e->getMessage()}")
                                    ->danger()
                                    ->send();
                            }
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Erreur de mise à jour')
                                ->body("Impossible de mettre à jour l'addon : {$e->getMessage()}")
                                ->danger()
                                ->send();
                        }
                    }),
                
                Tables\Actions\ViewAction::make()
                    ->label('Détails')
                    ->modalHeading(fn (Addon $record) => $record->name)
                    ->modalContent(function (Addon $record) {
                        return view('filament.server.addon-details', ['addon' => $record]);
                    })
                    ->modalWidth('2xl'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('scan_gmod_addons')
                    ->label('Scanner les addons Garry\'s Mod')
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
                    ->modalDescription('Cette action va scanner le répertoire garrysmod/addons pour détecter automatiquement les addons installés sur ce serveur.')
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
            ->bulkActions([])
            ->defaultSort('downloads_count', 'desc')
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_active', true);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddons::route('/'),
        ];
    }
}