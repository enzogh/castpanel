<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TicketResource\Pages;
use App\Filament\Admin\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Infolists;
use Filament\Infolists\Infolist;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Tickets';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du ticket')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->required()
                            ->rows(4),
                        
                        Forms\Components\Select::make('user_id')
                            ->label('Utilisateur')
                            ->relationship('user', 'email')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Select::make('server_id')
                            ->label('Serveur')
                            ->relationship('server', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Gestion du ticket')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(Ticket::getStatuses())
                            ->default(Ticket::STATUS_OPEN)
                            ->required(),
                        
                        Forms\Components\Select::make('priority')
                            ->label('Priorité')
                            ->options(Ticket::getPriorities())
                            ->default(Ticket::PRIORITY_MEDIUM)
                            ->required(),
                        
                        Forms\Components\Select::make('category')
                            ->label('Catégorie')
                            ->options(Ticket::getCategories())
                            ->default(Ticket::CATEGORY_GENERAL)
                            ->required(),
                        
                        Forms\Components\Select::make('assigned_to')
                            ->label('Assigné à')
                            ->relationship('assignedTo', 'email')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Serveur')
                    ->searchable()
                    ->sortable()
                    ->placeholder('N/A'),
                
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
                
                Tables\Columns\TextColumn::make('category')
                    ->label('Catégorie')
                    ->formatStateUsing(fn ($state) => Ticket::getCategories()[$state] ?? $state),
                
                Tables\Columns\TextColumn::make('assignedTo.email')
                    ->label('Assigné à')
                    ->placeholder('Non assigné'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
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
                
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Assigné à')
                    ->relationship('assignedTo', 'email'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
                Infolists\Components\Section::make('Détails du ticket')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Titre'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->prose(),
                        
                        Infolists\Components\TextEntry::make('user.email')
                            ->label('Utilisateur'),
                        
                        Infolists\Components\TextEntry::make('server.name')
                            ->label('Serveur')
                            ->placeholder('N/A'),
                    ])->columns(2),
                
                Infolists\Components\Section::make('Statut et priorité')
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
                        
                        Infolists\Components\TextEntry::make('assignedTo.email')
                            ->label('Assigné à')
                            ->placeholder('Non assigné'),
                    ])->columns(2),
                
                Infolists\Components\Section::make('Dates')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime(),
                        
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Modifié le')
                            ->dateTime(),
                        
                        Infolists\Components\TextEntry::make('closed_at')
                            ->label('Fermé le')
                            ->dateTime()
                            ->placeholder('Pas encore fermé'),
                    ])->columns(3),
            ]);
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
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}