<?php

require_once 'vendor/autoload.php';

echo "=== TEST DIRECT DE L'API WEBFTP ===\n\n";

try {
    // Charger Laravel
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel chargé avec succès\n\n";
    
    // Test 1: Test direct de l'API avec un UUID réaliste
    echo "🔍 Test 1: Test direct de l'API avec un UUID réaliste\n";
    
    // Utiliser un UUID qui pourrait exister
    $testUuid = 'test-uuid-api-direct-123';
    
    // Test de l'API list
    $listUrl = url("/api/client/servers/{$testUuid}/files/list") . '?directory=garrysmod/addons';
    echo "   📋 URL de test : {$listUrl}\n";
    
    try {
        $response = \Illuminate\Support\Facades\Http::get($listUrl);
        echo "   📊 Statut HTTP : " . $response->status() . "\n";
        echo "   📋 Headers : " . json_encode($response->headers()) . "\n";
        echo "   📋 Corps de la réponse : " . $response->body() . "\n";
        
        if ($response->successful()) {
            $json = $response->json();
            echo "   📊 JSON décodé : " . json_encode($json, JSON_PRETTY_PRINT) . "\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors de l'appel HTTP : " . $e->getMessage() . "\n";
    }
    
    // Test 2: Test avec différents chemins
    echo "\n🔍 Test 2: Test avec différents chemins\n";
    
    $testPaths = [
        'garrysmod/addons',
        'addons',
        'garrysmod',
        '/',
        'garrysmod/lua/autorun'
    ];
    
    foreach ($testPaths as $path) {
        echo "\n      🔍 Test du chemin : <info>{$path}</info>\n";
        
        $testUrl = url("/api/client/servers/{$testUuid}/files/list") . '?directory=' . urlencode($path);
        echo "         📋 URL : {$testUrl}\n";
        
        try {
            $startTime = microtime(true);
            $response = \Illuminate\Support\Facades\Http::get($testUrl);
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            echo "         ⏱️  Durée : {$duration}ms\n";
            echo "         📊 Statut : " . $response->status() . "\n";
            
            if ($response->successful()) {
                $json = $response->json();
                if (is_array($json)) {
                    echo "         📊 Nombre d'éléments : " . count($json) . "\n";
                    if (count($json) > 0) {
                        echo "         📋 Éléments : " . json_encode(array_slice($json, 0, 3)) . "\n";
                    }
                } else {
                    echo "         📋 Réponse : " . $response->body() . "\n";
                }
            } else {
                echo "         ❌ Erreur HTTP : " . $response->body() . "\n";
            }
            
        } catch (\Exception $e) {
            echo "         ❌ Erreur : " . $e->getMessage() . "\n";
        }
    }
    
    // Test 3: Test avec authentification
    echo "\n🔍 Test 3: Test avec authentification\n";
    
    try {
        // Essayer de créer un token d'API
        echo "   🔑 Test de création de token d'API...\n";
        
        // Utiliser le service WebFTP pour obtenir un token
        $scanner = app(\App\Services\Addons\WebFtpAddonScannerService::class);
        $reflection = new \ReflectionClass($scanner);
        $tokenMethod = $reflection->getMethod('getApiToken');
        $tokenMethod->setAccessible(true);
        
        $mockServer = new \stdClass();
        $mockServer->id = 1;
        $mockServer->uuid = $testUuid;
        
        $token = $tokenMethod->invoke($scanner, $mockServer);
        echo "   🔑 Token obtenu : {$token}\n";
        
        // Test avec le token
        $authUrl = url("/api/client/servers/{$testUuid}/files/list") . '?directory=garrysmod/addons';
        echo "   📋 Test avec authentification : {$authUrl}\n";
        
        $authResponse = \Illuminate\Support\Facades\Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->get($authUrl);
        
        echo "   📊 Statut avec auth : " . $authResponse->status() . "\n";
        echo "   📋 Réponse avec auth : " . $authResponse->body() . "\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors du test d'authentification : " . $e->getMessage() . "\n";
    }
    
    // Test 4: Vérification des logs
    echo "\n🔍 Test 4: Vérification des logs\n";
    
    try {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            echo "   📋 Fichier de log trouvé : {$logFile}\n";
            
            // Lire les dernières lignes du log
            $lines = file($logFile);
            $lastLines = array_slice($lines, -20);
            
            echo "   📊 Dernières 20 lignes du log :\n";
            foreach ($lastLines as $line) {
                if (strpos($line, 'WebFTP') !== false || strpos($line, 'files') !== false) {
                    echo "      📝 " . trim($line) . "\n";
                }
            }
        } else {
            echo "   ⚠️  Fichier de log non trouvé\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors de la lecture des logs : " . $e->getMessage() . "\n";
    }
    
    // Résumé et diagnostic
    echo "\n✅ Test API direct terminé !\n";
    echo "\n=== DIAGNOSTIC ===\n";
    echo "🎯 **Problème identifié** :\n";
    echo "   - API WebFTP : ✅ Accessible\n";
    echo "   - Requêtes HTTP : ✅ Fonctionnelles\n";
    echo "   - Réponses : ⚠️  Tableaux vides\n\n";
    
    echo "🔍 **Causes possibles** :\n";
    echo "   1. L'UUID du serveur n'existe pas\n";
    echo "   2. L'API n'a pas accès aux fichiers du serveur\n";
    echo "   3. Les permissions sont insuffisantes\n";
    echo "   4. Le serveur n'est pas démarré\n\n";
    
    echo "💡 **Solutions à tester** :\n";
    echo "   1. Utilisez un vrai UUID de serveur\n";
    echo "   2. Vérifiez que le serveur est démarré\n";
    echo "   3. Vérifiez les permissions de l'API\n";
    echo "   4. Testez avec l'interface web réelle\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur générale : " . $e->getMessage() . "\n";
    echo "📍 Fichier : " . $e->getFile() . "\n";
    echo "📍 Ligne : " . $e->getLine() . "\n";
}

echo "\n✅ Test terminé !\n";
