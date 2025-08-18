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
                Forms\Components\Textarea::make('message')
                    ->label('Votre message')
                    ->required()
                    ->rows(4)
                    ->placeholder('Tapez votre message ici...')
                    ->helperText('Décrivez votre problème ou répondez aux questions de notre équipe support.'),
                
                Forms\Components\FileUpload::make('attachments')
                    ->label('Pièces jointes')
                    ->multiple()
                    ->directory('ticket-attachments')
                    ->acceptedFileTypes(['image/*', 'application/pdf', '.txt', '.log'])
                    ->maxSize(5120) // 5MB max
                    ->helperText('Formats acceptés : images, PDF, TXT, LOG. Taille max : 5MB par fichier.')
                    ->columnSpanFull(),
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
                            ->prose(),
                        
                        Tables\Columns\TextColumn::make('attachments')
                            ->label('Pièces jointes')
                            ->formatStateUsing(function ($state) {
                                if (!$state || !is_array($state) || empty($state)) {
                                    return null;
                                }
                                
                                $files = collect($state)->map(function ($file) {
                                    $name = basename($file);
                                    return "📎 {$name}";
                                })->join(', ');
                                
                                return $files;
                            })
                            ->color('gray')
                            ->visible(fn ($record) => !empty($record->attachments)),
                    ])->space(2),
                ])->from('md'),
            ])
            ->filters([
                Tables\Filters\Filter::make('my_messages')
                    ->label('Mes messages')
                    ->query(fn (Builder $query) => $query->where('user_id', auth()->id())),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ajouter un message')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Ajouter un message')
                    ->modalDescription('Ajoutez votre message à la conversation.')
                    ->modalWidth('lg')
                    ->visible(fn () => $this->ownerRecord->isOpen())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['is_internal'] = false;
                        return $data;
                    })
                    ->after(function () {
                        Notification::make()
                            ->success()
                            ->title('Message envoyé')
                            ->body('Votre message a été ajouté au ticket.')
                            ->send();
                    }),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('created_at')
            ->paginated(false)
            ->poll('30s');
    }

    public function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();
        
        // Vérifier si la query est null et la créer si nécessaire
        if (!$query) {
            $query = $this->getOwnerRecord()->messages();
        }
        
        return $query
            ->where('is_internal', false) // Ne montrer que les messages non-internes aux clients
            ->with('user');
    }
}