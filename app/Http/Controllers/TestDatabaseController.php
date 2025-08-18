<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestDatabaseController extends Controller
{
    public function test()
    {
        try {
            Log::info('TestDatabaseController::test - Début');
            
            // Vérifier la configuration de base de données
            $connection = config('database.default');
            $database = config('database.connections.' . $connection . '.database');
            
            Log::info('Configuration de base de données', [
                'connection' => $connection,
                'database' => $database,
            ]);
            
            // Tester la connexion
            $pdo = DB::connection()->getPdo();
            Log::info('Connexion PDO réussie', [
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
                'server_version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            ]);
            
            // Tester les requêtes selon le type de base
            if ($connection === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
                Log::info('Tables SQLite trouvées', ['tables' => $tables]);
                
                // Vérifier si la table tickets existe
                $ticketsTable = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name='tickets'");
                if (!empty($ticketsTable)) {
                    $ticketCount = DB::table('tickets')->count();
                    Log::info('Table tickets trouvée', ['count' => $ticketCount]);
                    
                    // Vérifier le ticket ID 1
                    $ticket1 = DB::table('tickets')->where('id', 1)->first();
                    if ($ticket1) {
                        Log::info('Ticket ID 1 trouvé', [
                            'id' => $ticket1->id,
                            'title' => $ticket1->title,
                            'user_id' => $ticket1->user_id,
                            'server_id' => $ticket1->server_id,
                        ]);
                    } else {
                        Log::warning('Ticket ID 1 non trouvé');
                    }
                } else {
                    Log::warning('Table tickets non trouvée');
                }
                
            } else {
                // MySQL/MariaDB
                $tables = DB::select('SHOW TABLES');
                Log::info('Tables MySQL trouvées', ['tables' => $tables]);
                
                // Vérifier si la table tickets existe
                $ticketsTable = DB::select("SHOW TABLES LIKE 'tickets'");
                if (!empty($ticketsTable)) {
                    $ticketCount = DB::table('tickets')->count();
                    Log::info('Table tickets trouvée', ['count' => $ticketCount]);
                    
                    // Vérifier le ticket ID 1
                    $ticket1 = DB::table('tickets')->where('id', 1)->first();
                    if ($ticket1) {
                        Log::info('Ticket ID 1 trouvé', [
                            'id' => $ticket1->id,
                            'title' => $ticket1->title,
                            'user_id' => $ticket1->user_id,
                            'server_id' => $ticket1->server_id,
                        ]);
                    } else {
                        Log::warning('Ticket ID 1 non trouvé');
                    }
                } else {
                    Log::warning('Table tickets non trouvée');
                }
            }
            
            // Tester la création d'un ticket
            try {
                $testTicket = DB::table('tickets')->insert([
                    'id' => 999,
                    'title' => 'Ticket de test - ' . now()->format('Y-m-d H:i:s'),
                    'description' => 'Ceci est un ticket de test',
                    'status' => 'open',
                    'priority' => 'medium',
                    'category' => 'general',
                    'user_id' => 1,
                    'server_id' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                Log::info('Ticket de test créé avec succès', ['id' => 999]);
                
                // Supprimer le ticket de test
                DB::table('tickets')->where('id', 999)->delete();
                Log::info('Ticket de test supprimé');
                
            } catch (\Exception $e) {
                Log::error('Erreur lors de la création du ticket de test', [
                    'error' => $e->getMessage(),
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Test de base de données réussi',
                'database_info' => [
                    'connection' => $connection,
                    'database' => $database,
                    'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur TestDatabaseController', [
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
