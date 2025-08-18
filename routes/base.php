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

// Route pour tester la base de données
Route::get('/test-db', [App\Http\Controllers\TestDatabaseController::class, 'test'])->name('test.database');

// Route pour forcer la création de tout
Route::get('/force-create-all', [App\Http\Controllers\ForceCreateAllController::class, 'create'])->name('force.create.all');

// Route pour test ultra-simple
Route::get('/ultra-simple', [App\Http\Controllers\UltraSimpleController::class, 'test'])->name('ultra.simple');

// Route pour forcer la création de tout (DB::table() uniquement)
Route::get('/force-create-all-db', [App\Http\Controllers\ForceCreateAllDBController::class, 'create'])->name('force.create.all.db');

// Route pour tester la connexion de base
Route::get('/test-connection', [App\Http\Controllers\TestConnectionController::class, 'test'])->name('test.connection');

// Route pour tester la création de table simple
Route::get('/test-simple-table', [App\Http\Controllers\TestSimpleTableController::class, 'test'])->name('test.simple.table');
