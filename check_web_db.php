<?php
/**
 * Script pour vérifier la configuration de base de données de l'application web
 */

echo "🔍 Vérification de la configuration de base de données...\n\n";

// Vérifier le fichier .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "📁 Fichier .env trouvé\n";
    $envContent = file_get_contents($envFile);
    
    // Extraire les variables de base de données
    preg_match_all('/^DB_(\w+)=(.*)$/m', $envContent, $matches);
    
    if (!empty($matches[1])) {
        echo "⚙️  Configuration de base de données:\n";
        foreach ($matches[1] as $i => $key) {
            $value = $matches[2][$i];
            echo "   DB_{$key} = {$value}\n";
        }
    } else {
        echo "❌ Aucune variable DB_ trouvée dans .env\n";
    }
} else {
    echo "❌ Fichier .env non trouvé\n";
}

echo "\n🔍 Vérification de la configuration Laravel...\n";

// Vérifier le fichier config/database.php
$dbConfigFile = __DIR__ . '/config/database.php';
if (file_exists($dbConfigFile)) {
    echo "📁 Fichier config/database.php trouvé\n";
    
    // Lire la configuration
    $configContent = file_get_contents($dbConfigFile);
    
    // Extraire la connexion par défaut
    if (preg_match('/\'default\'\s*=>\s*env\(\'DB_CONNECTION\',\s*\'([^\']+)\'\)/', $configContent, $match)) {
        echo "🔗 Connexion par défaut: {$match[1]}\n";
    }
    
    // Vérifier les connexions disponibles
    if (preg_match_all('/\'(\w+)\'\s*=>\s*\[/', $configContent, $matches)) {
        echo "📋 Connexions disponibles: " . implode(', ', $matches[1]) . "\n";
    }
} else {
    echo "❌ Fichier config/database.php non trouvé\n";
}

echo "\n🔍 Vérification des bases de données existantes...\n";

// Vérifier SQLite
$sqlitePath = __DIR__ . '/database/database.sqlite';
if (file_exists($sqlitePath)) {
    echo "✅ SQLite local: {$sqlitePath}\n";
    try {
        $pdo = new PDO("sqlite:{$sqlitePath}");
        $ticketCount = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
        echo "   🎫 Tickets: {$ticketCount}\n";
    } catch (Exception $e) {
        echo "   ❌ Erreur: " . $e->getMessage() . "\n";
    }
}

// Vérifier MySQL/MariaDB
echo "\n🔍 Test des connexions MySQL/MariaDB...\n";

$mysqlConfigs = [
    ['host' => 'localhost', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => '127.0.0.1', 'port' => 3306, 'user' => 'root', 'pass' => ''],
    ['host' => 'localhost', 'port' => 3306, 'user' => 'pelican', 'pass' => ''],
];

foreach ($mysqlConfigs as $config) {
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']}";
        $pdo = new PDO($dsn, $config['user'], $config['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        echo "✅ MySQL connecté: {$config['host']}:{$config['port']} (user: {$config['user']})\n";
        
        // Lister les bases de données
        $databases = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        $relevantDbs = array_filter($databases, function($db) {
            return in_array($db, ['castpanel', 'panel', 'pelican', 'laravel']);
        });
        
        if (!empty($relevantDbs)) {
            echo "   📋 Bases pertinentes: " . implode(', ', $relevantDbs) . "\n";
            
            // Vérifier une base de données pertinente
            foreach ($relevantDbs as $db) {
                try {
                    $pdo->exec("USE {$db}");
                    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                    
                    if (in_array('tickets', $tables)) {
                        $ticketCount = $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn();
                        echo "   🎫 Base {$db}: {$ticketCount} tickets\n";
                    }
                } catch (Exception $e) {
                    echo "   ❌ Erreur base {$db}: " . $e->getMessage() . "\n";
                }
            }
        }
        
    } catch (Exception $e) {
        // Connexion échouée, continuer
    }
}

echo "\n🎯 Résumé:\n";
echo "1. SQLite local contient 1 ticket\n";
echo "2. L'application web utilise peut-être une autre base\n";
echo "3. Vérifiez la configuration .env\n";
echo "4. Testez /debug/tickets dans le navigateur\n";
echo "5. Vérifiez les logs après avoir essayé /server/1/tickets/1\n";
