<x-filament-panels::page class="fi-lua-error-logger-page">
    <div class="space-y-6">
        <!-- En-tête avec actions -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    Erreurs Lua détectées
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    Surveillez et gérez les erreurs Lua de votre serveur Garry's Mod
                </p>
            </div>
            
            <div class="flex items-center gap-3">
                <button
                    wire:click="testDatabase"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-orange-700 bg-orange-100 border border-orange-300 rounded-lg hover:bg-orange-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:bg-orange-900/20 dark:text-orange-300 dark:border-orange-700 dark:hover:bg-orange-800"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Test DB
                </button>

                <button
                    wire:click="refreshTable"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-lg hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-700 dark:hover:bg-blue-800"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh Table
                </button>

                <button
                    wire:click="showAllErrors"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-lg hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:bg-green-900/20 dark:text-green-300 dark:border-green-700 dark:hover:bg-green-800"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Toutes les erreurs
                </button>

                <button
                    wire:click="showErrorsWithoutFilters"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-700 bg-purple-100 border border-purple-300 rounded-lg hover:bg-purple-200 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 dark:bg-purple-900/20 dark:text-purple-300 dark:border-purple-700 dark:hover:bg-purple-800"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Sans filtres
                </button>
            </div>
        </div>

        <!-- Informations de debug -->
        @if(session('debug_info'))
            @php $debugInfo = session('debug_info'); @endphp
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">🔍 Informations de debug :</h4>
                <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                    <p><strong>Server ID :</strong> {{ $debugInfo['server_id'] }}</p>
                    <p><strong>Erreurs pour ce serveur :</strong> {{ $debugInfo['total_errors_for_server'] }}</p>
                    <p><strong>Erreurs résolues :</strong> {{ $debugInfo['resolved_errors'] }}</p>
                    <p><strong>Erreurs non résolues :</strong> {{ $debugInfo['unresolved_errors'] }}</p>
                    <p><strong>Erreurs sans statut :</strong> {{ $debugInfo['null_resolved_errors'] }}</p>
                    <p><strong>Total erreurs en base :</strong> {{ $debugInfo['total_errors_in_table'] }}</p>
                    @if($debugInfo['sample_errors'])
                        <p><strong>Exemples d'erreurs :</strong></p>
                        <ul class="ml-4 space-y-1">
                            @foreach($debugInfo['sample_errors'] as $error)
                                <li>
                                    <strong>ID:</strong> {{ $error['id'] }} | 
                                    <strong>Résolu:</strong> {{ $error['resolved'] ? 'Oui' : 'Non' }} | 
                                    <strong>Status:</strong> {{ $error['status'] ?? 'N/A' }} | 
                                    <strong>Message:</strong> {{ Str::limit($error['message'], 50) }}
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif

        @if(session('debug_error'))
            <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">❌ Erreur de debug :</h4>
                <p class="text-xs text-red-700 dark:text-red-300">{{ session('debug_error') }}</p>
            </div>
        @endif

        <!-- Tableau Filament -->
        {{ $this->table }}
    </div>
</x-filament-panels::page>