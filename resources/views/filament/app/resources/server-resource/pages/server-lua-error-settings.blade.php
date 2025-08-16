<x-filament-panels::page>
    <div class="space-y-6">
        <!-- En-tête de la page -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    Configuration des erreurs Lua
                </h1>
                <p class="text-muted-foreground">
                    Configurez le contrôle des erreurs Lua pour votre serveur
                </p>
            </div>
        </div>

        <!-- Formulaire de configuration -->
        <div class="max-w-2xl">
            <form wire:submit="saveSettings">
                {{ $this->form }}
                
                <div class="mt-6 flex justify-end">
                    <x-filament::button
                        type="submit"
                        color="success"
                        icon="tabler-device-floppy"
                    >
                        Sauvegarder les paramètres
                    </x-filament::button>
                </div>
            </form>
        </div>

        <!-- Informations supplémentaires -->
        <div class="max-w-2xl">
            <div class="rounded-lg border bg-card p-6">
                <h3 class="text-lg font-semibold mb-4">Informations sur la gestion des erreurs Lua</h3>
                
                <div class="space-y-4 text-sm text-muted-foreground">
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <strong>Collecte activée + Contrôle activé :</strong> Vous pouvez voir, résoudre et supprimer les erreurs Lua de votre serveur en temps réel.
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-orange-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <strong>Collecte activée + Contrôle désactivé :</strong> Les erreurs sont collectées et analysées, mais vous ne pouvez pas les gérer directement.
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-red-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <strong>Collecte désactivée :</strong> Aucune erreur Lua n'est collectée, analysée ou stockée. Le serveur n'est pas surveillé.
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-3">
                        <div class="w-2 h-2 bg-green-500 rounded-full mt-2 flex-shrink-0"></div>
                        <div>
                            <strong>Recommandé :</strong> Gardez la collecte activée pour maintenir la qualité de votre serveur. Désactivez temporairement si nécessaire.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
