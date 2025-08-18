<?php

use App\Livewire\Installer\PanelInstaller;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RequireTwoFactorAuthentication;
use App\Http\Controllers\DebugTicketController;

Route::get('/', function () {
    return redirect('/app');
});

Route::get('installer', PanelInstaller::class)->name('installer')
    ->withoutMiddleware(['auth', RequireTwoFactorAuthentication::class]);

// Routes de débogage pour les tickets
Route::prefix('debug')->group(function () {
    Route::get('/tickets', [DebugTicketController::class, 'index'])->name('debug.tickets.index');
    Route::post('/tickets', [DebugTicketController::class, 'createTicket'])->name('debug.tickets.create');
});

// Route pour forcer la création du ticket
Route::get('/force-ticket', [App\Http\Controllers\ForceTicketController::class, 'create'])->name('force.ticket.create');
