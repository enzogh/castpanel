<?php

namespace App\Filament\App\Resources\ServerResource\Pages;

use App\Filament\App\Resources\ServerResource;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ServerLuaErrorSettings extends Page
{
    protected static string $resource = ServerResource::class;

    protected static string $view = 'filament.app.resources.server-resource.pages.server-lua-error-settings';

    protected static ?string $title = 'Configuration des erreurs Lua';

    protected static ?string $navigationIcon = 'tabler-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $server = $this->getServer();
        
        $this->form->fill([
            'lua_error_control_enabled' => $server->lua_error_control_enabled ?? true,
            'lua_error_control_reason' => $server->lua_error_control_reason,
            'lua_error_logging_enabled' => $server->lua_error_logging_enabled ?? true,
            'lua_error_logging_reason' => $server->lua_error_logging_reason,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Collecte des erreurs Lua')
                    ->description('Configurez si vous souhaitez collecter les erreurs Lua de votre serveur.')
                    ->schema([
                        Toggle::make('lua_error_logging_enabled')
                            ->label('Activer la collecte des erreurs Lua')
                            ->helperText('Quand activé, les erreurs Lua de votre serveur seront collectées et analysées. Quand désactivé, aucune erreur ne sera collectée.')
                            ->default(true)
                            ->required()
                            ->reactive(),

                        Textarea::make('lua_error_logging_reason')
                            ->label('Raison de la désactivation (optionnel)')
                            ->helperText('Si vous désactivez la collecte, vous pouvez expliquer pourquoi (ex: serveur en production, performance, etc.)')
                            ->placeholder('Ex: Serveur en production, problèmes de performance...')
                            ->rows(3)
                            ->visible(fn ($get) => !$get('lua_error_logging_enabled')),
                    ])
                    ->columns(1),

                Section::make('Contrôle des erreurs Lua')
                    ->description('Configurez si vous souhaitez activer le contrôle des erreurs Lua pour ce serveur.')
                    ->schema([
                        Toggle::make('lua_error_control_enabled')
                            ->label('Activer le contrôle des erreurs Lua')
                            ->helperText('Quand activé, vous pourrez voir et gérer les erreurs Lua de votre serveur. Quand désactivé, les erreurs seront toujours collectées mais vous ne pourrez pas les contrôler.')
                            ->default(true)
                            ->required()
                            ->disabled(fn ($get) => !$get('lua_error_logging_enabled')),

                        Textarea::make('lua_error_control_reason')
                            ->label('Raison de la désactivation (optionnel)')
                            ->helperText('Si vous désactivez le contrôle, vous pouvez expliquer pourquoi (ex: serveur en maintenance, erreurs trop nombreuses, etc.)')
                            ->placeholder('Ex: Serveur en maintenance, erreurs trop nombreuses...')
                            ->rows(3)
                            ->visible(fn ($get) => $get('lua_error_logging_enabled') && !$get('lua_error_control_enabled')),
                    ])
                    ->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Sauvegarder')
                ->icon('tabler-device-floppy')
                ->color('success')
                ->action('saveSettings'),
        ];
    }

    public function saveSettings(): void
    {
        $data = $this->form->getState();
        
        $server = $this->getServer();
        
        // Vérifier les permissions
        if (!$this->canManageServer($server)) {
            Notification::make()
                ->title('Permission refusée')
                ->body('Vous n\'avez pas la permission de modifier les paramètres de ce serveur.')
                ->danger()
                ->send();
            return;
        }

        try {
            $server->update([
                'lua_error_logging_enabled' => $data['lua_error_logging_enabled'],
                'lua_error_logging_reason' => $data['lua_error_logging_enabled'] ? null : $data['lua_error_logging_reason'],
                'lua_error_control_enabled' => $data['lua_error_control_enabled'],
                'lua_error_control_reason' => $data['lua_error_control_enabled'] ? null : $data['lua_error_control_reason'],
            ]);

            Notification::make()
                ->title('Paramètres sauvegardés')
                ->body('La configuration des erreurs Lua a été mise à jour avec succès.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body('Une erreur est survenue lors de la sauvegarde des paramètres.')
                ->danger()
                ->send();
        }
    }

    protected function canManageServer($server): bool
    {
        $user = Auth::user();
        
        // L'administrateur peut tout faire
        if ($user->root_admin) {
            return true;
        }
        
        // Le propriétaire du serveur peut modifier ses paramètres
        if ($server->owner_id === $user->id) {
            return true;
        }
        
        // Les subusers avec la permission appropriée peuvent modifier
        $subuser = $server->subusers()->where('user_id', $user->id)->first();
        if ($subuser && $subuser->can('server.lua-error-control')) {
            return true;
        }
        
        return false;
    }

    protected function getServer()
    {
        // Récupérer le serveur depuis l'URL ou la session
        $serverId = request()->route('server') ?? session('current_server_id');
        
        if (!$serverId) {
            abort(404, 'Serveur non trouvé');
        }
        
        return \App\Models\Server::findOrFail($serverId);
    }
}
