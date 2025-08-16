<x-filament-panels::page class="fi-lua-error-logger-page">
    <div class="space-y-6">
        <!-- Filtres et recherche -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-wrap items-center gap-4">
                <!-- Recherche -->
                <div class="flex-1 min-w-64">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Rechercher
                    </label>
                    <input
                        type="text"
                        id="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Rechercher dans les messages ou addons..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                </div>

                <!-- Filtre de niveau -->
                <div>
                    <label for="levelFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Niveau
                    </label>
                    <select
                        id="levelFilter"
                        wire:model.live="levelFilter"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="all">Tous les niveaux</option>
                        <option value="ERROR">Erreur</option>
                        <option value="WARNING">Avertissement</option>
                        <option value="INFO">Information</option>
                    </select>
                </div>

                <!-- Filtre de temps -->
                <div>
                    <label for="timeFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Période
                    </label>
                    <select
                        id="timeFilter"
                        wire:model.live="timeFilter"
                        class="px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="all">Toute la période</option>
                        <option value="today">Aujourd'hui</option>
                        <option value="week">Cette semaine</option>
                        <option value="month">Ce mois</option>
                    </select>
                </div>

                <!-- Bascule erreurs résolues -->
                <div class="flex items-center">
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            wire:model.live="showResolved"
                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                        >
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Afficher résolues</span>
                    </label>
                </div>

                <!-- Bouton de test -->
                <div>
                    <button
                        wire:click="testDatabase"
                        class="px-3 py-2 text-sm rounded-md bg-orange-100 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-800 transition-colors"
                        title="Tester la base de données"
                    >
                        🔍 Test DB
                    </button>
                </div>

                <!-- Bouton pour afficher toutes les erreurs -->
                <div>
                    <button
                        wire:click="showAllErrors"
                        class="px-3 py-2 text-sm rounded-md bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-800 transition-colors"
                        title="Afficher toutes les erreurs (même résolues)"
                    >
                        📋 Toutes les erreurs
                    </button>
                </div>
            </div>

            <!-- Informations de debug -->
            @if(session('debug_info'))
                @php $debugInfo = session('debug_info'); @endphp
                <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
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
                <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg">
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-200 mb-2">❌ Erreur de debug :</h4>
                    <p class="text-xs text-red-700 dark:text-red-300">{{ session('debug_error') }}</p>
                </div>
            @endif
        </div>

        <!-- Tableau des erreurs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Erreurs Lua détectées
                    </h3>
                    <div class="flex items-center space-x-2">
                        <button
                            wire:click="refreshLogs"
                            class="px-3 py-1 text-sm rounded-md transition-colors bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-800"
                        >
                            Actualiser
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tableau des erreurs -->
            <div class="p-4">
                <div class="overflow-x-auto">
                    @php
                        // S'assurer que $logs est toujours un tableau
                        if (!isset($logs) || !is_array($logs)) {
                            $logs = [];
                        }
                    @endphp
                    
                    <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <!-- En-tête du tableau -->
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                                    Première fois
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                                    Dernière fois
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-24">
                                    Niveau
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Message d'erreur
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                                    Addon
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-48">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        
                        <!-- Corps du tableau -->
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @if(isset($logs) && is_array($logs) && count($logs) > 0)
                                @foreach($logs as $log)
                                    <tr class="hover:scale-[1.01] hover:shadow-sm transition-all duration-200 {{ $log['resolved'] ?? false ? 'opacity-60 bg-green-50 dark:bg-green-900/20' : '' }}">
                                        <!-- Première fois -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white w-32">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ \Carbon\Carbon::parse($log['first_seen'] ?? $log['timestamp'])->format('H:i:s') }}</span>
                                                <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($log['first_seen'] ?? $log['timestamp'])->format('d/m/Y') }}</span>
                                                @if(isset($log['count']) && $log['count'] > 1)
                                                    <span class="text-xs text-orange-600 dark:text-orange-400 font-medium">{{ $log['count'] }}x</span>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        <!-- Dernière fois -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white w-32">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ \Carbon\Carbon::parse($log['last_seen'] ?? $log['timestamp'])->format('H:i:s') }}</span>
                                                <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($log['last_seen'] ?? $log['timestamp'])->format('d/m/Y') }}</span>
                                                @if($log['resolved'] ?? false)
                                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Résolu</span>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Niveau -->
                                        <td class="px-6 py-4 whitespace-nowrap w-24">
                                            @php
                                                $levelColors = [
                                                    'ERROR' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300',
                                                    'WARNING' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
                                                    'INFO' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
                                                ];
                                                $levelColor = $levelColors[$log['level'] ?? 'ERROR'] ?? $levelColors['ERROR'];
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $levelColor }}">
                                                {{ $log['level'] ?? 'ERROR' }}
                                            </span>
                                        </td>
                                        
                                        <!-- Message d'erreur -->
                                        <td class="px-6 py-4 min-w-0">
                                            <div class="min-w-0">
                                                <p class="text-sm text-gray-900 dark:text-white font-mono break-words">
                                                    {{ Str::limit($log['message'], 80) }}
                                                </p>
                                                @if(isset($log['stack_trace']) && !empty($log['stack_trace']))
                                                    <details class="mt-2">
                                                        <summary class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                                            Voir la stack trace
                                                        </summary>
                                                        <pre class="mt-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-2 rounded border overflow-x-auto">{{ $log['stack_trace'] }}</pre>
                                                    </details>
                                                @endif
                                            </div>
                                        </td>

                                        <!-- Addon -->
                                        <td class="px-6 py-4 whitespace-nowrap w-32">
                                            @if(isset($log['addon']) && !empty($log['addon']))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300">
                                                    {{ Str::limit($log['addon'], 20) }}
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-400">-</span>
                                            @endif
                                        </td>
                                        
                                        <!-- Actions -->
                                        <td class="px-6 py-4 whitespace-nowrap w-48">
                                            <div class="flex items-center space-x-2">
                                                @if($log['resolved'] ?? false)
                                                    <button
                                                        wire:click="markAsUnresolved('{{ $log['error_key'] }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                                        class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 hover:bg-yellow-200 dark:hover:bg-yellow-800 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                        title="Marquer comme non résolu"
                                                    >
                                                        <svg wire:loading.remove class="w-2 h-2 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        <svg wire:loading class="w-2 h-2 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        Réouvrir
                                                    </button>
                                                @else
                                                    <button
                                                        wire:click="markAsResolved('{{ $log['error_key'] }}')"
                                                        wire:loading.attr="disabled"
                                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                                        class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-green-500 hover:bg-green-600 dark:bg-green-600 dark:hover:bg-green-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                        title="Marquer cette erreur comme résolue"
                                                    >
                                                        <svg wire:loading.remove class="w-2 h-2 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <svg wire:loading class="w-2 h-2 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                        </svg>
                                                        Résoudre
                                                    </button>
                                                @endif

                                                <button
                                                    wire:click="deleteError('{{ $log['error_key'] }}')"
                                                    class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white transition-colors"
                                                    title="Supprimer cette erreur"
                                                >
                                                    <svg class="w-2 h-2 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Supprimer
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <!-- Ligne vide quand pas d'erreurs -->
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center">
                                            @if(!isset($logs))
                                                <!-- Logs non encore chargés -->
                                                <svg class="w-8 h-8 text-blue-400 mb-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                                </svg>
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Chargement des erreurs...</h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    Récupération des erreurs depuis la base de données...
                                                </p>
                                            @else
                                                <!-- Aucune erreur trouvée -->
                                                <svg class="w-8 h-8 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune erreur trouvée</h3>
                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                    @if($showResolved)
                                                        Aucune erreur ne correspond aux critères de recherche.
                                                    @else
                                                        Aucune erreur ouverte trouvée. Toutes les erreurs sont résolues.
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Écouter les événements Livewire
        document.addEventListener('logs-refreshed', function() {
            // Actualiser la page
            window.location.reload();
        });

        document.addEventListener('logs-cleared', function() {
            // Actualiser la page
            window.location.reload();
        });

        document.addEventListener('download-file', function(event) {
            const { content, filename, contentType } = event.detail;
            
            // Créer un blob et télécharger le fichier
            const blob = new Blob([content], { type: contentType });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        });

        // Écouter les événements de résolution/suppression
        document.addEventListener('error-resolved', function(event) {
            console.log('Error resolved:', event.detail);
            // Forcer le refresh de Livewire
            if (window.Livewire) {
                window.Livewire.dispatch('refresh');
            }
        });

        document.addEventListener('error-unresolved', function(event) {
            console.log('Error unresolved:', event.detail);
            // Forcer le refresh de Livewire
            if (window.Livewire) {
                window.Livewire.dispatch('refresh');
            }
        });

        document.addEventListener('error-deleted', function(event) {
            console.log('Error deleted:', event.detail);
            // Forcer le refresh de Livewire
            if (window.Livewire) {
                window.Livewire.dispatch('refresh');
            }
        });
    </script>
</x-filament-panels::page>