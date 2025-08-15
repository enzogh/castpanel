<x-filament-panels::page class="fi-lua-error-logger-page">
    <div class="space-y-6">
        <!-- Message d'erreur de base de données -->
        <div id="database-error-message" class="hidden bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <span class="text-red-800 dark:text-red-200 font-medium">Erreur de connexion à la base de données</span>
            </div>
            <p class="text-red-700 dark:text-red-300 text-sm mt-1">Les données peuvent ne pas être à jour. Vérifiez la connectivité du serveur.</p>
        </div>





        <!-- Logs en temps réel -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Logs en temps réel
                        </h3>
                        @if(!$logsPaused)
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                                <span class="text-sm text-green-600 dark:text-green-400">Surveillance active</span>
                            </div>
                        @else
                            <div class="flex items-center space-x-2">
                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-red-600 dark:text-red-400">Surveillance en pause</span>
                            </div>
                        @endif
                    </div>
                    <div class="flex items-center space-x-2">
                        <button
                            wire:click="togglePause"
                            class="px-3 py-1 text-sm rounded-md transition-colors {{ $logsPaused ? 'bg-green-100 dark:bg-green-900/20 text-green-700 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300' }}"
                        >
                            {{ $logsPaused ? 'Reprendre' : 'Pause' }}
                        </button>
                        <button
                            wire:click="refreshLogs"
                            class="px-3 py-1 text-sm rounded-md transition-colors bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300"
                        >
                            Actualiser
                        </button>
                        <button
                            wire:click="toggleShowResolved"
                            class="px-3 py-1 text-sm rounded-md transition-colors {{ $showResolved ? 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-300' : 'bg-gray-100 dark:bg-gray-900/20 text-gray-700 dark:text-gray-300' }}"
                        >
                            {{ $showResolved ? 'Masquer résolues' : 'Afficher résolues' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tableau des logs -->
            <div class="p-4" wire:poll.30s="monitorConsole">
                <div class="overflow-x-auto">

                    
                    
            
            <!-- Protection contre les erreurs de type -->
            @php
                // S'assurer que $logs est toujours un tableau
                if (!is_array($logs)) {
                    $logs = [];
                }
            @endphp
            
            <!-- Surveillance automatique de la console -->
            <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Surveillance de la console</h3>
                            <p class="text-xs text-blue-600 dark:text-blue-400">
                                Capture automatique des erreurs Lua [ERROR] 
                                @if(is_array($logs))
                                    ({{ count($logs) }} erreur(s) ouverte(s))
                                @else
                                    (Erreurs en cours de chargement...)
                                @endif
                            </p>
                        </div>
                    </div>
                    <button
                        wire:click="monitorConsole"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="inline-flex items-center px-3 py-1 rounded text-xs font-medium bg-blue-500 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-700 text-white transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Surveiller la console maintenant"
                    >
                        <svg wire:loading.remove class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <svg wire:loading class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Surveiller
                    </button>
                </div>
            </div>
            
            <!-- Tableau toujours visible -->
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
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Message d'erreur
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-48">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        
                        <!-- Corps du tableau -->
                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @if(is_array($logs) && count($logs) > 0)
                                @foreach($logs as $log)
                                    <tr class="hover:scale-[1.01] hover:shadow-sm transition-all duration-200 {{ $log['resolved'] ?? false ? 'opacity-60 bg-green-50 dark:bg-green-900/20' : '' }}">
                                        <!-- Première fois -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white w-32">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ \Carbon\Carbon::parse($log['first_seen'] ?? $log['timestamp'])->format('H:i:s') }}</span>
                                                @if(isset($log['count']) && $log['count'] > 1)
                                                    <span class="text-xs text-orange-600 dark:text-orange-400 font-medium">{{ $log['count'] }}x</span>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        <!-- Dernière fois -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white w-32">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ \Carbon\Carbon::parse($log['last_seen'] ?? $log['timestamp'])->format('H:i:s') }}</span>
                                                @if($log['resolved'] ?? false)
                                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Résolu</span>
                                                @endif
                                            </div>
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
                                                        <svg wire:loading.remove class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                        <svg wire:loading class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                        <svg wire:loading.remove class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                        <svg wire:loading class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune erreur ouverte trouvée</h3>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Toutes les erreurs sont fermées ou résolues.
                                            </p>
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

        // Écouter les erreurs de base de données
        document.addEventListener('database-error', function(event) {
            console.log('Database error:', event.detail);
            const errorMessage = document.getElementById('database-error-message');
            if (errorMessage) {
                errorMessage.classList.remove('hidden');
            }
        });
    </script>
</x-filament-panels::page>