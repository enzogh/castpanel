<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TestConnectionController extends Controller
{
    public function test()
    {
        try {
            Log::info('TestConnectionController::test - Début');
            
            // Test 1: Configuration de base
            $connection = config('database.default');
            Log::info('Connection config', ['connection' => $connection]);
            
            // Test 2: Connexion DB
            $dbConnection = DB::connection();
            Log::info('DB connection OK');
            
            // Test 3: PDO
            $pdo = $dbConnection->getPdo();
            Log::info('PDO OK', [
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
            ]);
            
            // Test 4: Simple query
            $result = DB::select('SELECT 1 as test');
            Log::info('Simple query OK', ['result' => $result]);
            
            return response()->json([
                'success' => true,
                'message' => 'Connexion OK',
                'connection' => $connection,
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
            ]);
            
        } catch (\Exception $e) {
            Log::error('TestConnectionController erreur', [
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
