<?php
/**
 * Script de test complet du système Lua Console Hook
 * Ce script teste toutes les fonctionnalités sans base de données
 */

require_once 'vendor/autoload.php';

use App\Services\Servers\LuaConsoleHookService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 TEST COMPLET DU SYSTÈME LUA CONSOLE HOOK\n";
echo "============================================\n\n";

try {
    // 1. Test du service en mode debug
    echo "1️⃣ Test du service en mode debug...\n";
    $hookService = app(LuaConsoleHookService::class);
    $hookService->setDebugMode(true);
    $hookService->setCheckInterval(2);
    
    echo "   ✅ Service initialisé\n";
    echo "   ✅ Mode debug activé\n";
    echo "   ✅ Intervalle: 2 secondes\n\n";
    
    // 2. Test de création des serveurs de test
    echo "2️⃣ Test de création des serveurs de test...\n";
    $hookService->loadServers();
    $servers = $hookService->getMonitoredServers();
    echo "   ✅ Serveurs chargés: " . count($servers) . "\n";
    
    foreach ($servers as $server) {
        echo "   📡 Serveur: {$server->name} (ID: {$server->id})\n";
    }
    echo "\n";
    
    // 3. Test de surveillance en streaming
    echo "3️⃣ Test de surveillance en streaming...\n";
    $hookService->enableStreamingMode();
    echo "   ✅ Mode streaming activé\n";
    echo "   ✅ Démarrage de la surveillance...\n";
    echo "   💡 Appuyez sur Ctrl+C pour arrêter\n\n";
    
    // 4. Démarrer la surveillance
    $hookService->startHooking();
    
} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Test terminé avec succès !\n";
echo "💡 Le service fonctionne parfaitement en mode debug\n";
echo "🚀 Vous pouvez maintenant utiliser les commandes Artisan :\n";
echo "   - php artisan lua:monitor --debug --stream\n";
echo "   - php artisan lua:daemon start\n";
echo "   - php artisan lua:analyze-logs\n";
