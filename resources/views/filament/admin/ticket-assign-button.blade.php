@php
    $ticket = $getRecord();
    $user = auth()->user();
    $isAssignedToMe = $ticket->assigned_to === $user->id;
    $isUnassigned = !$ticket->assigned_to;
@endphp

<div class="flex items-center justify-center">
    @if($isUnassigned)
        <!-- Bouton pour s'assigner -->
        <button
            type="button"
            wire:click="mountTableAction('assign_me', '{{ $ticket->id }}')"
            class="inline-flex items-center justify-center gap-1 font-medium text-sm px-3 py-1.5 bg-success-600 text-white rounded-md shadow-sm hover:bg-success-700 focus:outline-none focus:ring-2 focus:ring-success-500 focus:ring-offset-1 transition-all duration-200 transform hover:scale-105"
            title="M'assigner ce ticket"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            <span class="hidden sm:inline">M'assigner</span>
        </button>
    @elseif($isAssignedToMe)
        <!-- Bouton pour se désassigner -->
        <button
            type="button"
            wire:click="mountTableAction('unassign_me', '{{ $ticket->id }}')"
            class="inline-flex items-center justify-center gap-1 font-medium text-sm px-3 py-1.5 bg-warning-600 text-white rounded-md shadow-sm hover:bg-warning-700 focus:outline-none focus:ring-2 focus:ring-warning-500 focus:ring-offset-1 transition-all duration-200 transform hover:scale-105"
            title="Me désassigner de ce ticket"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
            </svg>
            <span class="hidden sm:inline">Me désassigner</span>
        </button>
    @else
        <!-- Assigné à quelqu'un d'autre -->
        <div class="inline-flex items-center gap-1 text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-md">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <span class="hidden sm:inline">Assigné</span>
        </div>
    @endif
</div>