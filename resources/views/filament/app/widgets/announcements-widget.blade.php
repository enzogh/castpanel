<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-megaphone class="h-5 w-5" />
                Annonces
            </div>
        </x-slot>

        @if($this->getViewData()['announcements']->isEmpty())
            <div class="text-center py-6">
                <x-heroicon-o-information-circle class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">Aucune annonce</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Il n'y a actuellement aucune annonce à afficher.</p>
            </div>
        @else
            <div class="space-y-4">
                @foreach($this->getViewData()['announcements'] as $announcement)
                    <div class="relative rounded-lg border p-4 {{ match($announcement->type) {
                        'info' => 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950',
                        'warning' => 'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-950',
                        'success' => 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-950',
                        'danger' => 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950',
                        'maintenance' => 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950',
                        default => 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-950'
                    } }}">
                        
                        @if($announcement->is_pinned)
                            <div class="absolute -top-2 -right-2">
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <x-heroicon-o-star class="h-3 w-3 mr-1" />
                                    Épinglé
                                </span>
                            </div>
                        @endif

                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                @switch($announcement->type)
                                    @case('info')
                                        <x-heroicon-o-information-circle class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                        @break
                                    @case('warning')
                                        <x-heroicon-o-exclamation-triangle class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                                        @break
                                    @case('success')
                                        <x-heroicon-o-check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                                        @break
                                    @case('danger')
                                        <x-heroicon-o-x-circle class="h-6 w-6 text-red-600 dark:text-red-400" />
                                        @break
                                    @case('maintenance')
                                        <x-heroicon-o-wrench-screwdriver class="h-6 w-6 text-gray-600 dark:text-gray-400" />
                                        @break
                                    @default
                                        <x-heroicon-o-information-circle class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                @endswitch
                            </div>

                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium {{ match($announcement->type) {
                                    'info' => 'text-blue-900 dark:text-blue-100',
                                    'warning' => 'text-yellow-900 dark:text-yellow-100',
                                    'success' => 'text-green-900 dark:text-green-100',
                                    'danger' => 'text-red-900 dark:text-red-100',
                                    'maintenance' => 'text-gray-900 dark:text-gray-100',
                                    default => 'text-blue-900 dark:text-blue-100'
                                } }}">
                                    {{ $announcement->title }}
                                </h4>
                                
                                <div class="mt-2 text-sm {{ match($announcement->type) {
                                    'info' => 'text-blue-800 dark:text-blue-200',
                                    'warning' => 'text-yellow-800 dark:text-yellow-200',
                                    'success' => 'text-green-800 dark:text-green-200',
                                    'danger' => 'text-red-800 dark:text-red-200',
                                    'maintenance' => 'text-gray-800 dark:text-gray-200',
                                    default => 'text-blue-800 dark:text-blue-200'
                                } }} prose prose-sm max-w-none">
                                    {!! $announcement->content !!}
                                </div>

                                <div class="mt-3 flex items-center justify-between">
                                    <span class="text-xs {{ match($announcement->type) {
                                        'info' => 'text-blue-600 dark:text-blue-400',
                                        'warning' => 'text-yellow-600 dark:text-yellow-400',
                                        'success' => 'text-green-600 dark:text-green-400',
                                        'danger' => 'text-red-600 dark:text-red-400',
                                        'maintenance' => 'text-gray-600 dark:text-gray-400',
                                        default => 'text-blue-600 dark:text-blue-400'
                                    } }}">
                                        {{ $announcement->created_at->diffForHumans() }}
                                        @if($announcement->author)
                                            • Par {{ $announcement->author->email }}
                                        @endif
                                    </span>

                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium {{ match($announcement->type) {
                                        'info' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                        'warning' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'success' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'danger' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        'maintenance' => 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200',
                                        default => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
                                    } }}">
                                        {{ App\Models\Announcement::getTypes()[$announcement->type] ?? $announcement->type }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($this->getViewData()['announcements']->count() >= 5)
                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Affichage des 5 dernières annonces
                    </p>
                </div>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>