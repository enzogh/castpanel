<?php

namespace App\Filament\Server\Resources;

use App\Filament\Server\Resources\TicketResource\Pages;
use App\Filament\Server\Resources\TicketResource\RelationManagers;
use App\Models\Ticket;
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

    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';

    protected static ?string $navigationLabel = 'Support';

    protected static ?string $navigationGroup = 'Support';

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Nouveau ticket de support')
                    ->description('Décrivez votre problème concernant ce serveur')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Titre du problème')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Serveur ne démarre pas, Problème avec un addon, etc.'),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Description détaillée')
                            ->required()
                            ->rows(6)
                            ->placeholder('Décrivez votre problème en détail : que s\'est-il passé, quand, avez-vous fait des modifications récentes, etc.'),
                        
                        Forms\Components\Select::make('priority')
                            ->label('Urgence')
                            ->options([
                                Ticket::PRIORITY_LOW => '🟢 Faible - Question générale',
                                Ticket::PRIORITY_MEDIUM => '🟡 Moyen - Problème non critique',
                                Ticket::PRIORITY_HIGH => '🟠 Élevé - Serveur partiellement affecté',
                                Ticket::PRIORITY_URGENT => '🔴 Urgent - Serveur complètement inaccessible',
                            ])
                            ->default(Ticket::PRIORITY_MEDIUM)
                            ->required()
                            ->helperText('Choisissez le niveau d\'urgence approprié'),
                        
                        Forms\Components\Select::make('category')
                            ->label('Type de problème')
                            ->options([
                                Ticket::CATEGORY_TECHNICAL => '⚙️ Problème technique',
                                Ticket::CATEGORY_GENERAL => '💬 Question générale',
                                Ticket::CATEGORY_FEATURE_REQUEST => '💡 Demande de fonctionnalité',
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
                    ->label('Ticket #')
                    ->sortable()
                    ->prefix('#')
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Sujet')
                    ->searchable()
                    ->limit(40)
                    ->weight('medium'),
                
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
                    ->label('Urgence')
                    ->formatStateUsing(fn ($state) => match($state) {
                        Ticket::PRIORITY_LOW => '🟢 Faible',
                        Ticket::PRIORITY_MEDIUM => '🟡 Moyen',
                        Ticket::PRIORITY_HIGH => '🟠 Élevé',
                        Ticket::PRIORITY_URGENT => '🔴 Urgent',
                        default => $state,
                    })
                    ->colors([
                        'danger' => Ticket::PRIORITY_URGENT,
                        'warning' => Ticket::PRIORITY_HIGH,
                        'primary' => Ticket::PRIORITY_MEDIUM,
                        'gray' => Ticket::PRIORITY_LOW,
                    ]),
                
                Tables\Columns\TextColumn::make('assignedTo.email')
                    ->label('Assigné à')
                    ->placeholder('En attente')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('messages_count')
                    ->label('Messages')
                    ->counts('messages')
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(Ticket::getStatuses()),
                
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Urgence')
                    ->options(Ticket::getPriorities()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Voir')
                    ->button(),
                
                Tables\Actions\Action::make('reply')
                    ->label('Répondre')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('primary')
                    ->visible(fn (Ticket $record) => $record->isOpen())
                    ->url(fn (Ticket $record) => static::getUrl('view', ['record' => $record])),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->emptyStateHeading('Aucun ticket pour ce serveur')
            ->emptyStateDescription('Créez votre premier ticket si vous avez besoin d\'aide avec ce serveur.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Créer un ticket')
                    ->icon('heroicon-o-plus'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informations du ticket')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('id')
                                    ->label('Numéro de ticket')
                                    ->prefix('#')
                                    ->weight('bold'),
                                
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->dateTime('d/m/Y à H:i'),
                                
                                Infolists\Components\TextEntry::make('server.name')
                                    ->label('Serveur concerné'),
                                
                                Infolists\Components\TextEntry::make('assignedTo.email')
                                    ->label('Assigné à')
                                    ->placeholder('En attente d\'assignation'),
                            ]),
                        
                        Infolists\Components\TextEntry::make('title')
                            ->label('Titre'),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->prose()
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Statut et priorité')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Statut actuel')
                                    ->formatStateUsing(fn ($state) => Ticket::getStatuses()[$state] ?? $state)
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        Ticket::STATUS_RESOLVED => 'success',
                                        Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_PENDING => 'warning',
                                        Ticket::STATUS_CLOSED => 'danger',
                                        default => 'primary',
                                    }),
                                
                                Infolists\Components\TextEntry::make('priority')
                                    ->label('Niveau d\'urgence')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        Ticket::PRIORITY_LOW => '🟢 Faible',
                                        Ticket::PRIORITY_MEDIUM => '🟡 Moyen',
                                        Ticket::PRIORITY_HIGH => '🟠 Élevé',
                                        Ticket::PRIORITY_URGENT => '🔴 Urgent',
                                        default => $state,
                                    })
                                    ->badge()
                                    ->color(fn ($state) => match ($state) {
                                        Ticket::PRIORITY_URGENT => 'danger',
                                        Ticket::PRIORITY_HIGH => 'warning',
                                        Ticket::PRIORITY_MEDIUM => 'primary',
                                        default => 'gray',
                                    }),
                                
                                Infolists\Components\TextEntry::make('category')
                                    ->label('Catégorie')
                                    ->formatStateUsing(fn ($state) => match($state) {
                                        Ticket::CATEGORY_TECHNICAL => '⚙️ Technique',
                                        Ticket::CATEGORY_GENERAL => '💬 Général',
                                        Ticket::CATEGORY_FEATURE_REQUEST => '💡 Fonctionnalité',
                                        default => $state,
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $serverId = request()->route('tenant');
        $userId = auth()->id();
        
        // Log pour débogage
        \Log::info('TicketResource::getEloquentQuery', [
            'server_id' => $serverId,
            'user_id' => $userId,
            'route' => request()->route()->getName(),
            'url' => request()->url(),
        ]);
        
        $query = parent::getEloquentQuery()
            ->where('user_id', $userId)
            ->where('server_id', $serverId)
            ->with(['server', 'assignedTo', 'messages']);
        
        // Log de la requête SQL générée
        \Log::info('Ticket query SQL', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);
        
        return $query;
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