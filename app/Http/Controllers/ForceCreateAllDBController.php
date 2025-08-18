<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ForceCreateAllDBController extends Controller
{
    public function create()
    {
        try {
            Log::info('ForceCreateAllDBController::create - Début (DB::table() uniquement)');
            
            // Vérifier la base de données active
            $connection = config('database.default');
            $database = config('database.connections.' . $connection . '.database');
            
            Log::info('Base de données active', [
                'connection' => $connection,
                'database' => $database,
            ]);
            
            // Tester la connexion
            $pdo = DB::connection()->getPdo();
            Log::info('Connexion PDO réussie', [
                'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
                'server_version' => $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION),
            ]);
            
            // Vérifier les tables
            $tables = [];
            if ($connection === 'sqlite') {
                $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
            } else {
                $tables = DB::select('SHOW TABLES');
            }
            
            Log::info('Tables trouvées', ['tables' => $tables]);
            
            // Créer la table users si elle n'existe pas
            if (!in_array('users', array_column($tables, 'name'))) {
                Log::info('Création de la table users');
                if ($connection === 'sqlite') {
                    DB::statement('
                        CREATE TABLE users (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            name VARCHAR(255) NOT NULL,
                            email VARCHAR(255) UNIQUE NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ');
                } else {
                    DB::statement('
                        CREATE TABLE users (
                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            email VARCHAR(255) UNIQUE NOT NULL,
                            password VARCHAR(255) NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )
                    ');
                }
            }
            
            // Créer la table servers si elle n'existe pas
            if (!in_array('servers', array_column($tables, 'name'))) {
                Log::info('Création de la table servers');
                if ($connection === 'sqlite') {
                    DB::statement('
                        CREATE TABLE servers (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            name VARCHAR(255) NOT NULL,
                            ip_address VARCHAR(45),
                            status VARCHAR(50) DEFAULT "online",
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                        )
                    ');
                } else {
                    DB::statement('
                        CREATE TABLE servers (
                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            name VARCHAR(255) NOT NULL,
                            ip_address VARCHAR(45),
                            status VARCHAR(50) DEFAULT "online",
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                        )
                    ');
                }
            }
            
            // Créer la table tickets si elle n'existe pas
            if (!in_array('tickets', array_column($tables, 'name'))) {
                Log::info('Création de la table tickets');
                if ($connection === 'sqlite') {
                    DB::statement('
                        CREATE TABLE tickets (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            user_id INTEGER NOT NULL,
                            server_id INTEGER NOT NULL,
                            title VARCHAR(255) NOT NULL,
                            description TEXT,
                            status VARCHAR(50) DEFAULT "open",
                            priority VARCHAR(50) DEFAULT "medium",
                            category VARCHAR(50) DEFAULT "general",
                            assigned_to INTEGER,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id),
                            FOREIGN KEY (server_id) REFERENCES servers(id)
                        )
                    ');
                } else {
                    DB::statement('
                        CREATE TABLE tickets (
                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            user_id BIGINT UNSIGNED NOT NULL,
                            server_id BIGINT UNSIGNED NOT NULL,
                            title VARCHAR(255) NOT NULL,
                            description TEXT,
                            status VARCHAR(50) DEFAULT "open",
                            priority VARCHAR(50) DEFAULT "medium",
                            category VARCHAR(50) DEFAULT "general",
                            assigned_to BIGINT UNSIGNED,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (user_id) REFERENCES users(id),
                            FOREIGN KEY (server_id) REFERENCES servers(id)
                        )
                    ');
                }
            }
            
            // Créer la table ticket_messages si elle n'existe pas
            if (!in_array('ticket_messages', array_column($tables, 'name'))) {
                Log::info('Création de la table ticket_messages');
                if ($connection === 'sqlite') {
                    DB::statement('
                        CREATE TABLE ticket_messages (
                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                            ticket_id INTEGER NOT NULL,
                            user_id INTEGER NOT NULL,
                            message TEXT NOT NULL,
                            is_internal BOOLEAN DEFAULT 0,
                            attachments TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            FOREIGN KEY (ticket_id) REFERENCES tickets(id),
                            FOREIGN KEY (user_id) REFERENCES users(id)
                        )
                    ');
                } else {
                    DB::statement('
                        CREATE TABLE ticket_messages (
                            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                            ticket_id BIGINT UNSIGNED NOT NULL,
                            user_id BIGINT UNSIGNED NOT NULL,
                            message TEXT NOT NULL,
                            is_internal BOOLEAN DEFAULT 0,
                            attachments TEXT,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (ticket_id) REFERENCES tickets(id),
                            FOREIGN KEY (user_id) REFERENCES users(id)
                        )
                    ');
                }
            }
            
            // Créer un utilisateur de test
            $userId = DB::table('users')->insertGetId([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('Utilisateur créé', ['id' => $userId]);
            
            // Créer un serveur de test
            $serverId = DB::table('servers')->insertGetId([
                'name' => 'Serveur Principal',
                'ip_address' => '127.0.0.1',
                'status' => 'online',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('Serveur créé', ['id' => $serverId]);
            
            // Créer le ticket ID 1
            $ticketId = DB::table('tickets')->insertGetId([
                'id' => 1,
                'user_id' => $userId,
                'server_id' => $serverId,
                'title' => 'Ticket de bienvenue - ' . now()->format('Y-m-d H:i:s'),
                'description' => 'Ceci est un ticket créé automatiquement pour résoudre l\'erreur 404.',
                'status' => 'open',
                'priority' => 'medium',
                'category' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('Ticket créé', ['id' => $ticketId]);
            
            // Créer un message de test
            $messageId = DB::table('ticket_messages')->insertGetId([
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'message' => 'Ceci est le premier message du ticket de bienvenue.',
                'is_internal' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            Log::info('Message créé', ['id' => $messageId]);
            
            // Vérifier que tout est créé
            $userCount = DB::table('users')->count();
            $serverCount = DB::table('servers')->count();
            $ticketCount = DB::table('tickets')->count();
            $messageCount = DB::table('ticket_messages')->count();
            
            Log::info('Résumé de création', [
                'users' => $userCount,
                'servers' => $serverCount,
                'tickets' => $ticketCount,
                'messages' => $messageCount,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Tout créé avec succès (DB::table() uniquement)',
                'data' => [
                    'user_id' => $userId,
                    'server_id' => $serverId,
                    'ticket_id' => $ticketId,
                    'message_id' => $messageId,
                    'counts' => [
                        'users' => $userCount,
                        'servers' => $serverCount,
                        'tickets' => $ticketCount,
                        'messages' => $messageCount,
                    ],
                ],
                'database_info' => [
                    'connection' => $connection,
                    'database' => $database,
                    'driver' => $pdo->getAttribute(\PDO::ATTR_DRIVER_NAME),
                ],
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur ForceCreateAllDBController', [
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
