<x-filament-panels::page class="fi-lua-error-logger-page">
    <div class="space-y-6">
        <!-- Statistiques des erreurs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 dark:bg-red-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Erreurs critiques</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->stats['critical_errors'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Avertissements</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->stats['warnings'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Informations</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->stats['info'] ?? 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $this->stats['total'] ?? 0 }}</p>
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
                            wire:click="toggleAutoScroll"
                            class="px-3 py-1 text-sm rounded-md transition-colors {{ $autoScroll ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                        >
                            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                            {{ $autoScroll ? 'Auto-scroll' : 'Auto-scroll désactivé' }}
                        </button>
                        <button
                            wire:click="togglePauseLogs"
                            class="px-3 py-1 text-sm rounded-md transition-colors {{ $logsPaused ? 'bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
                        >
                            <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $logsPaused ? 'M14.828 14.828a4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' : 'M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z' }}"></path>
                            </svg>
                            {{ $logsPaused ? 'Reprendre' : 'Pause' }}
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 h-96 overflow-y-auto font-mono text-sm">
                    @if(empty($this->logs))
                        <div class="text-gray-500 dark:text-gray-400 text-center py-8">
                            <svg class="w-8 h-8 mx-auto mb-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>Aucun log disponible pour le moment</p>
                            <p class="text-sm mt-1">Les logs apparaîtront ici en temps réel</p>
                        </div>
                    @else
                        @foreach($this->logs as $log)
                            <div class="log-entry mb-2 p-2 rounded border-l-4 
                                {{ $log['level'] === 'error' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 
                                   ($log['level'] === 'warning' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 
                                    'border-blue-500 bg-blue-50 dark:bg-blue-900/20') }}
                                {{ isset($log['count']) ? 'ring-2 ring-blue-300 dark:ring-blue-600' : '' }}">
                                @if(isset($log['count']) && $log['count'] > 1)
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            Erreur répétée ({{ $log['count'] }}x)
                                        </span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            Première fois: {{ \Carbon\Carbon::parse($log['first_seen'])->format('H:i:s') }}
                                        </span>
                                    </div>
                                @elseif(isset($log['count']) && $log['count'] === 1)
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                            </svg>
                                            Nouvelle erreur
                                        </span>
                                    </div>
                                @endif
                                <div class="flex items-start space-x-2">
                                    <div class="flex-shrink-0">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ \Carbon\Carbon::parse($log['timestamp'])->format('H:i:s') }}
                                        </span>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                            {{ $log['level'] === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                               ($log['level'] === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                                'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200') }}">
                                            {{ strtoupper($log['level']) }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $log['addon'] ?? 'Unknown Addon' }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-300">
                                            {{ $log['message'] }}
                                        </div>
                                        @if(!empty($log['stack_trace']))
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">
                                                {{ $log['stack_trace'] }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Analyse des erreurs -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Analyse des erreurs
                </h3>
            </div>
            
            <div class="p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Top des addons avec erreurs -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                            Top des addons avec erreurs
                        </h4>
                        <div class="space-y-2">
                            @if(empty($this->topAddonErrors))
                                <div class="text-gray-500 dark:text-gray-400 text-center py-4">
                                    Aucune donnée disponible
                                </div>
                            @else
                                @foreach($this->topAddonErrors as $addon => $count)
                                    <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $addon }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $count }} erreurs</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- Top des erreurs -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                            Top des erreurs
                        </h4>
                        <div class="space-y-2">
                            @if(empty($this->topErrorTypes))
                                <div class="text-gray-500 dark:text-gray-400 text-center py-4">
                                    Aucune donnée disponible
                                </div>
                            @else
                                @foreach($this->topErrorTypes as $errorType => $count)
                                    <div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $errorType }}</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $count }} occurrences</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
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
    </script>
</x-filament-panels::page>