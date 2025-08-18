<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Server;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForceTicketController extends Controller
{
    public function create()
    {
        try {
            Log::info('ForceTicketController::create - Début (BYPASS AUTH)');
            
            // Vérifier la base de données active
            $connection = config('database.default');
            $database = config('database.connections.' . $connection . '.database');
            
            Log::info('Base de données active', [
                'connection' => $connection,
                'database' => $database,
            ]);
            
            // BYPASS AUTH : Créer un utilisateur de test
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ]);
                Log::info('Utilisateur créé', ['id' => $user->id]);
            } else {
                Log::info('Utilisateur existant', ['id' => $user->id]);
            }
            
            // Créer un serveur de test
            $server = Server::first();
            if (!$server) {
                $server = Server::create([
                    'name' => 'Serveur Principal',
                    'ip_address' => '127.0.0.1',
                    'status' => 'online',
                ]);
                Log::info('Serveur créé', ['id' => $server->id]);
            } else {
                Log::info('Serveur existant', ['id' => $server->id]);
            }
            
            // FORCER la création du ticket ID 1
            $ticket = Ticket::find(1);
            if ($ticket) {
                // Mettre à jour le ticket existant
                $ticket->update([
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                    'title' => 'Ticket de bienvenue - ' . now()->format('Y-m-d H:i:s'),
                ]);
                Log::info('Ticket mis à jour', ['id' => $ticket->id]);
            } else {
                // Créer le ticket avec un ID spécifique
                $ticket = new Ticket();
                $ticket->id = 1;
                $ticket->user_id = $user->id;
                $ticket->server_id = $server->id;
                $ticket->title = 'Ticket de bienvenue - ' . now()->format('Y-m-d H:i:s');
                $ticket->description = 'Ceci est un ticket créé automatiquement pour résoudre l\'erreur 404.';
                $ticket->status = 'open';
                $ticket->priority = 'medium';
                $ticket->category = 'general';
                $ticket->save();
                
                Log::info('Ticket créé', ['id' => $ticket->id]);
            }
            
            // Vérifier que le ticket est visible
            $visibleTicket = Ticket::where('id', 1)->first();
            
            if ($visibleTicket) {
                Log::info('SUCCÈS: Ticket visible', [
                    'id' => $visibleTicket->id,
                    'title' => $visibleTicket->title,
                    'user_id' => $visibleTicket->user_id,
                    'server_id' => $visibleTicket->server_id,
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Ticket créé avec succès',
                    'ticket' => [
                        'id' => $visibleTicket->id,
                        'title' => $visibleTicket->title,
                        'user_id' => $visibleTicket->user_id,
                        'server_id' => $visibleTicket->server_id,
                    ],
                    'database_info' => [
                        'connection' => $connection,
                        'database' => $database,
                    ],
                ]);
            } else {
                Log::error('ERREUR: Ticket toujours invisible');
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket créé mais toujours invisible',
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Erreur ForceTicketController', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
