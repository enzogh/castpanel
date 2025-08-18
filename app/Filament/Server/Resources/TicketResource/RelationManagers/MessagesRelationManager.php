<?php

namespace App\Filament\Server\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Conversation';

    protected static ?string $recordTitleAttribute = 'message';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Votre réponse')
                    ->description('Répondez à l\'équipe support ou ajoutez des informations complémentaires')
                    ->schema([
                        Forms\Components\Textarea::make('message')
                            ->label('Message')
                            ->required()
                            ->rows(5)
                            ->placeholder('Décrivez l\'évolution du problème, ajoutez des détails ou répondez aux questions de l\'équipe support...')
                            ->helperText('Soyez aussi précis que possible pour nous aider à résoudre votre problème rapidement.'),
                        
                        Forms\Components\FileUpload::make('attachments')
                            ->label('Captures d\'écran ou fichiers de logs')
                            ->multiple()
                            ->directory('ticket-attachments')
                            ->acceptedFileTypes(['image/*', 'application/pdf', '.txt', '.log', '.conf'])
                            ->maxSize(10240) // 10MB max
                            ->helperText('Ajoutez des captures d\'écran, logs d\'erreur ou fichiers de configuration. Max: 10MB par fichier.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('user.email')
                            ->label('De')
                            ->weight('bold')
                            ->formatStateUsing(function ($state, $record) {
                                $isOwnMessage = $record->user_id === auth()->id();
                                $prefix = $isOwnMessage ? '👤 Vous' : '🛠️ Support';
                                return "{$prefix} ({$state})";
                            })
                            ->color(fn ($record) => $record->user_id === auth()->id() ? 'primary' : 'success'),
                        
                        Tables\Columns\TextColumn::make('created_at')
                            ->label('Envoyé le')
                            ->dateTime('d/m/Y à H:i')
                            ->color('gray'),
                    ])->space(1),
                    
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('message')
                            ->label('Message')
                            ->wrap()
                            ->prose()
                            ->formatStateUsing(function ($state) {
                                return nl2br(e($state));
                            })
                            ->html(),
                        
                        Tables\Columns\TextColumn::make('attachments')
                            ->label('Pièces jointes')
                            ->formatStateUsing(function ($state) {
                                if (!$state || !is_array($state) || empty($state)) {
                                    return null;
                                }
                                
                                $files = collect($state)->map(function ($file) {
                                    $name = basename($file);
                                    $extension = pathinfo($file, PATHINFO_EXTENSION);
                                    $icon = match(strtolower($extension)) {
                                        'jpg', 'jpeg', 'png', 'gif', 'webp' => '🖼️',
                                        'pdf' => '📄',
                                        'txt', 'log' => '📝',
                                        'conf', 'config' => '⚙️',
                                        default => '📎'
                                    };
                                    return "{$icon} {$name}";
                                })->join('<br>');
                                
                                return $files;
                            })
                            ->html()
                            ->color('gray')
                            ->visible(fn ($record) => !empty($record->attachments)),
                    ])->space(2),
                ])->from('md'),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un message')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Répondre au ticket')
                    ->modalDescription('Ajoutez votre message à la conversation avec l\'équipe support.')
                    ->modalWidth('lg')
                    ->visible(fn () => $this->ownerRecord && method_exists($this->ownerRecord, 'isOpen') ? $this->ownerRecord->isOpen() : false)
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['is_internal'] = false;
                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('Message envoyé')
                            ->body('Votre message a été ajouté au ticket. L\'équipe support sera notifiée.')
                            ->send();
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at')
            ->paginated(false)
            ->poll('30s')
            ->emptyStateHeading('Aucun message pour le moment')
            ->emptyStateDescription('Commencez la conversation avec l\'équipe support')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Envoyer le premier message')
                    ->icon('heroicon-o-chat-bubble-left-right'),
            ]);
    }

    public function getTableQuery(): Builder
    {
        try {
            // Vérifier que la relation messages existe
            if (!$this->ownerRecord || !method_exists($this->ownerRecord, 'messages')) {
                Log::warning('MessagesRelationManager: ownerRecord ou relation messages manquante');
                // Retourner une requête vide
                return DB::table('ticket_messages')->whereRaw('1 = 0');
            }
            
            // Vérifier que parent::getTableQuery() retourne une requête valide
            $parentQuery = parent::getTableQuery();
            
            if (!$parentQuery) {
                Log::warning('MessagesRelationManager: parent::getTableQuery() retourne null');
                // Retourner une requête vide
                return DB::table('ticket_messages')->whereRaw('1 = 0');
            }
            
            return $parentQuery
                ->where('is_internal', false) // Ne montrer que les messages non-internes aux clients
                ->with('user');
                
        } catch (\Exception $e) {
            Log::error('MessagesRelationManager: Erreur dans getTableQuery', [
                'error' => $e->getMessage(),
            ]);
            
            // Retourner une requête vide en cas d'erreur
            return DB::table('ticket_messages')->whereRaw('1 = 0');
        }
    }
}