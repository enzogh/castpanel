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
            Log::info('ForceTicketController::create - Début');
            
            // Vérifier la base de données active
            $connection = config('database.default');
            $database = config('database.connections.' . $connection . '.database');
            
            Log::info('Base de données active', [
                'connection' => $connection,
                'database' => $database,
            ]);
            
            // Créer ou récupérer un utilisateur
            $user = User::first();
            if (!$user) {
                $user = User::create([
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                    'password' => bcrypt('password'),
                ]);
                Log::info('Utilisateur créé', ['id' => $user->id]);
            }
            
            // Créer ou récupérer un serveur
            $server = Server::first();
            if (!$server) {
                $server = Server::create([
                    'name' => 'Serveur Principal',
                    'ip_address' => '127.0.0.1',
                    'status' => 'online',
                ]);
                Log::info('Serveur créé', ['id' => $server->id]);
            }
            
            // Vérifier si le ticket 1 existe
            $ticket = Ticket::find(1);
            if ($ticket) {
                // Mettre à jour le ticket existant
                $ticket->update([
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                ]);
                Log::info('Ticket mis à jour', ['id' => $ticket->id]);
            } else {
                // Créer le ticket
                $ticket = Ticket::create([
                    'id' => 1,
                    'user_id' => $user->id,
                    'server_id' => $server->id,
                    'title' => 'Bienvenue dans le système de tickets',
                    'description' => 'Ceci est votre premier ticket de bienvenue.',
                    'status' => 'open',
                    'priority' => 'medium',
                    'category' => 'general',
                ]);
                Log::info('Ticket créé', ['id' => $ticket->id]);
            }
            
            // Vérifier que le ticket est visible
            $visibleTicket = Ticket::where('id', 1)
                ->where('server_id', $server->id)
                ->first();
            
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
