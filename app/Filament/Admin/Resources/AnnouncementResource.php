<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\AnnouncementResource\Pages;
use App\Models\Announcement;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Annonces';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Contenu de l\'annonce')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\RichEditor::make('content')
                            ->label('Contenu')
                            ->required()
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'italic',
                                'link',
                                'orderedList',
                                'redo',
                                'strike',
                                'underline',
                                'undo',
                            ]),
                        
                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(Announcement::getTypes())
                            ->default(Announcement::TYPE_INFO)
                            ->required(),
                    ]),
                
                Forms\Components\Section::make('Paramètres d\'affichage')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true),
                        
                        Forms\Components\Toggle::make('is_pinned')
                            ->label('Épinglé')
                            ->helperText('Les annonces épinglées apparaissent en haut'),
                        
                        Forms\Components\Select::make('target_users')
                            ->label('Cible')
                            ->options(Announcement::getTargets())
                            ->default(Announcement::TARGET_ALL)
                            ->required(),
                    ])->columns(3),
                
                Forms\Components\Section::make('Planification')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_at')
                            ->label('Date de début')
                            ->nullable(),
                        
                        Forms\Components\DateTimePicker::make('end_at')
                            ->label('Date de fin')
                            ->nullable()
                            ->after('start_at'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(50),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => Announcement::getTypes()[$state] ?? $state)
                    ->colors([
                        'primary' => Announcement::TYPE_INFO,
                        'warning' => Announcement::TYPE_WARNING,
                        'success' => Announcement::TYPE_SUCCESS,
                        'danger' => Announcement::TYPE_DANGER,
                        'gray' => Announcement::TYPE_MAINTENANCE,
                    ]),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean(),
                
                Tables\Columns\IconColumn::make('is_pinned')
                    ->label('Épinglé')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('target_users')
                    ->label('Cible')
                    ->formatStateUsing(fn ($state) => Announcement::getTargets()[$state] ?? $state),
                
                Tables\Columns\TextColumn::make('author.email')
                    ->label('Auteur')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('start_at')
                    ->label('Début')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Immédiat'),
                
                Tables\Columns\TextColumn::make('end_at')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Permanent'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(Announcement::getTypes()),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Actif'),
                
                Tables\Filters\TernaryFilter::make('is_pinned')
                    ->label('Épinglé'),
                
                Tables\Filters\SelectFilter::make('target_users')
                    ->label('Cible')
                    ->options(Announcement::getTargets()),
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

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Contenu')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Titre'),
                        
                        Infolists\Components\TextEntry::make('content')
                            ->label('Contenu')
                            ->prose()
                            ->html(),
                    ]),
                
                Infolists\Components\Section::make('Paramètres')
                    ->schema([
                        Infolists\Components\TextEntry::make('type')
                            ->label('Type')
                            ->formatStateUsing(fn ($state) => Announcement::getTypes()[$state] ?? $state)
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                Announcement::TYPE_INFO => 'primary',
                                Announcement::TYPE_WARNING => 'warning',
                                Announcement::TYPE_SUCCESS => 'success',
                                Announcement::TYPE_DANGER => 'danger',
                                Announcement::TYPE_MAINTENANCE => 'gray',
                                default => 'primary',
                            }),
                        
                        Infolists\Components\IconEntry::make('is_active')
                            ->label('Actif')
                            ->boolean(),
                        
                        Infolists\Components\IconEntry::make('is_pinned')
                            ->label('Épinglé')
                            ->boolean(),
                        
                        Infolists\Components\TextEntry::make('target_users')
                            ->label('Cible')
                            ->formatStateUsing(fn ($state) => Announcement::getTargets()[$state] ?? $state),
                        
                        Infolists\Components\TextEntry::make('author.email')
                            ->label('Auteur'),
                    ])->columns(3),
                
                Infolists\Components\Section::make('Planification')
                    ->schema([
                        Infolists\Components\TextEntry::make('start_at')
                            ->label('Date de début')
                            ->dateTime()
                            ->placeholder('Immédiat'),
                        
                        Infolists\Components\TextEntry::make('end_at')
                            ->label('Date de fin')
                            ->dateTime()
                            ->placeholder('Permanent'),
                        
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime(),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnnouncements::route('/'),
            'create' => Pages\CreateAnnouncement::route('/create'),
            'view' => Pages\ViewAnnouncement::route('/{record}'),
            'edit' => Pages\EditAnnouncement::route('/{record}/edit'),
        ];
    }
}