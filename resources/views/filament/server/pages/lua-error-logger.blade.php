<x-filament-panels::page class="fi-lua-error-logger-page">
    <div class="space-y-6">
        <!-- Statistiques des erreurs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 dark:bg-red-900/20 rounded-lg">
                        <x-filament::icon
                            name="tabler-alert-circle"
                            class="w-6 h-6 text-red-600 dark:text-red-400"
                        />
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
                        <x-filament::icon
                            name="tabler-alert-triangle"
                            class="w-6 h-6 text-yellow-600 dark:text-yellow-400"
                        />
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
                        <x-filament::icon
                            name="tabler-info-circle"
                            class="w-6 h-6 text-blue-600 dark:text-blue-400"
                        />
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
                        <x-filament::icon
                            name="tabler-check-circle"
                            class="w-6 h-6 text-green-600 dark:text-green-400"
                        />
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
                    <x-filament::input.wrapper>
                        <x-filament::input
                            wire:model.live="search"
                            placeholder="Rechercher une erreur, un addon, etc..."
                        />
                    </x-filament::input.wrapper>
                </div>
                <div class="sm:w-48">
                    <label for="level-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Niveau
                    </label>
                    <x-filament::select wire:model.live="levelFilter">
                        <option value="">Tous les niveaux</option>
                        <option value="error">Erreurs</option>
                        <option value="warning">Avertissements</option>
                        <option value="info">Informations</option>
                    </x-filament::select>
                </div>
                <div class="sm:w-48">
                    <label for="time-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Période
                    </label>
                    <x-filament::select wire:model.live="timeFilter">
                        <option value="1h">Dernière heure</option>
                        <option value="24h">Dernières 24h</option>
                        <option value="7d">7 derniers jours</option>
                        <option value="30d">30 derniers jours</option>
                        <option value="all">Tout</option>
                    </x-filament::select>
                </div>
            </div>
        </div>

        <!-- Logs en temps réel -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Logs en temps réel
                    </h3>
                    <div class="flex items-center space-x-2">
                        <x-filament::button
                            wire:click="toggleAutoScroll"
                            :color="$autoScroll ? 'primary' : 'gray'"
                            size="sm"
                        >
                            <x-filament::icon name="tabler-arrow-down" class="w-4 h-4 mr-1" />
                            {{ $autoScroll ? 'Auto-scroll' : 'Auto-scroll désactivé' }}
                        </x-filament::button>
                        <x-filament::button
                            wire:click="togglePauseLogs"
                            :color="$logsPaused ? 'danger' : 'gray'"
                            size="sm"
                        >
                            <x-filament::icon name="{{ $logsPaused ? 'tabler-player-play' : 'tabler-player-pause' }}" class="w-4 h-4 mr-1" />
                            {{ $logsPaused ? 'Reprendre' : 'Pause' }}
                        </x-filament::button>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 h-96 overflow-y-auto font-mono text-sm">
                    @if(empty($this->logs))
                        <div class="text-gray-500 dark:text-gray-400 text-center py-8">
                            <x-filament::icon name="tabler-check-circle" class="w-8 h-8 mx-auto mb-2 text-green-500" />
                            <p>Aucun log disponible pour le moment</p>
                            <p class="text-sm mt-1">Les logs apparaîtront ici en temps réel</p>
                        </div>
                    @else
                        @foreach($this->logs as $log)
                            <div class="log-entry mb-2 p-2 rounded border-l-4 
                                {{ $log['level'] === 'error' ? 'border-red-500 bg-red-50 dark:bg-red-900/20' : 
                                   ($log['level'] === 'warning' ? 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20' : 
                                    'border-blue-500 bg-blue-50 dark:bg-blue-900/20') }}">
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