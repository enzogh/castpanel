<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Statut du Daemon -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Statut du Daemon</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Statut -->
                    <div class="text-center">
                        <div class="text-2xl font-bold mb-2">
                            @if($isDaemonRunning)
                                <span class="text-green-600 dark:text-green-400">🟢 En cours</span>
                            @else
                                <span class="text-red-600 dark:text-red-400">🔴 Arrêté</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Statut</p>
                    </div>
                    
                    <!-- PID -->
                    <div class="text-center">
                        <div class="text-2xl font-bold mb-2">
                            @if($daemonPid)
                                <span class="text-blue-600 dark:text-blue-400">{{ $daemonPid }}</span>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Process ID</p>
                    </div>
                    
                    <!-- Intervalle -->
                    <div class="text-center">
                        <div class="text-2xl font-bold mb-2">
                            <span class="text-purple-600 dark:text-purple-400">{{ $pollingInterval }}s</span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Intervalle de vérification</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contrôles -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Contrôles</h3>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4">
                    @if(!$isDaemonRunning)
                        <button
                            wire:click="startDaemon"
                            class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Démarrer le Daemon
                        </button>
                    @else
                        <button
                            wire:click="stopDaemon"
                            class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"></path>
                            </svg>
                            Arrêter le Daemon
                        </button>
                        
                        <button
                            wire:click="restartDaemon"
                            class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Redémarrer
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Configuration -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Configuration</h3>
            </div>
            <div class="p-6">
                <div class="space-y-6">
                    <!-- Intervalle de polling -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Intervalle de vérification
                        </label>
                        <div class="flex space-x-2">
                            @foreach([1, 5, 10, 30, 60] as $interval)
                                <button
                                    wire:click="setPollingInterval({{ $interval }})"
                                    class="px-3 py-2 rounded-lg transition-colors {{ $pollingInterval === $interval ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
                                >
                                    {{ $interval }}s
                                </button>
                            @endforeach
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Fréquence de vérification des serveurs GMod pour les erreurs Lua
                        </p>
                    </div>

                    <!-- Auto-refresh -->
                    <div>
                        <label class="flex items-center">
                            <input
                                type="checkbox"
                                wire:model="autoRefresh"
                                wire:change="toggleAutoRefresh"
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            >
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                Actualisation automatique de l'interface
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informations -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Informations</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Surveillance automatique</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Le daemon surveille automatiquement tous vos serveurs Garry's Mod et détecte les erreurs Lua en temps réel.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Stack traces complètes</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Chaque erreur est capturée avec sa stack trace complète pour faciliter le debugging.
                            </p>
                        </div>
                    </div>
                    
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-6 h-6 bg-purple-100 dark:bg-purple-900/20 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Enregistrement en base</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Toutes les erreurs sont automatiquement enregistrées dans la table `lua_errors` avec tous les détails.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Auto-refresh de la page -->
        @if($autoRefresh && $isDaemonRunning)
            <div wire:poll.5s="checkDaemonStatus"></div>
        @endif
    </div>
</x-filament-panels::page>
