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
                    ->placeholder('Non assigné')
                    ->badge()
                    ->color(fn (Ticket $record) => $record->assigned_to === auth()->id() ? 'success' : 'gray')
                    ->formatStateUsing(function ($state, Ticket $record) {
                        if (!$state) return 'Non assigné';
                        return $record->assigned_to === auth()->id() ? '🔵 ' . $state : $state;
                    }),
                
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
                
                Tables\Filters\Filter::make('my_tickets')
                    ->label('Mes tickets')
                    ->query(fn (Builder $query) => $query->where('assigned_to', auth()->id()))
                    ->toggle(),
                
                Tables\Filters\Filter::make('unassigned')
                    ->label('Non assignés')
                    ->query(fn (Builder $query) => $query->whereNull('assigned_to'))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\Action::make('assign_to_me')
                    ->label('M\'assigner')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->visible(fn (Ticket $record) => !$record->assigned_to)
                    ->action(function (Ticket $record) {
                        $record->update([
                            'assigned_to' => auth()->id(),
                            'status' => $record->status === Ticket::STATUS_OPEN ? Ticket::STATUS_IN_PROGRESS : $record->status,
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Ticket assigné')
                            ->body("Le ticket #{$record->id} vous a été assigné.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Assigner le ticket')
                    ->modalDescription(fn (Ticket $record) => "Êtes-vous sûr de vouloir vous assigner le ticket #{$record->id} : {$record->title} ?")
                    ->modalSubmitActionLabel('Oui, m\'assigner'),
                
                Tables\Actions\Action::make('unassign')
                    ->label('Désassigner')
                    ->icon('heroicon-o-user-minus')
                    ->color('warning')
                    ->visible(fn (Ticket $record) => $record->assigned_to === auth()->id())
                    ->action(function (Ticket $record) {
                        $record->update([
                            'assigned_to' => null,
                            'status' => Ticket::STATUS_OPEN,
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Ticket désassigné')
                            ->body("Le ticket #{$record->id} n'est plus assigné.")
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Désassigner le ticket')
                    ->modalDescription(fn (Ticket $record) => "Êtes-vous sûr de vouloir vous désassigner du ticket #{$record->id} : {$record->title} ?")
                    ->modalSubmitActionLabel('Oui, me désassigner'),
                
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('assign_to_me_bulk')
                        ->label('M\'assigner tous')
                        ->icon('heroicon-o-user-plus')
                        ->color('success')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!$record->assigned_to) {
                                    $record->update([
                                        'assigned_to' => auth()->id(),
                                        'status' => $record->status === Ticket::STATUS_OPEN ? Ticket::STATUS_IN_PROGRESS : $record->status,
                                    ]);
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Tickets assignés')
                                ->body("{$count} ticket(s) vous ont été assignés.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Assigner les tickets sélectionnés')
                        ->modalDescription('Êtes-vous sûr de vouloir vous assigner tous les tickets sélectionnés qui ne sont pas déjà assignés ?')
                        ->modalSubmitActionLabel('Oui, m\'assigner tous'),
                    
                    Tables\Actions\BulkAction::make('unassign_bulk')
                        ->label('Désassigner tous')
                        ->icon('heroicon-o-user-minus')
                        ->color('warning')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->assigned_to === auth()->id()) {
                                    $record->update([
                                        'assigned_to' => null,
                                        'status' => Ticket::STATUS_OPEN,
                                    ]);
                                    $count++;
                                }
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Tickets désassignés')
                                ->body("{$count} ticket(s) ont été désassignés.")
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Désassigner les tickets sélectionnés')
                        ->modalDescription('Êtes-vous sûr de vouloir vous désassigner de tous les tickets sélectionnés qui vous sont actuellement assignés ?')
                        ->modalSubmitActionLabel('Oui, me désassigner de tous'),
                    
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