<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DebugTicketController extends Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $serverId = request()->route('tenant');
            
            Log::info('DebugTicketController::index', [
                'user' => $user ? $user->id : null,
                'server_id' => $serverId,
                'auth_check' => auth()->check(),
            ]);
            
            if (!$user) {
                return response()->json([
                    'error' => 'Utilisateur non authentifié',
                    'auth_check' => auth()->check(),
                ], 401);
            }
            
            // Vérifier la base de données
            $dbInfo = [
                'connection' => config('database.default'),
                'database' => config('database.connections.' . config('database.default') . '.database'),
            ];
            
            // Vérifier les tables
            $tables = [];
            try {
                $tables = DB::select('SELECT name FROM sqlite_master WHERE type="table"');
            } catch (\Exception $e) {
                $tables = ['error' => $e->getMessage()];
            }
            
            // Vérifier les tickets
            $tickets = [];
            try {
                $tickets = Ticket::all()->map(function($t) {
                    return [
                        'id' => $t->id,
                        'title' => $t->title,
                        'user_id' => $t->user_id,
                        'server_id' => $t->server_id,
                        'status' => $t->status,
                    ];
                });
            } catch (\Exception $e) {
                $tickets = ['error' => $e->getMessage()];
            }
            
            // Vérifier les utilisateurs
            $users = [];
            try {
                $users = User::all()->map(function($u) {
                    return [
                        'id' => $u->id,
                        'email' => $u->email,
                    ];
                });
            } catch (\Exception $e) {
                $users = ['error' => $e->getMessage()];
            }
            
            // Vérifier les serveurs
            $servers = [];
            try {
                $servers = Server::all()->map(function($s) {
                    return [
                        'id' => $s->id,
                        'name' => $s->name,
                    ];
                });
            } catch (\Exception $e) {
                $servers = ['error' => $e->getMessage()];
            }
            
            // Requête directe pour le ticket 1
            $ticket1 = null;
            try {
                $ticket1 = DB::table('tickets')->where('id', 1)->first();
            } catch (\Exception $e) {
                $ticket1 = ['error' => $e->getMessage()];
            }
            
            return response()->json([
                'success' => true,
                'debug_info' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                    ],
                    'server_id' => $serverId,
                    'database' => $dbInfo,
                    'tables' => $tables,
                    'tickets_count' => is_array($tickets) ? count($tickets) : 'error',
                    'users_count' => is_array($users) ? count($users) : 'error',
                    'servers_count' => is_array($servers) ? count($servers) : 'error',
                    'ticket_1' => $ticket1,
                ],
                'tickets' => $tickets,
                'users' => $users,
                'servers' => $servers,
            ]);
            
        } catch (\Exception $e) {
            Log::error('DebugTicketController::index error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Erreur lors du débogage',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    
    public function createTicket()
    {
        try {
            $user = auth()->user();
            $serverId = request()->route('tenant');
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }
            
            // Récupérer ou créer un serveur
            $server = Server::first();
            if (!$server) {
                $server = Server::create([
                    'name' => 'Serveur Principal',
                ]);
            }
            
            // Créer un ticket de test
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'server_id' => $server->id,
                'title' => 'Ticket de test - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'Ceci est un ticket de test créé pour résoudre l\'erreur 404.',
                'status' => 'open',
                'priority' => 'medium',
                'category' => 'general',
            ]);
            
            return response()->json([
                'success' => true,
                'ticket_created' => [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'user_id' => $ticket->user_id,
                    'server_id' => $ticket->server_id,
                ],
                'message' => 'Ticket de test créé avec succès',
            ]);
            
        } catch (\Exception $e) {
            Log::error('DebugTicketController::createTicket error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'error' => 'Erreur lors de la création du ticket',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
