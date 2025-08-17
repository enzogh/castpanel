<div class="p-6">
    <div class="flex items-start space-x-6">
        <!-- Image de l'addon -->
        <div class="flex-shrink-0">
            @if($addon->image_url)
                <img src="{{ $addon->image_url }}" alt="{{ $addon->name }}" class="w-24 h-24 rounded-lg object-cover">
            @else
                <div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                </div>
            @endif
        </div>

        <!-- Informations principales -->
        <div class="flex-1 space-y-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $addon->name }}</h2>
                <p class="text-gray-600 dark:text-gray-400">par {{ $addon->author }}</p>
            </div>

            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    {{ App\Models\Addon::getCategories()[$addon->category] ?? $addon->category }}
                </span>
                <span class="text-sm text-gray-500 dark:text-gray-400">Version {{ $addon->version }}</span>
                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $addon->formatted_file_size }}</span>
            </div>

            <!-- Tags -->
            @if($addon->tags && count($addon->tags) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($addon->tags as $tag)
                        <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300">
                            {{ $tag }}
                        </span>
                    @endforeach
                </div>
            @endif

            <!-- Description -->
            <div class="prose prose-sm max-w-none dark:prose-invert">
                <p>{{ $addon->description }}</p>
            </div>

            <!-- Statistiques -->
            <div class="flex items-center space-x-6 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ number_format($addon->downloads_count) }} téléchargements
                </div>
                
                @if($addon->rating > 0)
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1 text-yellow-400" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                        {{ $addon->rating }}/5
                    </div>
                @endif
            </div>

            <!-- Liens -->
            <div class="flex items-center space-x-4">
                @if($addon->repository_url)
                    <a href="{{ $addon->repository_url }}" target="_blank" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                        </svg>
                        Repository
                    </a>
                @endif

                @if($addon->documentation_url)
                    <a href="{{ $addon->documentation_url }}" target="_blank" class="inline-flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                        Documentation
                    </a>
                @endif
            </div>

            <!-- Instructions d'installation -->
            @if($addon->installation_instructions)
                <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Instructions d'installation</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">{{ $addon->installation_instructions }}</div>
                </div>
            @endif

            <!-- Prérequis -->
            @if($addon->requirements && count($addon->requirements) > 0)
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                    <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200 mb-2">Prérequis</h3>
                    <ul class="text-sm text-yellow-700 dark:text-yellow-300 space-y-1">
                        @foreach($addon->requirements as $requirement => $details)
                            <li>• {{ is_array($details) ? $details['name'] ?? $requirement : $details }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>