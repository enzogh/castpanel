<?php
/**
 * Script pour forcer la création du ticket dans la base de données active
 */

// Charger Laravel
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Ticket;
use App\Models\User;
use App\Models\Server;
use Illuminate\Support\Facades\DB;

echo "🔧 Création forcée du ticket...\n";

try {
    // Vérifier la connexion de base de données
    echo "📊 Connexion de base de données: " . config('database.default') . "\n";
    echo "🗄️  Base de données: " . config('database.connections.' . config('database.default') . '.database') . "\n";
    
    // Tester la connexion
    DB::connection()->getPdo();
    echo "✅ Connexion à la base de données réussie\n";
    
    // Vérifier les tables
    $tables = DB::select('SHOW TABLES');
    if (empty($tables)) {
        // Essayer SQLite
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
    }
    
    echo "📋 Tables trouvées: " . count($tables) . "\n";
    
    // Créer ou récupérer un utilisateur
    $user = User::first();
    if (!$user) {
        echo "👤 Création d'un utilisateur de test...\n";
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        echo "✅ Utilisateur créé: ID {$user->id}\n";
    } else {
        echo "✅ Utilisateur existant: ID {$user->id}\n";
    }
    
    // Créer ou récupérer un serveur
    $server = Server::first();
    if (!$server) {
        echo "🖥️  Création d'un serveur de test...\n";
        $server = Server::create([
            'name' => 'Serveur Principal',
            'ip_address' => '127.0.0.1',
            'status' => 'online',
        ]);
        echo "✅ Serveur créé: ID {$server->id}\n";
    } else {
        echo "✅ Serveur existant: ID {$server->id}\n";
    }
    
    // Vérifier si le ticket 1 existe déjà
    $existingTicket = Ticket::find(1);
    if ($existingTicket) {
        echo "✅ Ticket ID 1 existe déjà: {$existingTicket->title}\n";
        
        // Mettre à jour le ticket pour s'assurer qu'il est visible
        $existingTicket->update([
            'user_id' => $user->id,
            'server_id' => $server->id,
        ]);
        echo "✅ Ticket mis à jour avec user_id={$user->id} et server_id={$server->id}\n";
    } else {
        echo "🎫 Création du ticket ID 1...\n";
        
        // Créer le ticket avec un ID spécifique
        $ticket = new Ticket();
        $ticket->id = 1;
        $ticket->user_id = $user->id;
        $ticket->server_id = $server->id;
        $ticket->title = 'Bienvenue dans le système de tickets';
        $ticket->description = 'Ceci est votre premier ticket de bienvenue.';
        $ticket->status = 'open';
        $ticket->priority = 'medium';
        $ticket->category = 'general';
        $ticket->save();
        
        echo "✅ Ticket créé avec succès: ID {$ticket->id}\n";
    }
    
    // Vérifier que le ticket est maintenant visible
    $visibleTicket = Ticket::where('id', 1)
        ->where('server_id', $server->id)
        ->first();
    
    if ($visibleTicket) {
        echo "🎉 SUCCÈS: Le ticket est maintenant visible !\n";
        echo "   ID: {$visibleTicket->id}\n";
        echo "   Titre: {$visibleTicket->title}\n";
        echo "   User ID: {$visibleTicket->user_id}\n";
        echo "   Server ID: {$visibleTicket->server_id}\n";
    } else {
        echo "❌ ERREUR: Le ticket n'est toujours pas visible\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "📍 Fichier: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
