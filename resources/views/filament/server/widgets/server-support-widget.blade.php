<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-lifebuoy class="h-5 w-5" />
                Support pour {{ $this->getViewData()['server']?->name ?? 'ce serveur' }}
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Support rapide -->
            <div class="text-center py-4">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
                    <x-heroicon-o-chat-bubble-left-right class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                
                <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-gray-100">
                    Problème avec ce serveur ?
                </h3>
                
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Notre équipe est là pour vous aider rapidement
                </p>
                
                <div class="mt-4 flex gap-2 justify-center">
                    <a href="{{ \App\Filament\Server\Resources\TicketResource::getUrl('create', ['tenant' => $this->getViewData()['serverId']]) }}" 
                       class="inline-flex items-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">
                        <x-heroicon-o-exclamation-triangle class="h-4 w-4 mr-2" />
                        Problème urgent
                    </a>
                    
                    <a href="{{ \App\Filament\Server\Resources\TicketResource::getUrl('create', ['tenant' => $this->getViewData()['serverId']]) }}" 
                       class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <x-heroicon-o-chat-bubble-left class="h-4 w-4 mr-2" />
                        Question générale
                    </a>
                </div>
            </div>

            <!-- Liens rapides -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="flex justify-between text-sm">
                    <a href="{{ \App\Filament\Server\Resources\TicketResource::getUrl('index', ['tenant' => $this->getViewData()['serverId']]) }}" 
                       class="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 flex items-center">
                        <x-heroicon-o-ticket class="h-4 w-4 mr-1" />
                        Mes tickets
                    </a>
                    
                    <a href="{{ route('filament.app.resources.tickets.index') }}" 
                       class="text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 flex items-center">
                        <x-heroicon-o-queue-list class="h-4 w-4 mr-1" />
                        Tous mes tickets
                    </a>
                </div>
            </div>

            <!-- Types de problèmes courants -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <h4 class="text-xs font-medium text-gray-900 dark:text-gray-100 uppercase tracking-wide">
                    Problèmes courants
                </h4>
                <div class="mt-2 space-y-1">
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        • Serveur ne démarre pas
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        • Problème avec un addon
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        • Configuration personnalisée
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400">
                        • Problème de performance
                    </div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>