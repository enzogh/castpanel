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
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="critical-errors">0</p>
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
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="warnings">0</p>
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
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="info">0</p>
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
                        <p class="text-2xl font-semibold text-gray-900 dark:text-white" id="total-logs">0</p>
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
                        id="search"
                        placeholder="Rechercher une erreur, un addon, etc..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white"
                    >
                </div>
                <div class="sm:w-48">
                    <label for="level-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Niveau
                    </label>
                    <select
                        id="level-filter"
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
                        id="time-filter"
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
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        Logs en temps réel
                    </h3>
                    <div class="flex items-center space-x-2">
                        <button
                            id="auto-scroll"
                            class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 rounded-md hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors"
                        >
                            <x-filament::icon name="tabler-arrow-down" class="w-4 h-4 mr-1" />
                            Auto-scroll
                        </button>
                        <button
                            id="pause-logs"
                            class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            <x-filament::icon name="tabler-player-pause" class="w-4 h-4 mr-1" />
                            Pause
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div
                    id="logs-container"
                    class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 h-96 overflow-y-auto font-mono text-sm"
                    style="font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;"
                >
                    <div class="text-gray-500 dark:text-gray-400 text-center py-8">
                        <x-filament::icon name="tabler-loader" class="w-8 h-8 mx-auto mb-2 animate-spin" />
                        <p>Chargement des logs...</p>
                    </div>
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
                        <div id="addon-errors" class="space-y-2">
                            <div class="text-gray-500 dark:text-gray-400 text-center py-4">
                                Aucune donnée disponible
                            </div>
                        </div>
                    </div>

                    <!-- Top des erreurs -->
                    <div>
                        <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3">
                            Top des erreurs
                        </h4>
                        <div id="error-types" class="space-y-2">
                            <div class="text-gray-500 dark:text-gray-400 text-center py-4">
                                Aucune donnée disponible
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let logsPaused = false;
        let autoScroll = true;
        let logData = [];
        let filteredLogs = [];

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initializeLogger();
            setupEventListeners();
            startLogPolling();
        });

        function initializeLogger() {
            // Initialiser les statistiques
            updateStats();
            
            // Charger les logs initiaux
            loadInitialLogs();
        }

        function setupEventListeners() {
            // Recherche
            document.getElementById('search').addEventListener('input', filterLogs);
            
            // Filtres
            document.getElementById('level-filter').addEventListener('change', filterLogs);
            document.getElementById('time-filter').addEventListener('change', filterLogs);
            
            // Boutons
            document.getElementById('auto-scroll').addEventListener('click', toggleAutoScroll);
            document.getElementById('pause-logs').addEventListener('click', togglePauseLogs);
        }

        function startLogPolling() {
            // Polling toutes les 5 secondes
            setInterval(() => {
                if (!logsPaused) {
                    fetchNewLogs();
                }
            }, 5000);
        }

        function loadInitialLogs() {
            // Simuler le chargement des logs initiaux
            setTimeout(() => {
                const container = document.getElementById('logs-container');
                container.innerHTML = `
                    <div class="text-gray-500 dark:text-gray-400 text-center py-8">
                        <x-filament::icon name="tabler-check-circle" class="w-8 h-8 mx-auto mb-2 text-green-500" />
                        <p>Aucun log disponible pour le moment</p>
                        <p class="text-sm mt-1">Les logs apparaîtront ici en temps réel</p>
                    </div>
                `;
            }, 1000);
        }

        function fetchNewLogs() {
            // Ici, vous feriez un appel API pour récupérer les nouveaux logs
            // Pour l'instant, on simule avec des données d'exemple
            const mockLogs = generateMockLogs();
            appendLogs(mockLogs);
        }

        function generateMockLogs() {
            const levels = ['error', 'warning', 'info'];
            const addons = ['DarkRP', 'TTT', 'Prop Hunt', 'Sandbox', 'Wiremod'];
            const messages = [
                'Lua error in addon: attempt to index a nil value',
                'Failed to load addon: missing dependency',
                'Addon loaded successfully',
                'Warning: deprecated function used',
                'Error: invalid argument type'
            ];

            const logs = [];
            for (let i = 0; i < Math.floor(Math.random() * 3) + 1; i++) {
                logs.push({
                    timestamp: new Date().toISOString(),
                    level: levels[Math.floor(Math.random() * levels.length)],
                    addon: addons[Math.floor(Math.random() * addons.length)],
                    message: messages[Math.floor(Math.random() * messages.length)],
                    stack: 'stack trace would go here...'
                });
            }
            return logs;
        }

        function appendLogs(logs) {
            const container = document.getElementById('logs-container');
            
            // Supprimer le message "aucun log"
            if (container.querySelector('.text-gray-500')) {
                container.innerHTML = '';
            }

            logs.forEach(log => {
                const logElement = createLogElement(log);
                container.appendChild(logElement);
            });

            // Auto-scroll si activé
            if (autoScroll) {
                container.scrollTop = container.scrollHeight;
            }

            // Mettre à jour les statistiques
            updateStats();
        }

        function createLogElement(log) {
            const div = document.createElement('div');
            div.className = 'log-entry mb-2 p-2 rounded border-l-4';
            
            const levelColors = {
                error: 'border-red-500 bg-red-50 dark:bg-red-900/20',
                warning: 'border-yellow-500 bg-yellow-50 dark:bg-yellow-900/20',
                info: 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
            };

            div.className += ` ${levelColors[log.level] || levelColors.info}`;

            const time = new Date(log.timestamp).toLocaleTimeString();
            const levelIcon = {
                error: 'tabler-alert-circle',
                warning: 'tabler-alert-triangle',
                info: 'tabler-info-circle'
            }[log.level];

            div.innerHTML = `
                <div class="flex items-start space-x-2">
                    <div class="flex-shrink-0">
                        <span class="text-xs text-gray-500 dark:text-gray-400">${time}</span>
                    </div>
                    <div class="flex-shrink-0">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
                            log.level === 'error' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                            log.level === 'warning' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                        }">
                            ${log.level.toUpperCase()}
                        </span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                            ${log.addon}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            ${log.message}
                        </div>
                        ${log.stack ? `<div class="text-xs text-gray-500 dark:text-gray-400 mt-1 font-mono">${log.stack}</div>` : ''}
                    </div>
                </div>
            `;

            return div;
        }

        function filterLogs() {
            const searchTerm = document.getElementById('search').value.toLowerCase();
            const levelFilter = document.getElementById('level-filter').value;
            const timeFilter = document.getElementById('time-filter').value;

            // Appliquer les filtres
            filteredLogs = logData.filter(log => {
                const matchesSearch = !searchTerm || 
                    log.message.toLowerCase().includes(searchTerm) ||
                    log.addon.toLowerCase().includes(searchTerm);
                
                const matchesLevel = !levelFilter || log.level === levelFilter;
                
                // Ici vous pourriez ajouter la logique de filtrage par temps
                const matchesTime = true; // À implémenter selon vos besoins

                return matchesSearch && matchesLevel && matchesTime;
            });

            // Mettre à jour l'affichage
            displayFilteredLogs();
        }

        function displayFilteredLogs() {
            const container = document.getElementById('logs-container');
            container.innerHTML = '';

            if (filteredLogs.length === 0) {
                container.innerHTML = `
                    <div class="text-gray-500 dark:text-gray-400 text-center py-8">
                        <x-filament::icon name="tabler-search" class="w-8 h-8 mx-auto mb-2" />
                        <p>Aucun log ne correspond aux critères de recherche</p>
                    </div>
                `;
                return;
            }

            filteredLogs.forEach(log => {
                const logElement = createLogElement(log);
                container.appendChild(logElement);
            });
        }

        function updateStats() {
            // Mettre à jour les statistiques (à implémenter avec les vraies données)
            document.getElementById('critical-errors').textContent = '0';
            document.getElementById('warnings').textContent = '0';
            document.getElementById('info').textContent = '0';
            document.getElementById('total-logs').textContent = '0';
        }

        function toggleAutoScroll() {
            autoScroll = !autoScroll;
            const button = document.getElementById('auto-scroll');
            
            if (autoScroll) {
                button.classList.remove('bg-gray-100', 'dark:bg-gray-700');
                button.classList.add('bg-blue-100', 'dark:bg-blue-900/20');
                button.innerHTML = '<x-filament::icon name="tabler-arrow-down" class="w-4 h-4 mr-1" /> Auto-scroll';
            } else {
                button.classList.remove('bg-blue-100', 'dark:bg-blue-900/20');
                button.classList.add('bg-gray-100', 'dark:bg-gray-700');
                button.innerHTML = '<x-filament::icon name="tabler-arrow-down" class="w-4 h-4 mr-1" /> Auto-scroll désactivé';
            }
        }

        function togglePauseLogs() {
            logsPaused = !logsPaused;
            const button = document.getElementById('pause-logs');
            
            if (logsPaused) {
                button.classList.remove('bg-gray-100', 'dark:bg-gray-700');
                button.classList.add('bg-red-100', 'dark:bg-red-900/20');
                button.innerHTML = '<x-filament::icon name="tabler-player-play" class="w-4 h-4 mr-1" /> Reprendre';
            } else {
                button.classList.remove('bg-red-100', 'dark:bg-red-900/20');
                button.classList.add('bg-gray-100', 'dark:bg-gray-700');
                button.innerHTML = '<x-filament::icon name="tabler-player-pause" class="w-4 h-4 mr-1" /> Pause';
            }
        }

        // Écouter les événements Livewire
        document.addEventListener('logs-refreshed', function() {
            loadInitialLogs();
        });

        document.addEventListener('logs-cleared', function() {
            const container = document.getElementById('logs-container');
            container.innerHTML = `
                <div class="text-gray-500 dark:text-gray-400 text-center py-8">
                    <x-filament::icon name="tabler-check-circle" class="w-8 h-8 mx-auto mb-2 text-green-500" />
                    <p>Logs effacés avec succès</p>
                </div>
            `;
            updateStats();
        });

        document.addEventListener('logs-exported', function() {
            // Ici vous pourriez implémenter l'export des logs
            console.log('Export des logs demandé');
        });
    </script>
</x-filament-panels::page>
