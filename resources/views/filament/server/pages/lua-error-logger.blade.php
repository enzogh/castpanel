<x-filament-panels::page>
    <div class="space-y-6">
        <!-- En-tête de la page -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ __('Lua Error Logger') }}
                </h1>
                <p class="text-muted-foreground">
                    {{ __('Gérez et surveillez les erreurs Lua de votre serveur') }}
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <x-filament::button
                    wire:click="clearLogs"
                    color="danger"
                    icon="tabler-trash"
                >
                    {{ __('Vider tous les logs') }}
                </x-filament::button>
            </div>
        </div>

        <!-- Formulaire de configuration -->
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100 mb-4">
                    Configuration des erreurs Lua
                </h3>
                
                <form wire:submit="saveSettings">
                    {{ $this->form }}
                    
                    <div class="mt-6 flex justify-end">
                        <x-filament::button
                            type="submit"
                            color="success"
                            icon="tabler-settings"
                        >
                            Sauvegarder la configuration
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tableau des erreurs -->
        {{ $this->table }}
    </div>
</x-filament-panels::page>