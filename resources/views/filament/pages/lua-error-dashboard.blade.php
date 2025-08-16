<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Erreurs récentes -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Erreurs récentes</h3>
            </div>
            <div class="p-6">
                @if(count($recentErrors) > 0)
                    <div class="space-y-4">
                        @foreach($recentErrors as $error)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                                                {{ $error['level'] ?? 'ERROR' }}
                                            </span>
                                            @if(isset($error['server']['name']))
                                                <span class="text-sm text-gray-500 dark:text-gray-400">
                                                    Serveur: {{ $error['server']['name'] }}
                                                </span>
                                            @endif
                                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ \Carbon\Carbon::parse($error['last_seen'])->diffForHumans() }}
                                            </span>
                                        </div>
                                        
                                        <p class="text-sm text-gray-900 dark:text-white font-mono mb-2">
                                            {{ Str::limit($error['message'], 100) }}
                                        </p>
                                        
                                        @if(isset($error['addon']) && $error['addon'])
                                            <p class="text-xs text-gray-600 dark:text-gray-400">
                                                Addon: <span class="font-medium">{{ $error['addon'] }}</span>
                                            </p>
                                        @endif
                                        
                                        @if(isset($error['stack_trace']) && $error['stack_trace'])
                                            <details class="mt-2">
                                                <summary class="text-xs text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                                    Voir la stack trace
                                                </summary>
                                                <pre class="mt-2 text-xs text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 p-2 rounded border overflow-x-auto">{{ $error['stack_trace'] }}</pre>
                                            </details>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        @if($error['resolved'] ?? false)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300">
                                                ✓ Résolu
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                                ⚠️ Ouvert
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucune erreur récente</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Toutes vos serveurs GMod fonctionnent parfaitement !
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Erreurs par serveur -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Erreurs par serveur</h3>
            </div>
            <div class="p-6">
                @if(count($serverErrors) > 0)
                    <div class="space-y-3">
                        @foreach($serverErrors as $serverError)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/20 rounded-full flex items-center justify-center">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $serverError->name }}
                                        </h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            ID: {{ $serverError->id }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                                        {{ $serverError->error_count }}
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">erreur(s)</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-6">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Aucune erreur enregistrée pour le moment
                        </p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">Actions rapides</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('filament.admin.pages.lua-console-daemon') }}" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Contrôle du Daemon</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Gérer la surveillance</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.pages.lua-error-logger') }}" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Logger d'erreurs</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Voir les détails</p>
                            </div>
                        </div>
                    </a>

                    <a href="{{ route('filament.admin.pages.lua-error-dashboard') }}" class="block p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/20 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Dashboard</h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Vue d'ensemble</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Auto-refresh -->
        <div wire:poll.30s="$refresh"></div>
    </div>
</x-filament-panels::page>
