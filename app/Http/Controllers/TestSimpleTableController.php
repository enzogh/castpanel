<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestSimpleTableController extends Controller
{
    public function test()
    {
        try {
            Log::info('TestSimpleTableController::test - Début');
            
            // Test 1: Connexion
            $connection = config('database.default');
            $pdo = DB::connection()->getPdo();
            
            Log::info('Connexion OK', [
                'connection' => $connection,
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
            ]);
            
            // Test 2: Vérifier si la table test existe
            $tables = [];
            if ($connection === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            } else {
                $tables = DB::select('SHOW TABLES');
            }
            
            $tableNames = array_column($tables, 'name');
            Log::info('Tables existantes', ['tables' => $tableNames]);
            
            // Test 3: Créer une table test simple si elle n'existe pas
            if (!in_array('test_table', $tableNames)) {
                Log::info('Création de la table test_table');
                
                if ($connection === 'sqlite') {
                    DB::statement('
                        CREATE TABLE test_table (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            name VARCHAR(255) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ');
                } else {
                    DB::statement('
                        CREATE TABLE test_table (
                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ');
                }
                
                Log::info('Table test_table créée');
            } else {
                Log::info('Table test_table existe déjà');
            }
            
            // Test 4: Insérer une donnée de test
            $testId = DB::table('test_table')->insertGetId([
                'name' => 'Test - ' . now()->format('Y-m-d H:i:s'),
                'created_at' => now(),
            ]);
            
            Log::info('Donnée de test insérée', ['id' => $testId]);
            
            // Test 5: Vérifier la donnée
            $testData = DB::table('test_table')->where('id', $testId)->first();
            
            if ($testData) {
                Log::info('Donnée de test récupérée', [
                    'id' => $testData->id,
                    'name' => $testData->name,
                ]);
            }
            
            // Test 6: Compter les données
            $count = DB::table('test_table')->count();
            Log::info('Nombre de données', ['count' => $count]);
            
            return response()->json([
                'success' => true,
                'message' => 'Test de table simple réussi',
                'data' => [
                    'table_created' => !in_array('test_table', $tableNames),
                    'test_id' => $testId,
                    'total_count' => $count,
                ],
                'connection' => $connection,
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
            ]);
            
        } catch (\Exception $e) {
            Log::error('TestSimpleTableController erreur', [
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
