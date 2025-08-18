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
                    
                    // Debug rôles détaillé
                    $userRoles = $user->roles()->pluck('name')->toArray();
                    $hasAdminRole = $user->hasRole('admin');
                    $hasRootRole = $user->hasRole('root');
                    $hasSuperAdminRole = $user->hasRole('super-admin');
                    
                    // Tous les rôles dans la base
                    $allRoles = \App\Models\Role::pluck('name')->toArray();
                    
                    // Tickets avec leurs détails
                    $ticketDetails = Ticket::select('id', 'title', 'user_id', 'status')->get()->map(function ($ticket) {
                        return "#{$ticket->id}: {$ticket->title} (User: {$ticket->user_id}, Status: {$ticket->status})";
                    })->toArray();
                    
                    $debugInfo = "User: {$user->email} | ";
                    $debugInfo .= "Roles: " . (empty($userRoles) ? 'AUCUN' : implode(', ', $userRoles)) . " | ";
                    $debugInfo .= "hasRole('admin'): " . ($hasAdminRole ? 'OUI' : 'NON') . " | ";
                    $debugInfo .= "hasRole('root'): " . ($hasRootRole ? 'OUI' : 'NON') . " | ";
                    $debugInfo .= "hasRole('super-admin'): " . ($hasSuperAdminRole ? 'OUI' : 'NON') . " | ";
                    $debugInfo .= "Tous rôles dispo: " . implode(', ', $allRoles) . " | ";
                    $debugInfo .= "Total tickets: {$totalTickets}";
                    
                    Notification::make()
                        ->warning()
                        ->title('Debug: Système de rôles')
                        ->body($debugInfo)
                        ->persistent()
                        ->send();
                }),
            
            Actions\CreateAction::make(),
        ];
    }
}