<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-lifebuoy class="h-5 w-5" />
                Support rapide
            </div>
        </x-slot>

        <div class="text-center py-6">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
                <x-heroicon-o-ticket class="h-6 w-6 text-blue-600 dark:text-blue-400" />
            </div>
            
            <h3 class="mt-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                Besoin d'aide ?
            </h3>
            
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Notre équipe support est là pour vous aider avec vos serveurs et vos questions.
            </p>
            
            <div class="mt-6">
                <a href="{{ \App\Filament\App\Resources\TicketResource::getUrl('create') }}" 
                   class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:bg-blue-500 dark:hover:bg-blue-400">
                    <x-heroicon-o-plus class="h-4 w-4 mr-2" />
                    Créer un ticket
                </a>
            </div>
            
            <div class="mt-4">
                <a href="{{ \App\Filament\App\Resources\TicketResource::getUrl('index') }}" 
                   class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300">
                    Voir mes tickets existants →
                </a>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>