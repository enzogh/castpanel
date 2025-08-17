<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AddonResource\Pages;
use App\Models\Addon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class AddonResource extends Resource
{
    protected static ?string $model = Addon::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Catalogue Addons';

    protected static ?string $navigationGroup = 'Gestion';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations générales')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $context, ?string $state, Forms\Set $set) => 
                                $context === 'edit' ? null : $set('slug', Str::slug($state))
                            ),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(3),
                        
                        Forms\Components\TextInput::make('author')
                            ->label('Auteur')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('version')
                            ->label('Version')
                            ->required()
                            ->maxLength(50),
                    ])->columns(2),
                
                Forms\Components\Section::make('Catégorisation')
                    ->schema([
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options(Addon::getCategories())
                            ->required(),
                        
                        Forms\Components\TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Ajouter des tags...'),
                        
                        Forms\Components\TagsInput::make('supported_games')
                            ->label('Jeux supportés')
                            ->placeholder('gmod, css, tf2...')
                            ->required(),
                    ])->columns(3),
                
                Forms\Components\Section::make('Liens et ressources')
                    ->schema([
                        Forms\Components\TextInput::make('download_url')
                            ->label('URL de téléchargement')
                            ->url()
                            ->required(),
                        
                        Forms\Components\TextInput::make('repository_url')
                            ->label('URL du dépôt')
                            ->url(),
                        
                        Forms\Components\TextInput::make('documentation_url')
                            ->label('URL de documentation')
                            ->url(),
                        
                        Forms\Components\TextInput::make('image_url')
                            ->label('URL de l\'image')
                            ->url(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Métadonnées')
                    ->schema([
                        Forms\Components\TextInput::make('file_size')
                            ->label('Taille du fichier (bytes)')
                            ->numeric(),
                        
                        Forms\Components\TextInput::make('rating')
                            ->label('Note')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(5)
                            ->step(0.1),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('is_featured')
                            ->label('En vedette'),
                        
                        Forms\Components\Toggle::make('requires_config')
                            ->label('Nécessite une configuration'),
                    ])->columns(3),
                
                Forms\Components\Section::make('Configuration avancée')
                    ->schema([
                        Forms\Components\KeyValue::make('requirements')
                            ->label('Prérequis')
                            ->keyLabel('Prérequis')
                            ->valueLabel('Description'),
                        
                        Forms\Components\Textarea::make('installation_instructions')
                            ->label('Instructions d\'installation')
                            ->rows(4),
                    ]),
            ]);
    }

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
                
                Tables\Columns\TextColumn::make('author')
                    ->label('Auteur')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('version')
                    ->label('Version'),
                
                Tables\Columns\BadgeColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($state) => Addon::getCategories()[$state] ?? $state),
                
                Tables\Columns\TextColumn::make('downloads_count')
                    ->label('Téléchargements')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->label('Note')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Vedette')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(Addon::getCategories()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('En vedette'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddons::route('/'),
            'create' => Pages\CreateAddon::route('/create'),
            'view' => Pages\ViewAddon::route('/{record}'),
            'edit' => Pages\EditAddon::route('/{record}/edit'),
        ];
    }
}