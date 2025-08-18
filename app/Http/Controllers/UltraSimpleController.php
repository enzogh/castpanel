<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UltraSimpleController extends Controller
{
    public function test()
    {
        try {
            Log::info('UltraSimpleController::test - Début');
            
            // Test 1: Connexion de base
            $connection = config('database.default');
            $database = config('database.connections.' . $connection . '.database');
            
            Log::info('Configuration DB', [
                'connection' => $connection,
                'database' => $database,
            ]);
            
            // Test 2: Connexion PDO
            $pdo = DB::connection()->getPdo();
            Log::info('PDO OK', [
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
            ]);
            
            // Test 3: Vérifier les tables
            $tables = [];
            if ($connection === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            } else {
                $tables = DB::select('SHOW TABLES');
            }
            
            Log::info('Tables trouvées', ['tables' => $tables]);
            
            // Test 4: Vérifier le contenu des tables
            $results = [];
            
            if (in_array('users', array_column($tables, 'name'))) {
                $userCount = DB::table('users')->count();
                $results['users'] = $userCount;
                Log::info('Users count', ['count' => $userCount]);
            }
            
            if (in_array('servers', array_column($tables, 'name'))) {
                $serverCount = DB::table('servers')->count();
                $results['servers'] = $serverCount;
                Log::info('Servers count', ['count' => $serverCount]);
            }
            
            if (in_array('tickets', array_column($tables, 'name'))) {
                $ticketCount = DB::table('tickets')->count();
                $results['tickets'] = $ticketCount;
                Log::info('Tickets count', ['count' => $ticketCount]);
                
                // Vérifier le ticket ID 1
                $ticket1 = DB::table('tickets')->where('id', 1)->first();
                if ($ticket1) {
                    $results['ticket_1'] = [
                        'id' => $ticket1->id,
                        'title' => $ticket1->title,
                        'user_id' => $ticket1->user_id,
                        'server_id' => $ticket1->server_id,
                    ];
                    Log::info('Ticket 1 trouvé', $results['ticket_1']);
                } else {
                    $results['ticket_1'] = null;
                    Log::warning('Ticket 1 non trouvé');
                }
            }
            
            if (in_array('ticket_messages', array_column($tables, 'name'))) {
                $messageCount = DB::table('ticket_messages')->count();
                $results['ticket_messages'] = $messageCount;
                Log::info('Ticket messages count', ['count' => $messageCount]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Test ultra-simple réussi',
                'database_info' => [
                    'connection' => $connection,
                    'database' => $database,
                    'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
                ],
                'tables' => array_column($tables, 'name'),
                'results' => $results,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur UltraSimpleController', [
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
