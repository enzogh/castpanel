<div class="space-y-4">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Première apparition</h3>
            <p class="text-sm text-gray-900 dark:text-white">{{ $error->first_seen->format('d/m/Y H:i:s') }}</p>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Dernière apparition</h3>
            <p class="text-sm text-gray-900 dark:text-white">{{ $error->last_seen->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Niveau</h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                {{ $error->level === 'ERROR' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300' : 
                   ($error->level === 'WARNING' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' : 
                   'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300') }}">
                {{ $error->level }}
            </span>
        </div>
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Compteur</h3>
            <p class="text-sm text-gray-900 dark:text-white">{{ $error->count }} fois</p>
        </div>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Addon</h3>
        <p class="text-sm text-gray-900 dark:text-white">{{ $error->addon ?? 'N/A' }}</p>
    </div>

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Message d'erreur</h3>
        <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-800 rounded-md">
            <p class="text-sm font-mono text-gray-900 dark:text-white break-words">{{ $error->message }}</p>
        </div>
    </div>

    @if($error->stack_trace)
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Stack trace</h3>
            <div class="mt-1 p-3 bg-gray-50 dark:bg-gray-800 rounded-md max-h-64 overflow-y-auto">
                <pre class="text-xs text-gray-900 dark:text-white whitespace-pre-wrap">{{ $error->stack_trace }}</pre>
            </div>
        </div>
    @endif

    <div>
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Statut</h3>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
            {{ $error->resolved ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' }}">
            {{ $error->resolved ? 'Résolu' : 'Ouvert' }}
        </span>
    </div>

    @if($error->resolved_at)
        <div>
            <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Résolu le</h3>
            <p class="text-sm text-gray-900 dark:text-white">{{ $error->resolved_at->format('d/m/Y H:i:s') }}</p>
        </div>
    @endif
</div>
