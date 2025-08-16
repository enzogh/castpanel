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

        <!-- Tableau des erreurs -->
        {{ $this->table }}
    </div>
</x-filament-panels::page>