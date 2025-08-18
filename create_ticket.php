<?php
/**
 * Script simple pour créer un ticket par défaut
 * Exécutez ce script directement avec PHP pour contourner les problèmes Composer
 */

// Configuration de la base de données (ajustez selon votre configuration)
$dbConfig = [
    'host' => 'localhost',
    'dbname' => 'castpanel', // ou le nom de votre base de données
    'username' => 'root',     // ou votre nom d'utilisateur
    'password' => '',         // ou votre mot de passe
    'charset' => 'utf8mb4'
];

try {
    // Connexion à la base de données
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connexion à la base de données réussie\n";
    
    // Vérifier si des tickets existent déjà
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM tickets");
    $ticketCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($ticketCount > 0) {
        echo "ℹ️  Des tickets existent déjà ({$ticketCount} tickets)\n";
    } else {
        echo "ℹ️  Aucun ticket trouvé, création d'un ticket par défaut...\n";
        
        // Récupérer le premier utilisateur et serveur
        $user = $pdo->query("SELECT id FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $server = $pdo->query("SELECT id FROM servers LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if (!$user || !$server) {
            echo "❌ Aucun utilisateur ou serveur trouvé. Créez d'abord des utilisateurs et serveurs.\n";
            exit(1);
        }
        
        echo "✅ Utilisateur ID: {$user['id']}\n";
        echo "✅ Serveur ID: {$server['id']}\n";
        
        // Créer le ticket
        $stmt = $pdo->prepare("
            INSERT INTO tickets (id, user_id, server_id, title, description, status, priority, category, created_at, updated_at)
            VALUES (1, ?, ?, 'Bienvenue dans le système de tickets', 'Ce ticket a été créé automatiquement pour vous permettre de commencer à utiliser le système de support.', 'open', 'medium', 'general', NOW(), NOW())
        ");
        
        if ($stmt->execute([$user['id'], $server['id']])) {
            echo "✅ Ticket créé avec succès (ID: 1)\n";
            
            // Créer le message initial
            $stmt = $pdo->prepare("
                INSERT INTO ticket_messages (ticket_id, user_id, message, is_internal, created_at, updated_at)
                VALUES (1, ?, 'Bienvenue ! Ce ticket a été créé automatiquement.', 0, NOW(), NOW())
            ");
            
            if ($stmt->execute([$user['id']])) {
                echo "✅ Message initial créé avec succès\n";
            } else {
                echo "⚠️  Message initial non créé\n";
            }
            
            echo "\n🎉 Ticket créé avec succès !\n";
            echo "🔗 Accédez maintenant à: /server/{$server['id']}/tickets/1\n";
            
        } else {
            echo "❌ Erreur lors de la création du ticket\n";
        }
    }
    
    // Afficher les tickets existants
    echo "\n📋 Tickets existants:\n";
    $tickets = $pdo->query("
        SELECT t.id, t.title, t.status, t.user_id, t.server_id
        FROM tickets t
        ORDER BY t.id
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tickets as $ticket) {
        echo "- Ticket #{$ticket['id']}: {$ticket['title']} (Status: {$ticket['status']})\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    echo "💡 Vérifiez votre configuration de base de données dans le script\n";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
