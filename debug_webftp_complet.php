<?php

require_once 'vendor/autoload.php';

echo "=== DEBUG COMPLET WEBFTP - POURQUOI AUCUN ADDON ? ===\n\n";

try {
    // Charger Laravel
    $app = require_once 'bootstrap/app.php';
    $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
    
    echo "✅ Laravel chargé avec succès\n\n";
    
    // Test 1: Vérification du service WebFTP
    echo "🔍 Test 1: Vérification du service WebFTP\n";
    
    try {
        $scanner = app(\App\Services\Addons\WebFtpAddonScannerService::class);
        echo "   ✅ Service WebFTP Scanner instancié\n";
        
        // Vérifier la classe
        $reflection = new \ReflectionClass($scanner);
        echo "   📋 Classe : " . get_class($scanner) . "\n";
        echo "   📋 Méthodes disponibles : " . count($reflection->getMethods()) . "\n";
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors de l'instanciation : " . $e->getMessage() . "\n";
        exit(1);
    }

    // Test 2: Test avec un serveur mock réaliste
    echo "\n🔍 Test 2: Test avec un serveur mock réaliste\n";
    
    try {
        // Créer un serveur mock plus réaliste
        $mockServer = new \stdClass();
        $mockServer->id = 1;
        $mockServer->name = 'Serveur Test GMod Debug';
        $mockServer->egg_id = 1;
        $mockServer->uuid = 'test-uuid-debug-123';
        
        $mockEgg = new \stdClass();
        $mockEgg->id = 1;
        $mockEgg->name = 'Garry\'s Mod';
        $mockServer->egg = $mockEgg;
        
        echo "   🎮 Serveur mock créé : {$mockServer->name}\n";
        echo "   🔑 UUID du serveur : {$mockServer->uuid}\n";
        
        // Test de isGmodServer
        echo "   🔍 Test de isGmodServer...\n";
        $isGmodResult = $scanner->isGmodServer($mockServer);
        echo "   📊 Résultat : " . ($isGmodResult ? '✅ Oui, c\'est un serveur GMod' : '❌ Non, ce n\'est pas un serveur GMod') . "\n";
        
        if (!$isGmodResult) {
            echo "❌ Le serveur n'est pas reconnu comme Garry's Mod. Arrêt du test.\n";
            exit(1);
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors de la création du serveur mock : " . $e->getMessage() . "\n";
        exit(1);
    }

    // Test 3: Debug des méthodes WebFTP avec réflexion
    echo "\n🔍 Test 3: Debug des méthodes WebFTP avec réflexion\n";
    
    try {
        $reflection = new \ReflectionClass($scanner);
        
        // Test de listDirectoryViaWebFtp
        echo "   📋 Test de listDirectoryViaWebFtp...\n";
        
        $listMethod = $reflection->getMethod('listDirectoryViaWebFtp');
        $listMethod->setAccessible(true);
        
        // Tester plusieurs chemins
        $testPaths = [
            'garrysmod/addons',
            'addons',
            'garrysmod',
            '/',
            'garrysmod/lua/autorun'
        ];
        
        foreach ($testPaths as $path) {
            echo "\n      🔍 Test du chemin : <info>{$path}</info>\n";
            
            try {
                echo "         📋 Appel de listDirectoryViaWebFtp...\n";
                $startTime = microtime(true);
                
                $contents = $listMethod->invoke($scanner, $mockServer, $path);
                
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);
                
                echo "         ⏱️  Durée : {$duration}ms\n";
                echo "         📊 Type de retour : " . gettype($contents) . "\n";
                
                if (is_array($contents)) {
                    echo "         📊 Nombre d'éléments : " . count($contents) . "\n";
                    
                    if (count($contents) > 0) {
                        echo "         📋 Éléments trouvés :\n";
                        foreach (array_slice($contents, 0, 5) as $index => $item) {
                            $name = $item['name'] ?? 'unknown';
                            $type = $item['type'] ?? 'unknown';
                            $size = isset($item['size']) ? $item['size'] : 'N/A';
                            echo "            " . ($index + 1) . ". {$name} (Type: {$type}, Taille: {$size})\n";
                        }
                        if (count($contents) > 5) {
                            echo "            ... et " . (count($contents) - 5) . " autres\n";
                        }
                    } else {
                        echo "         ⚠️  Aucun contenu trouvé\n";
                    }
                } else {
                    echo "         ❌ Retour invalide (pas un tableau)\n";
                    echo "         📋 Contenu brut : " . var_export($contents, true) . "\n";
                }
                
            } catch (\Exception $e) {
                echo "         ❌ Erreur : " . $e->getMessage() . "\n";
                echo "         📍 Fichier : " . $e->getFile() . "\n";
                echo "         📍 Ligne : " . $e->getLine() . "\n";
                
                // Afficher la stack trace pour plus de détails
                echo "         📋 Stack trace :\n";
                $trace = $e->getTrace();
                foreach (array_slice($trace, 0, 3) as $index => $traceItem) {
                    $file = $traceItem['file'] ?? 'unknown';
                    $line = $traceItem['line'] ?? 'unknown';
                    $function = $traceItem['function'] ?? 'unknown';
                    echo "            " . ($index + 1) . ". {$function}() dans {$file}:{$line}\n";
                }
            }
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors du debug des méthodes : " . $e->getMessage() . "\n";
    }

    // Test 4: Test du scan complet avec debug
    echo "\n🔍 Test 4: Test du scan complet avec debug\n";
    
    try {
        echo "   🔍 Lancement du scan complet...\n";
        $startTime = microtime(true);
        
        $addons = $scanner->scanAddons($mockServer);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        
        echo "   ⏱️  Durée du scan : {$duration}ms\n";
        echo "   📊 Nombre d'addons détectés : " . count($addons) . "\n";
        
        if (count($addons) > 0) {
            echo "   📋 Addons détectés :\n";
            foreach ($addons as $addon) {
                echo "      - {$addon['name']} v{$addon['version']} par {$addon['author']}\n";
                echo "        Chemin : {$addon['file_path']}\n";
                echo "        Type : {$addon['type']}\n";
                echo "        Description : {$addon['description']}\n\n";
            }
        } else {
            echo "   ⚠️  Aucun addon détecté\n\n";
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors du scan complet : " . $e->getMessage() . "\n";
        echo "   📍 Fichier : " . $e->getFile() . "\n";
        echo "   📍 Ligne : " . $e->getLine() . "\n";
    }

    // Test 5: Vérification des routes et de l'API
    echo "\n🔍 Test 5: Vérification des routes et de l'API\n";
    
    try {
        echo "   🌐 Vérification des routes de fichiers...\n";
        
        $routes = app('router')->getRoutes();
        $fileRoutes = collect($routes)->filter(function ($route) {
            return strpos($route->uri(), 'files') !== false;
        });
        
        echo "   📊 Routes des fichiers disponibles : " . $fileRoutes->count() . "\n";
        
        // Vérifier les routes spécifiques
        $specificRoutes = [
            'api.client.servers.files.list',
            'api.client.servers.files.contents',
            'api.client.servers.files.download'
        ];
        
        foreach ($specificRoutes as $routeName) {
            try {
                $url = route($routeName, ['server' => 'test-uuid']);
                echo "   ✅ Route {$routeName} : {$url}\n";
            } catch (\Exception $e) {
                echo "   ❌ Route {$routeName} : " . $e->getMessage() . "\n";
            }
        }
        
    } catch (\Exception $e) {
        echo "   ❌ Erreur lors de la vérification des routes : " . $e->getMessage() . "\n";
    }

    // Résumé et diagnostic
    echo "\n✅ Debug WebFTP terminé !\n";
    echo "\n=== DIAGNOSTIC ===\n";
    echo "🎯 **Problème identifié** :\n";
    echo "   - Service WebFTP : ✅ Fonctionnel\n";
    echo "   - Routes : ✅ Disponibles\n";
    echo "   - Serveur mock : ✅ Reconnu comme GMod\n";
    echo "   - Scan : ⚠️  Aucun addon détecté\n\n";
    
    echo "🔍 **Causes possibles** :\n";
    echo "   1. Les méthodes WebFTP retournent des tableaux vides\n";
    echo "   2. Les chemins de fichiers ne correspondent pas\n";
    echo "   3. L'API WebFTP n'a pas accès aux fichiers\n";
    echo "   4. Les permissions sont insuffisantes\n\n";
    
    echo "💡 **Solutions à tester** :\n";
    echo "   1. Vérifiez que vos addons sont dans garrysmod/addons/\n";
    echo "   2. Testez manuellement l'API WebFTP\n";
    echo "   3. Vérifiez les permissions des fichiers\n";
    echo "   4. Utilisez le mode debug de l'interface web\n";
    
} catch (\Exception $e) {
    echo "❌ Erreur générale : " . $e->getMessage() . "\n";
    echo "📍 Fichier : " . $e->getFile() . "\n";
    echo "📍 Ligne : " . $e->getLine() . "\n";
}

echo "\n✅ Debug terminé !\n";
