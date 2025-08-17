<div wire:poll.15s
     class="relative cursor-pointer group transition-all duration-200 hover:scale-105 hover:shadow-lg"
     x-on:click="window.location.href = '{{ \App\Filament\Server\Pages\Console::getUrl(panel: 'server', tenant: $server) }}'">

    <div class="absolute left-0 top-0 bottom-0 w-1 rounded-l-lg"
         style="background-color: {{ $server->condition->getColor(true) }};">
    </div>

    <div class="flex-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden shadow-sm group-hover:shadow-md transition-shadow duration-200">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-gray-50 to-white dark:from-gray-800 dark:to-gray-750 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-filament::icon-button
                        :icon="$server->condition->getIcon()"
                        :color="$server->condition->getColor()"
                        :tooltip="$server->condition->getLabel()"
                        size="lg"
                    />
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ $server->name }}
                        </h2>
                        @if($server->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 truncate max-w-xs">
                                {{ $server->description }}
                            </p>
                        @endif
                    </div>
                </div>
                <div x-on:click.stop>
                    <x-filament-tables::actions
                        :actions="\App\Filament\App\Resources\ServerResource\Pages\ListServers::getPowerActions(view: 'grid')"
                        :alignment="\Filament\Support\Enums\Alignment::Center"
                        :record="$server"
                    />
                </div>
            </div>
        </div>

        <!-- Server Info Section -->
        <div class="px-4 py-3">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="tabler-clock" class="w-4 h-4 text-gray-500" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Uptime: {{ $server->formatResource(\App\Enums\ServerResourceType::Uptime) }}
                    </span>
                </div>
                @if($server->allocation?->address)
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="tabler-network" class="w-4 h-4 text-gray-500" />
                        <span class="text-sm font-mono text-blue-600 dark:text-blue-400">
                            {{ $server->allocation->address }}
                        </span>
                    </div>
                @endif
            </div>

            <!-- Game/Egg Info -->
            @if($server->egg)
                <div class="flex items-center gap-2 mb-3">
                    <x-filament::icon icon="tabler-package" class="w-4 h-4 text-gray-500" />
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $server->egg->name }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Resources Section -->
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                <x-filament::icon icon="tabler-chart-bar" class="w-4 h-4" />
                Ressources
            </h3>
            <div class="grid grid-cols-3 gap-4">
                <!-- CPU -->
                <div class="text-center">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <x-filament::icon icon="tabler-cpu" class="w-4 h-4 text-blue-500" />
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">CPU</p>
                    </div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ $server->formatResource(\App\Enums\ServerResourceType::CPU) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        / {{ $server->formatResource(\App\Enums\ServerResourceType::CPULimit) }}
                    </p>
                </div>

                <!-- Memory -->
                <div class="text-center">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <x-filament::icon icon="tabler-device-desktop-analytics" class="w-4 h-4 text-green-500" />
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">RAM</p>
                    </div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ $server->formatResource(\App\Enums\ServerResourceType::Memory) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        / {{ $server->formatResource(\App\Enums\ServerResourceType::MemoryLimit) }}
                    </p>
                </div>

                <!-- Disk -->
                <div class="text-center">
                    <div class="flex items-center justify-center gap-1 mb-1">
                        <x-filament::icon icon="tabler-device-sd-card" class="w-4 h-4 text-purple-500" />
                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Disk</p>
                    </div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                        {{ $server->formatResource(\App\Enums\ServerResourceType::Disk) }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        / {{ $server->formatResource(\App\Enums\ServerResourceType::DiskLimit) }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-4 py-2 bg-gray-100 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>ID: {{ $server->uuidShort }}</span>
                <span>Node: {{ $server->node->name }}</span>
            </div>
        </div>
    </div>
</div>
