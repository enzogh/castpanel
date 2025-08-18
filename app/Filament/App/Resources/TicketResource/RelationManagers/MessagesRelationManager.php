<?php

namespace App\Filament\App\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversation';

    protected static ?string $recordTitleAttribute = 'message';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('💬 Votre message')
                            ->required()
                            ->rows(6)
                            ->placeholder('Tapez votre message ici...')
                            ->helperText('💡 Soyez précis dans votre description pour obtenir une réponse rapide.')
                            ->maxLength(2000)
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                $remaining = 2000 - strlen($state ?? '');
                                $set('message_length', "Caractères restants: {$remaining}");
                            }),
                        
                        Forms\Components\Placeholder::make('message_length')
                            ->content('Caractères restants: 2000')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'text-sm text-gray-500']),
                    ]),
                
                Forms\Components\Section::make('📎 Pièces jointes')
                    ->schema([
                        Forms\Components\FileUpload::make('attachments')
                            ->label('')
                            ->multiple()
                            ->directory('ticket-attachments')
                            ->acceptedFileTypes(['image/*', 'application/pdf', '.txt', '.log', '.zip'])
                            ->maxSize(5120) // 5MB max
                            ->maxFiles(5)
                            ->helperText('📋 Formats acceptés : images, PDF, TXT, LOG, ZIP • Max : 5 fichiers de 5MB chacun')
                            ->uploadingMessage('⏳ Téléchargement en cours...')
                            ->removeUploadedFileButtonPosition('right')
                            ->uploadButtonPosition('left')
                            ->imageEditor()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\Layout\Grid::make([
                    'default' => 1,
                    'md' => 1,
                ])
                ->schema([
                    Tables\Columns\Layout\Panel::make([
                        Tables\Columns\Layout\Split::make([
                            // Avatar et infos utilisateur à gauche
                            Tables\Columns\Layout\Stack::make([
                                Tables\Columns\TextColumn::make('user.username')
                                    ->label('')
                                    ->weight('bold')
                                    ->size('sm')
                                    ->color(fn ($record) => $record->user_id === auth()->id() ? 'primary' : 'success')
                                    ->icon(fn ($record) => $record->user_id === auth()->id() ? 'heroicon-s-user' : 'heroicon-s-user-group'),
                                
                                Tables\Columns\TextColumn::make('created_at')
                                    ->label('')
                                    ->since()
                                    ->size('xs')
                                    ->color('gray')
                                    ->tooltip(fn ($record) => $record->created_at->format('d/m/Y à H:i:s')),
                            ])->space(1)->alignment('start'),
                            
                            // Contenu du message à droite  
                            Tables\Columns\Layout\Stack::make([
                                Tables\Columns\TextColumn::make('message')
                                    ->label('')
                                    ->wrap()
                                    ->html()
                                    ->size('sm'),
                                
                                Tables\Columns\TextColumn::make('attachments')
                                    ->label('')
                                    ->formatStateUsing(function ($state) {
                                        if (!$state || !is_array($state) || empty($state)) {
                                            return null;
                                        }
                                        
                                        $files = collect($state)->map(function ($file) {
                                            $name = basename($file);
                                            return "📎 {$name}";
                                        })->join(' ');
                                        
                                        return $files;
                                    })
                                    ->color('gray')
                                    ->size('xs')
                                    ->visible(fn ($record) => !empty($record->attachments)),
                            ])->space(2)->grow(),
                        ])->from('sm'),
                    ])
                    ->extraAttributes(fn ($record) => [
                        'class' => $record->user_id === auth()->id() 
                            ? 'bg-primary-50 border-l-4 border-l-primary-500 dark:bg-primary-900/20' 
                            : 'bg-gray-50 border-l-4 border-l-gray-300 dark:bg-gray-800/50'
                    ]),
                ]),
            ])
            ->contentGrid([
                'default' => 1,
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('💬 Répondre')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->modalHeading('💬 Répondre au ticket')
                    ->modalDescription(fn () => "Ticket #{$this->ownerRecord->id}: {$this->ownerRecord->title}")
                    ->modalWidth('2xl')
                    ->visible(fn () => $this->ownerRecord->isOpen())
                    ->createAnother(false)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['is_internal'] = false;
                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('✅ Message envoyé')
                            ->body('Votre réponse a été ajoutée au ticket.')
                            ->duration(3000)
                            ->send();
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at', 'asc')
            ->paginated(false)
            ->poll('15s')
            ->striped(false)
            ->emptyStateHeading('💬 Aucun message')
            ->emptyStateDescription('Cette conversation n\'a pas encore de messages.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-ellipsis');
    }

    public function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Vérifier si la query est null et la créer si nécessaire
        if (!$query) {
            $query = $this->getOwnerRecord()->messages()->getQuery();
        }
        
        return $query
            ->where('is_internal', false) // Ne montrer que les messages non-internes aux clients
            ->with('user');
    }
}