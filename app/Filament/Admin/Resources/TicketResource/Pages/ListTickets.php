<?php

namespace App\Filament\Admin\Resources\TicketResource\Pages;

use App\Filament\Admin\Resources\TicketResource;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;

class ListTickets extends ListRecords
{
    protected static string $resource = TicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('debug_tickets')
                ->label('Debug Tickets')
                ->icon('heroicon-o-bug-ant')
                ->color('warning')
                ->action(function () {
                    $totalTickets = Ticket::count();
                    $ticketIds = Ticket::pluck('id')->toArray();
                    $user = auth()->user();
                    $userRoles = $user->roles()->pluck('name')->toArray();
                    
                    // Tickets avec leurs détails
                    $ticketDetails = Ticket::select('id', 'title', 'user_id', 'status')->get()->map(function ($ticket) {
                        return "#{$ticket->id}: {$ticket->title} (User: {$ticket->user_id}, Status: {$ticket->status})";
                    })->toArray();
                    
                    Notification::make()
                        ->warning()
                        ->title('Debug: Tickets en base')
                        ->body("User: {$user->email} | Roles: " . implode(', ', $userRoles) . " | Total: {$totalTickets} tickets | Détails: " . implode(' | ', $ticketDetails))
                        ->persistent()
                        ->send();
                }),
            
            Actions\CreateAction::make(),
        ];
    }
}