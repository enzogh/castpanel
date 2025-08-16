<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Models\LuaError;
use App\Notifications\LuaErrorDetected;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LuaConsoleHookService
{
    /**
     * @var bool
     */
    private bool $isRunning = false;

    /**
     * @var array
     */
    private array $monitoredServers = [];

    /**
     * @var int
     */
    private int $checkInterval = 1; // secondes - plus rapide pour le streaming

    /**
     * @var int
     */
    private int $maxRetries = 3;

    /**
     * @var array
     */
    private array $consoleHistory = [];

    /**
     * @var array
     */
    private array $lastLineCounts = [];

    /**
     * @var bool
     */
    private bool $streamingMode = false;

    /**
     * @var bool
     */
    private bool $debugMode = false;

    /**
     * Démarre le service de surveillance en temps réel
     */
    public function startHooking(): void
    {
        if ($this->isRunning) {
            Log::warning('LuaConsoleHook: Service is already running');
            return;
        }

        $this->isRunning = true;
        Log::info('LuaConsoleHook: Service started');

        try {
            $this->loadServers();
            $this->startMonitoringLoop();
        } catch (\Exception $e) {
            Log::error('LuaConsoleHook: Failed to start monitoring', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->isRunning = false;
            throw $e;
        }
    }

    /**
     * Arrête le service de surveillance
     */
    public function stopHooking(): void
    {
        $this->isRunning = false;
        Log::info('LuaConsoleHook: Service stopped');
    }

    /**
     * Vérifie si le service est en cours d'exécution
     */
    public function isRunning(): bool
    {
        return $this->isRunning;
    }

    /**
     * Active ou désactive le mode debug
     */
    public function setDebugMode(bool $enabled): void
    {
        $this->debugMode = $enabled;
        Log::info('LuaConsoleHook: Debug mode ' . ($enabled ? 'enabled' : 'disabled'));
    }

    /**
     * Active le mode test avec des serveurs simulés
     */
    public function enableTestMode(): void
    {
        $this->debugMode = true;
        Log::info('LuaConsoleHook: Test mode enabled with simulated servers');
    }

    /**
     * Active le mode streaming live
     */
    public function enableStreamingMode(): void
    {
        $this->streamingMode = true;
        $this->checkInterval = 0; // Pas de délai pour le streaming
        Log::info('LuaConsoleHook: Streaming mode enabled');
    }

    /**
     * Vérifie si le mode streaming est activé
     */
    public function isStreamingMode(): bool
    {
        return $this->streamingMode;
    }

    /**
     * Vérifie si le mode debug est activé
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * Charge la liste des serveurs à surveiller
     */
    private function loadServers(): void
    {
        try {
            if ($this->debugMode) {
                echo "🔍 Loading servers...\n";
            }

            // En mode debug, utiliser directement les serveurs de test
            if ($this->debugMode) {
                echo "🎮 Using test servers for demonstration\n";
                $servers = $this->createTestServers();
            } else {
                // Charger tous les serveurs et filtrer avec les méthodes du modèle
                $servers = Server::with(['egg', 'node'])->get();
            }

            if ($this->debugMode) {
                echo "📊 All servers in database:\n";
                if (count($servers) === 0) {
                    echo "  ❌ No servers found in database!\n";
                } else {
                    foreach ($servers as $server) {
                        $eggName = $server->egg ? $server->egg->name : 'No egg';
                        $nodeName = $server->node ? $server->node->name : 'No node';
                        $isInstalled = $server->isInstalled() ? '✅' : '❌';
                        $isSuspended = $server->isSuspended() ? '🚫' : '✅';
                        $isGmod = $this->isGarrysModServer($server) ? '🎮' : '❌';
                        
                        echo "  - {$server->name} (ID: {$server->id})\n";
                        echo "    Egg: {$eggName} | Node: {$nodeName}\n";
                        echo "    Installed: {$isInstalled} | Suspended: {$isSuspended} | GMod: {$isGmod}\n";
                    }
                }
                echo "  Total servers in DB: " . count($servers) . "\n\n";
            }

            $this->monitoredServers = $servers->filter(function ($server) {
                // Vérifier que le serveur est installé et non suspendu
                return $server->isInstalled() && !$server->isSuspended() && $this->isGarrysModServer($server);
            })->values()->all();

            if ($this->debugMode) {
                echo "🎯 Servers that passed all filters:\n";
                if (count($this->monitoredServers) === 0) {
                    echo "  ❌ No servers passed the filters!\n";
                    echo "  This could be because:\n";
                    echo "    - No servers are installed\n";
                    echo "    - All servers are suspended\n";
                    echo "    - No servers match Garry's Mod criteria\n";
                } else {
                    foreach ($this->monitoredServers as $server) {
                        $eggName = $server->egg ? $server->egg->name : 'No egg';
                        echo "  - {$server->name} (ID: {$server->id}) - Egg: {$eggName}\n";
                    }
                }
                echo "  Total monitored: " . count($this->monitoredServers) . " servers\n\n";
            }

            Log::info('LuaConsoleHook: Loaded servers for monitoring', [
                'total_servers' => count($this->monitoredServers)
            ]);

        } catch (\Exception $e) {
            if ($this->debugMode) {
                echo "❌ ERROR loading servers: " . $e->getMessage() . "\n";
                echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
            }
            Log::error('LuaConsoleHook: Failed to load servers', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Démarre la boucle de surveillance principale
     */
    private function startMonitoringLoop(): void
    {
        Log::info('LuaConsoleHook: Starting monitoring loop');

        while ($this->isRunning) {
            try {
                if ($this->streamingMode) {
                    // Mode streaming : pas de délai, affichage continu
                    $this->checkAllServers();
                    usleep(100000); // 0.1 seconde pour éviter de surcharger le CPU
                } else {
                    // Mode normal avec délai
                    if ($this->debugMode) {
                        echo "🔍 Checking all servers... (Interval: {$this->checkInterval}s)\n";
                    }
                    
                    $this->checkAllServers();
                    
                    if ($this->debugMode) {
                        echo "⏳ Waiting {$this->checkInterval} seconds before next check...\n";
                    }
                    
                    sleep($this->checkInterval);
                }
            } catch (\Exception $e) {
                Log::error('LuaConsoleHook: Error in monitoring loop', [
                    'error' => $e->getMessage()
                ]);
                
                // Continuer malgré l'erreur
                if (!$this->streamingMode) {
                    sleep($this->checkInterval);
                }
            }
        }
    }

    /**
     * Vérifie tous les serveurs surveillés
     */
    private function checkAllServers(): void
    {
        foreach ($this->monitoredServers as $server) {
            try {
                $this->checkServerConsole($server);
            } catch (\Exception $e) {
                Log::error('LuaConsoleHook: Failed to check server', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Vérifie la console d'un serveur spécifique
     */
    private function checkServerConsole($server): void
    {
        try {
            $consoleOutput = $this->getConsoleOutput($server);
            
            if (empty($consoleOutput)) {
                return;
            }

            $serverId = $server->id;
            $lines = explode("\n", $consoleOutput);
            $currentLineCount = count($lines);
            
            // Initialiser l'historique si c'est la première fois
            if (!isset($this->lastLineCounts[$serverId])) {
                $this->lastLineCounts[$serverId] = 0;
                $this->consoleHistory[$serverId] = [];
            }

            // En mode streaming, afficher seulement les nouvelles lignes
            if ($this->streamingMode || $this->debugMode) {
                $newLines = array_slice($lines, $this->lastLineCounts[$serverId]);
                
                foreach ($newLines as $lineNumber => $line) {
                    $line = trim($line);
                    if (!empty($line)) {
                        $absoluteLineNumber = $this->lastLineCounts[$serverId] + $lineNumber + 1;
                        $isError = $this->isLuaError($line);
                        $status = $isError ? '🚨 ERROR' : '📝 INFO';
                        $timestamp = date('H:i:s');
                        
                        // Affichage en streaming live
                        if ($this->streamingMode) {
                            echo "[{$timestamp}] [{$status}] {$server->name}: {$line}\n";
                        } else {
                            echo "[{$status}] Server {$server->name} (ID: {$serverId}) - Line {$absoluteLineNumber}: {$line}\n";
                        }
                        
                        // Stocker dans l'historique
                        $this->consoleHistory[$serverId][] = [
                            'timestamp' => $timestamp,
                            'line' => $line,
                            'is_error' => $isError,
                            'line_number' => $absoluteLineNumber
                        ];
                    }
                }
                
                // Mettre à jour le compteur de lignes
                $this->lastLineCounts[$serverId] = $currentLineCount;
            }

            // Parser pour détecter les erreurs Lua
            $errors = $this->parseConsoleForLuaErrors($consoleOutput);
            
            if (!empty($errors)) {
                $this->processNewErrors($server, $errors);
            }

        } catch (\Exception $e) {
            Log::error('LuaConsoleHook: Failed to check server console', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Récupère la sortie de la console depuis le daemon
     */
    private function getConsoleOutput($server): string
    {
        // En mode debug, retourner des données de test
        if ($this->debugMode) {
            return $this->getTestConsoleOutput($server);
        }

        try {
            $daemonUrl = $this->getDaemonUrl($server);
            $token = $this->getDaemonToken($server);
            
            if (empty($token)) {
                return '';
            }
            
            $fullUrl = $daemonUrl . '/api/servers/' . $server->uuid . '/logs';
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get($fullUrl);

            if ($response->successful()) {
                $data = $response->json();
                return $data['data'] ?? '';
            }

            return '';

        } catch (\Exception $e) {
            Log::error('LuaConsoleHook: Failed to get console output', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Parse la sortie de la console pour détecter les erreurs Lua
     */
    private function parseConsoleForLuaErrors(string $consoleOutput): array
    {
        $errors = [];
        $lines = explode("\n", $consoleOutput);
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }

            // Détecter les erreurs Lua communes
            if ($this->isLuaError($line)) {
                $errors[] = [
                    'line' => $lineNumber + 1,
                    'content' => $line,
                    'type' => $this->classifyLuaError($line),
                    'timestamp' => now()
                ];
            }
        }

        return $errors;
    }

    /**
     * Détermine si une ligne contient une erreur Lua
     */
    private function isLuaError(string $line): bool
    {
        $errorPatterns = [
            '/\[ERROR\]/i',
            '/lua error/i',
            '/attempt to call/i',
            '/attempt to index/i',
            '/bad argument/i',
            '/stack overflow/i',
            '/memory error/i',
            '/syntax error/i',
            '/runtime error/i'
        ];

        foreach ($errorPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Classifie le type d'erreur Lua
     */
    private function classifyLuaError(string $line): string
    {
        if (preg_match('/\[ERROR\]/i', $line)) {
            return 'console_error';
        }
        
        if (preg_match('/attempt to call/i', $line)) {
            return 'function_call_error';
        }
        
        if (preg_match('/attempt to index/i', $line)) {
            return 'index_error';
        }
        
        if (preg_match('/bad argument/i', $line)) {
            return 'argument_error';
        }
        
        if (preg_match('/stack overflow/i', $line)) {
            return 'stack_overflow';
        }
        
        if (preg_match('/memory error/i', $line)) {
            return 'memory_error';
        }
        
        if (preg_match('/syntax error/i', $line)) {
            return 'syntax_error';
        }
        
        if (preg_match('/runtime error/i', $line)) {
            return 'runtime_error';
        }

        return 'unknown_error';
    }

    /**
     * Traite les nouvelles erreurs détectées
     */
    private function processNewErrors($server, array $errors): void
    {
        foreach ($errors as $error) {
            try {
                if ($this->debugMode) {
                    // En mode debug, simuler la détection d'erreur sans base de données
                    echo "🚨 NEW ERROR DETECTED! Server: {$server->name} - Type: {$error['type']} - Line: {$error['line']}\n";
                    echo "   Content: {$error['content']}\n";
                    continue;
                }

                // Vérifier si l'erreur n'a pas déjà été enregistrée
                $existingError = LuaError::where('server_id', $server->id)
                    ->where('content', $error['content'])
                    ->where('created_at', '>', now()->subMinutes(5))
                    ->first();

                if ($existingError) {
                    continue; // Erreur déjà enregistrée récemment
                }

                // Créer une nouvelle entrée d'erreur
                $errorKey = md5($error['content'] . $server->id);
                
                $luaError = LuaError::create([
                    'server_id' => $server->id,
                    'error_key' => $errorKey,
                    'level' => 'ERROR',
                    'message' => $error['content'],
                    'addon' => null, // À déterminer si possible
                    'stack_trace' => null,
                    'count' => 1,
                    'first_seen' => $error['timestamp'],
                    'last_seen' => $error['timestamp'],
                    'status' => 'open',
                    'resolved' => false
                ]);

                // Envoyer une notification
                $this->sendErrorNotification($server, $luaError);

                Log::info('LuaConsoleHook: New Lua error detected', [
                    'server_id' => $server->id,
                    'error_id' => $luaError->id,
                    'error_type' => $error['type'],
                    'line' => $error['line']
                ]);

            } catch (\Exception $e) {
                Log::error('LuaConsoleHook: Failed to process error', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Envoie une notification pour une nouvelle erreur
     */
    private function sendErrorNotification($server, $luaError): void
    {
        // En mode debug, simuler l'envoi de notification
        if ($this->debugMode) {
            echo "📧 Notification sent for error on server: {$server->name}\n";
            return;
        }

        try {
            // Notifier les administrateurs du serveur
            if ($server->owner) {
                $server->owner->notify(new LuaErrorDetected($server, $luaError));
            }

            // Notifier les sous-utilisateurs avec les permissions appropriées
            foreach ($server->subusers as $subuser) {
                if ($subuser->can('view_console', $server)) {
                    $subuser->user->notify(new LuaErrorDetected($server, $luaError));
                }
            }

        } catch (\Exception $e) {
            Log::error('LuaConsoleHook: Failed to send notification', [
                'server_id' => $server->id,
                'error_id' => $luaError->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Vérifie si un serveur est un serveur Garry's Mod
     */
    private function isGarrysModServer($server): bool
    {
        // En mode debug, accepter tous les serveurs pour les tests
        if ($this->debugMode) {
            return true;
        }

        // Vérifier que c'est bien un modèle Server
        if (!$server instanceof Server) {
            return false;
        }

        if (!$server->egg) {
            return false;
        }

        $eggName = strtolower($server->egg->name ?? '');
        
        return Str::contains($eggName, [
            'garry\'s mod',
            'gmod',
            'garrysmod',
            'source engine'
        ]);
    }

    /**
     * Récupère l'URL du daemon pour un serveur
     */
    private function getDaemonUrl($server): string
    {
        if (!$server->node) {
            throw new \Exception('Server has no associated node');
        }

        $scheme = $server->node->scheme ?? 'http';
        $host = $server->node->fqdn ?? $server->node->ip;
        $port = $server->node->daemon_port ?? 8080;

        return "{$scheme}://{$host}:{$port}";
    }

    /**
     * Récupère le token d'authentification du daemon
     */
    private function getDaemonToken($server): string
    {
        if (!$server->node) {
            return '';
        }

        return $server->node->daemon_token ?? '';
    }

    /**
     * Met à jour la liste des serveurs surveillés
     */
    public function refreshServers(): void
    {
        $this->loadServers();
        Log::info('LuaConsoleHook: Server list refreshed');
    }

    /**
     * Définit l'intervalle de vérification
     */
    public function setCheckInterval(int $seconds): void
    {
        $this->checkInterval = max(1, $seconds);
        Log::info('LuaConsoleHook: Check interval updated', [
            'new_interval' => $this->checkInterval
        ]);
    }

    /**
     * Obtient les statistiques du service
     */
    public function getStats(): array
    {
        $totalLines = 0;
        $totalErrors = 0;
        
        foreach ($this->consoleHistory as $serverHistory) {
            $totalLines += count($serverHistory);
            foreach ($serverHistory as $entry) {
                if ($entry['is_error']) {
                    $totalErrors++;
                }
            }
        }

        return [
            'is_running' => $this->isRunning,
            'streaming_mode' => $this->streamingMode,
            'monitored_servers' => count($this->monitoredServers),
            'check_interval' => $this->checkInterval,
            'total_lines_streamed' => $totalLines,
            'total_errors_detected' => $totalErrors,
            'console_history' => $this->consoleHistory
        ];
    }

    /**
     * Obtient l'historique de la console pour un serveur
     */
    public function getConsoleHistory(int $serverId): array
    {
        return $this->consoleHistory[$serverId] ?? [];
    }

    /**
     * Efface l'historique de la console
     */
    public function clearConsoleHistory(): void
    {
        $this->consoleHistory = [];
        $this->lastLineCounts = [];
        if ($this->debugMode) {
            echo "🗑️  Console history cleared\n";
        }
    }

    /**
     * Crée des serveurs de test pour le mode debug
     */
    private function createTestServers(): \Illuminate\Support\Collection
    {
        echo "🎮 Creating test servers for demonstration...\n";
        
        // Créer des objets simulés pour les serveurs de test
        $testServers = collect([
            $this->createMockServer(1, 'Test GMod Server 1', 'Garry\'s Mod'),
            $this->createMockServer(2, 'Test GMod Server 2', 'Source Engine')
        ]);

        echo "✅ Created " . count($testServers) . " test servers\n";
        return $testServers;
    }

    /**
     * Crée un serveur de test simulé
     */
    private function createMockServer(int $id, string $name, string $eggName): object
    {
        $server = new class($id, $name, $eggName) {
            public $id;
            public $name;
            public $uuid;
            public $egg;
            public $node;

            public function __construct($id, $name, $eggName) {
                $this->id = $id;
                $this->name = $name;
                $this->uuid = "test-uuid-{$id}";
                $this->egg = (object) ['name' => $eggName];
                $this->node = (object) [
                    'name' => "Test Node {$id}",
                    'ip' => '127.0.0.1',
                    'daemon_port' => 8080 + $id - 1,
                    'daemon_token' => "test-token-{$id}"
                ];
            }

            public function isInstalled(): bool
            {
                return true;
            }

            public function isSuspended(): bool
            {
                return false;
            }
        };

        return $server;
    }

    /**
     * Retourne des données de console de test pour le mode debug
     */
    private function getTestConsoleOutput($server): string
    {
        static $lineCounters = [];
        static $lastUpdate = [];
        
        $serverId = $server->id;
        
        // Initialiser les compteurs
        if (!isset($lineCounters[$serverId])) {
            $lineCounters[$serverId] = 0;
            $lastUpdate[$serverId] = time();
        }

        // En mode streaming, générer de nouvelles lignes progressivement
        if ($this->streamingMode) {
            $currentTime = time();
            $timeDiff = $currentTime - $lastUpdate[$serverId];
            
            // Générer 1-3 nouvelles lignes toutes les secondes
            if ($timeDiff >= 1) {
                $newLines = rand(1, 3);
                $lineCounters[$serverId] += $newLines;
                $lastUpdate[$serverId] = $currentTime;
            }
        } else {
            // Mode normal : retourner toutes les lignes
            $lineCounters[$serverId] = 10;
        }

        // Lignes de base pour chaque serveur
        $baseLines = [
            "Server starting up...",
            "Loading addons...",
            "[ERROR] Lua script failed to load: addon 'test_addon' not found",
            "Addon loaded successfully",
            "Server ready for connections",
            "[ERROR] Attempt to call nil value in function 'player_connect'",
            "Player connected: TestPlayer",
            "[ERROR] Bad argument #1 to 'print' (string expected, got nil)",
            "Map loaded: gm_construct",
            "Gamemode initialized"
        ];

        $baseLines2 = [
            "Initializing Source Engine server...",
            "Loading game files...",
            "Server configuration loaded",
            "[ERROR] Failed to load map 'de_dust2'",
            "Map loaded successfully",
            "Bot AI initialized",
            "[ERROR] Memory allocation failed for texture loading",
            "Server ready",
            "Player joined: CounterTerrorist",
            "Round started"
        ];

        // Choisir les lignes selon le serveur
        $lines = ($server->id == 1) ? $baseLines : $baseLines2;
        
        // Retourner seulement le nombre de lignes demandées
        $linesToShow = array_slice($lines, 0, $lineCounters[$serverId]);
        
        return implode("\n", $linesToShow) . "\n";
    }
}
