<x-filament-panels::page class="fi-lua-error-logger-page">
    <div class="space-y-6">
        <!-- Statistiques des erreurs -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
            <!-- En-tête des statistiques -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Vue d'ensemble des erreurs</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Statistiques en temps réel de votre serveur</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                        <span class="text-sm text-green-600 dark:text-green-400 font-medium">En direct</span>
                    </div>
                </div>
            </div>
            
            <!-- Grille des statistiques -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <!-- Erreurs critiques -->
                    <div class="relative group">
                        <div class="bg-gradient-to-br from-red-50 to-red-100 dark:from-red-900/20 dark:to-red-800/20 rounded-xl p-6 border border-red-200 dark:border-red-700/50 transition-all duration-200 group-hover:shadow-lg group-hover:scale-105">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                                    <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-red-600 dark:text-red-400">{{ $stats['critical_errors'] ?? 0 }}</p>
                                    <p class="text-xs text-red-500 dark:text-red-400 font-medium">Erreurs</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-red-800 dark:text-red-300">Erreurs critiques</p>
                                <p class="text-xs text-red-600 dark:text-red-400 mt-1">Problèmes nécessitant une attention immédiate</p>
                            </div>
                        </div>
                    </div>

                    <!-- Avertissements -->
                    <div class="relative group">
                        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 dark:from-yellow-900/20 dark:to-yellow-800/20 rounded-xl p-6 border border-yellow-200 dark:border-yellow-700/50 transition-all duration-200 group-hover:shadow-lg group-hover:scale-105">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-yellow-100 dark:bg-yellow-900/30 rounded-lg">
                                    <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400">{{ $stats['warnings'] ?? 0 }}</p>
                                    <p class="text-xs text-yellow-500 dark:text-yellow-400 font-medium">Avertissements</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">Avertissements</p>
                                <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-1">Problèmes à surveiller</p>
                            </div>
                        </div>
                    </div>

                    <!-- Informations -->
                    <div class="relative group">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-xl p-6 border border-blue-200 dark:border-blue-700/50 transition-all duration-200 group-hover:shadow-lg group-hover:scale-105">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-blue-600 dark:text-blue-400">{{ $stats['info'] ?? 0 }}</p>
                                    <p class="text-xs text-blue-500 dark:text-blue-400 font-medium">Infos</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-blue-800 dark:text-blue-300">Informations</p>
                                <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">Messages informatifs</p>
                            </div>
                        </div>
                    </div>

                    <!-- Total -->
                    <div class="relative group">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-xl p-6 border border-green-200 dark:border-green-700/50 transition-all duration-200 group-hover:shadow-lg group-hover:scale-105">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-green-600 dark:text-green-400">{{ $stats['total'] ?? 0 }}</p>
                                    <p class="text-xs text-green-500 dark:text-green-400 font-medium">Total</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-green-800 dark:text-green-300">Total des logs</p>
                                <p class="text-xs text-green-600 dark:text-green-400 mt-1">Tous les événements collectés</p>
                            </div>
                        </div>
                    </div>

                    <!-- Erreurs résolues -->
                    <div class="relative group">
                        <div class="bg-gradient-to-br from-emerald-50 to-emerald-100 dark:from-emerald-900/20 dark:to-emerald-800/20 rounded-xl p-6 border border-emerald-200 dark:border-emerald-700/50 transition-all duration-200 group-hover:shadow-lg group-hover:scale-105">
                            <div class="flex items-center justify-between mb-4">
                                <div class="p-3 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg">
                                    <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="text-right">
                                    <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ $stats['resolved'] ?? 0 }}</p>
                                    <p class="text-xs text-emerald-500 dark:text-emerald-400 font-medium">Résolues</p>
                                </div>
                            </div>
                            <div class="text-center">
                                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Erreurs résolues</p>
                                <p class="text-xs text-emerald-600 dark:text-emerald-400 mt-1">Problèmes traités</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Rechercher dans les logs
                    </label>
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Rechercher une erreur, un addon, etc..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                </div>
                <div class="sm:w-48">
                    <label for="level-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Niveau
                    </label>
                    <select
                        wire:model.live="levelFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="">Tous les niveaux</option>
                        <option value="error">Erreurs</option>
                        <option value="warning">Avertissements</option>
                        <option value="info">Informations</option>
                    </select>
                </div>
                <div class="sm:w-48">
                    <label for="time-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Période
                    </label>
                    <select
                        wire:model.live="timeFilter"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                        <option value="1h">Dernière heure</option>
                        <option value="24h">Dernières 24h</option>
                        <option value="7d">7 derniers jours</option>
                        <option value="30d">30 derniers jours</option>
                        <option value="all">Tout</option>
                    </select>
                </div>
                <div class="sm:w-48">
                    <label for="show-resolved" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Afficher les résolues
                    </label>
                    <div class="flex items-center">
                        <input
                            type="checkbox"
                            wire:model.live="showResolved"
                            id="show-resolved"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700"
                        >
                        <label for="show-resolved" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            {{ $showResolved ? 'Oui' : 'Non' }}
                        </label>
                    </div>
                </div>
            </div>
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
                    </div>
                </div>
            </div>

            <!-- Tableau des logs -->
            <div class="p-4">
                <div class="overflow-x-auto">
                    @if(count($logs) > 0)
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <!-- En-tête du tableau -->
                            <thead class="bg-gray-50 dark:bg-gray-800">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Première fois
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Dernière fois
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Message d'erreur
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            
                            <!-- Corps du tableau -->
                            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($logs as $log)
                                    <tr class="hover:scale-[1.01] hover:shadow-sm transition-all duration-200 {{ $log['resolved'] ?? false ? 'opacity-60 bg-green-50 dark:bg-green-900/20' : '' }}">
                                        <!-- Première fois -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ \Carbon\Carbon::parse($log['first_seen'] ?? $log['timestamp'])->format('H:i:s') }}</span>
                                                @if(isset($log['count']) && $log['count'] > 1)
                                                    <span class="text-xs text-orange-600 dark:text-orange-400 font-medium">{{ $log['count'] }}x</span>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        <!-- Dernière fois -->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <div class="flex flex-col">
                                                <span class="font-medium">{{ \Carbon\Carbon::parse($log['last_seen'] ?? $log['timestamp'])->format('H:i:s') }}</span>
                                                @if($log['resolved'] ?? false)
                                                    <span class="text-xs text-green-600 dark:text-green-400 font-medium">✓ Résolu</span>
                                                @endif
                                            </div>
                                        </td>
                                        
                                        <!-- Message d'erreur -->
                                        <td class="px-6 py-4">
                                            <div class="max-w-md">
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
                                        <td class="px-6 py-4 whitespace-nowrap">
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
                            </tbody>
                        </table>
                    @else
                        <div class="p-8 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Aucun log trouvé</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Aucune erreur Lua n'a été détectée pour le moment.
                            </p>
                        </div>
                    @endif
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