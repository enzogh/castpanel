<?php
/**
 * Script de test pour le contrôleur de débogage
 * Simule une requête HTTP vers /debug/tickets
 */

// Simuler une requête HTTP
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/debug/tickets';

// Inclure le fichier de débogage
echo "🔍 Test du contrôleur de débogage...\n";
echo "📁 Vérification des fichiers...\n";

// Vérifier que les fichiers existent
$files = [
    'app/Http/Controllers/DebugTicketController.php',
    'app/Filament/Server/Resources/TicketResource.php',
    'app/Filament/Server/Resources/TicketResource/Pages/ViewTicket.php',
    'routes/base.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}\n";
    } else {
        echo "❌ {$file}\n";
    }
}

echo "\n🔍 Vérification de la base de données...\n";

// Vérifier si la base de données SQLite existe
$dbPath = __DIR__ . '/database/database.sqlite';
if (file_exists($dbPath)) {
    echo "✅ Base de données SQLite trouvée: {$dbPath}\n";
    
    try {
        $pdo = new PDO("sqlite:{$dbPath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Vérifier les tables
        $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 Tables trouvées: " . implode(', ', $tables) . "\n";
        
        // Vérifier les tickets
        $ticketCount = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
        echo "🎫 Nombre de tickets: {$ticketCount}\n";
        
        // Vérifier le ticket ID 1
        $ticket1 = $pdo->query("SELECT * FROM tickets WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
        if ($ticket1) {
            echo "✅ Ticket ID 1 trouvé: {$ticket1['title']}\n";
        } else {
            echo "❌ Ticket ID 1 non trouvé\n";
        }
        
        // Vérifier les utilisateurs
        $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "👤 Nombre d'utilisateurs: {$userCount}\n";
        
        // Vérifier les serveurs
        $serverCount = $pdo->query("SELECT COUNT(*) FROM servers")->fetchColumn();
        echo "🖥️  Nombre de serveurs: {$serverCount}\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "❌ Base de données SQLite non trouvée\n";
    echo "💡 Créez d'abord la base de données avec le script SQLite\n";
}

echo "\n🎯 Prochaines étapes:\n";
echo "1. Accédez à /debug/tickets dans votre navigateur\n";
echo "2. Vérifiez les logs dans storage/logs/laravel.log\n";
echo "3. Essayez d'accéder à /server/1/tickets/1\n";
echo "4. Analysez les erreurs et logs\n";
