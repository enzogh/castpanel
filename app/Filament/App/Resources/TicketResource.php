<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\TicketResource\Pages;
use App\Filament\App\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Mes tickets';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Nouveau ticket')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Décrivez brièvement votre problème'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description détaillée')
                            ->required()
                            ->rows(6)
                            ->placeholder('Décrivez votre problème en détail...'),
                        
                        Forms\Components\Select::make('server_id')
                            ->label('Serveur concerné')
                            ->relationship('server', 'name', fn (Builder $query) => 
                                $query->where('owner_id', auth()->id())
                            )
                            ->getOptionLabelFromRecordUsing(fn (Server $record) => "{$record->name} (#{$record->id})")
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Sélectionnez le serveur concerné par votre demande, ou laissez vide pour une question générale'),
                        
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options([
                                Ticket::PRIORITY_LOW => 'Faible - Question générale',
                                Ticket::PRIORITY_MEDIUM => 'Moyen - Problème non urgent',
                                Ticket::PRIORITY_HIGH => 'Élevé - Problème urgent',
                                Ticket::PRIORITY_URGENT => 'Urgent - Serveur inaccessible',
                            ])
                            ->default(Ticket::PRIORITY_MEDIUM)
                            ->required(),
                        
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options([
                                Ticket::CATEGORY_TECHNICAL => 'Problème technique',
                                Ticket::CATEGORY_BILLING => 'Question de facturation',
                                Ticket::CATEGORY_GENERAL => 'Question générale',
                                Ticket::CATEGORY_FEATURE_REQUEST => 'Demande de fonctionnalité',
                            ])
                            ->default(Ticket::CATEGORY_TECHNICAL)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->prefix('#'),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(50)
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Serveur')
                    ->searchable()
                    ->placeholder('Général'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => Ticket::getStatuses()[$state] ?? $state)
                    ->colors([
                        'success' => Ticket::STATUS_RESOLVED,
                        'warning' => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING],
                        'danger' => Ticket::STATUS_CLOSED,
                        'primary' => Ticket::STATUS_OPEN,
                    ]),
                
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priorité')
                    ->formatStateUsing(fn ($state) => Ticket::getPriorities()[$state] ?? $state)
                    ->colors([
                        'danger' => Ticket::PRIORITY_URGENT,
                        'warning' => Ticket::PRIORITY_HIGH,
                        'primary' => Ticket::PRIORITY_MEDIUM,
                        'gray' => Ticket::PRIORITY_LOW,
                    ]),
                
                Tables\Columns\TextColumn::make('assignedTo.email')
                    ->label('Assigné à')
                    ->placeholder('Non assigné')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Dernière activité')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(Ticket::getStatuses()),
                
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Priorité')
                    ->options(Ticket::getPriorities()),
                
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->options(Ticket::getCategories()),
                
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Serveur')
                    ->relationship('server', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir'),
                
                Tables\Actions\Action::make('add_message')
                    ->label('Répondre')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->visible(fn (Ticket $record) => $record->isOpen())
                    ->url(fn (Ticket $record) => static::getUrl('view', ['record' => $record]))
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Détails du ticket')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Numéro de ticket')
                                    ->prefix('#'),
                                
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->dateTime('d/m/Y à H:i'),
                                
                                Infolists\Components\TextEntry::make('server.name')
                                    ->label('Serveur concerné')
                                    ->placeholder('Général'),
                                
                                Infolists\Components\TextEntry::make('assignedTo.email')
                                    ->label('Assigné à')
                                    ->placeholder('Non assigné'),
                            ]),
                        
                        Infolists\Components\TextEntry::make('title')
                            ->label('Titre'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->prose(),
                    ]),
                
                Infolists\Components\Section::make('Statut et priorité')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Statut')
                                    ->formatStateUsing(fn ($state) => Ticket::getStatuses()[$state] ?? $state)
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        Ticket::STATUS_RESOLVED => 'success',
                                        Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING => 'warning',
                                        Ticket::STATUS_CLOSED => 'danger',
                                        default => 'primary',
                                    }),
                                
                                Infolists\Components\TextEntry::make('priority')
                                    ->label('Priorité')
                                    ->formatStateUsing(fn ($state) => Ticket::getPriorities()[$state] ?? $state)
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        Ticket::PRIORITY_URGENT => 'danger',
                                        Ticket::PRIORITY_HIGH => 'warning',
                                        Ticket::PRIORITY_MEDIUM => 'primary',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('category')
                                    ->label('Catégorie')
                                    ->formatStateUsing(fn ($state) => Ticket::getCategories()[$state] ?? $state),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->with(['server', 'assignedTo', 'messages']);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MessagesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }
}