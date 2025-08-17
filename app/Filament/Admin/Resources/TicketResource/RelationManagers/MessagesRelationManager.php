<?php

namespace App\Filament\Admin\Resources\TicketResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MessagesRelationManager extends RelationManager
{
    protected static string $relationship = 'messages';

    protected static ?string $title = 'Messages';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('message')
                    ->label('Message')
                    ->required()
                    ->rows(4),
                
                Forms\Components\Toggle::make('is_internal')
                    ->label('Message interne')
                    ->helperText('Les messages internes ne sont visibles que par les administrateurs'),
                
                Forms\Components\FileUpload::make('attachments')
                    ->label('Pièces jointes')
                    ->multiple()
                    ->directory('ticket-attachments')
                    ->acceptedFileTypes(['image/*', 'application/pdf', '.txt', '.doc', '.docx'])
                    ->maxSize(10240),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Utilisateur')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('message')
                    ->label('Message')
                    ->limit(100)
                    ->wrap(),
                
                Tables\Columns\IconColumn::make('is_internal')
                    ->label('Interne')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Envoyé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_internal')
                    ->label('Messages internes'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at');
    }
}