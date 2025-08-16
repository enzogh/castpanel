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
     * @var int|null
     */
    private ?int $targetServerId = null;

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
     * Définit l'ID du serveur à surveiller
     */
    public function setTargetServerId(int $serverId): void
    {
        $this->targetServerId = $serverId;
        Log::info("LuaConsoleHook: Target server ID set to {$serverId}");
    }

    /**
     * Obtient l'ID du serveur ciblé
     */
    public function getTargetServerId(): ?int
    {
        return $this->targetServerId;
    }

    /**
     * Vérifie si un serveur spécifique est ciblé
     */
    public function hasTargetServer(): bool
    {
        return $this->targetServerId !== null;
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
                $basicCheck = $server->isInstalled() && !$server->isSuspended() && $this->isGarrysModServer($server);
                
                // Si un serveur spécifique est ciblé, vérifier l'ID
                if ($this->hasTargetServer()) {
                    return $basicCheck && $server->id === $this->targetServerId;
                }
                
                return $basicCheck;
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
     * Récupère le contenu des fichiers de logs depuis le daemon
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
            
            // Récupérer les logs depuis l'API du daemon
            $fullUrl = $daemonUrl . '/api/servers/' . $server->uuid . '/logs';
            
            $response = Http::timeout(30) // Timeout plus long pour les gros fichiers
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get($fullUrl);

            if ($response->successful()) {
                $data = $response->json();
                $logs = $data['data'] ?? '';
                
                // Si c'est un fichier de log, essayer de le lire directement
                if (empty($logs) && isset($data['file_path'])) {
                    $logs = $this->readLogFileFromDaemon($server, $data['file_path']);
                }
                
                return $logs;
            }

            return '';

        } catch (\Exception $e) {
            Log::error('LuaConsoleHook: Failed to get log files', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Lit un fichier de log directement depuis le daemon
     */
    private function readLogFileFromDaemon($server, string $filePath): string
    {
        try {
            $daemonUrl = $this->getDaemonUrl($server);
            $token = $this->getDaemonToken($server);
            
            if (empty($token)) {
                return '';
            }
            
            // Endpoint pour lire un fichier spécifique
            $fullUrl = $daemonUrl . '/api/servers/' . $server->uuid . '/files/contents';
            
            $response = Http::timeout(60) // Timeout long pour les gros fichiers
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->post($fullUrl, [
                    'file' => $filePath,
                    'start_line' => 0,
                    'end_line' => 10000 // Lire jusqu'à 10k lignes
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['contents'] ?? '';
            }

            return '';

        } catch (\Exception $e) {
            Log::error('LuaConsoleHook: Failed to read log file', [
                'server_id' => $server->id,
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
            return '';
        }
    }

    /**
     * Parse le contenu des fichiers de logs pour détecter les erreurs Lua avec stack traces
     */
    private function parseConsoleForLuaErrors(string $consoleOutput): array
    {
        $errors = [];
        $lines = explode("\n", $consoleOutput);
        $totalLines = count($lines);
        
        if ($this->debugMode) {
            echo "📄 Analyzing log file with {$totalLines} lines...\n";
        }
        
        for ($lineNumber = 0; $lineNumber < $totalLines; $lineNumber++) {
            $line = trim($lines[$lineNumber]);
            
            if (empty($line)) {
                continue;
            }

            // Détecter les erreurs Lua communes
            if ($this->isLuaError($line)) {
                // Capturer la stack trace complète après l'erreur
                $stackTrace = $this->captureStackTrace($lines, $lineNumber, $totalLines);
                $context = $this->captureErrorContext($lines, $lineNumber, $totalLines);
                
                // Essayer d'extraire un timestamp de la ligne
                $timestamp = $this->extractTimestampFromLogLine($line);
                
                $errors[] = [
                    'line' => $lineNumber + 1,
                    'content' => $line,
                    'type' => $this->classifyLuaError($line),
                    'timestamp' => $timestamp ?? now(),
                    'stack_trace' => $stackTrace,
                    'context' => $context,
                    'raw_line' => $lines[$lineNumber] // Ligne originale non trimmée
                ];
                
                if ($this->debugMode) {
                    echo "🚨 Error found at line {$lineNumber}: {$line}\n";
                }
            }
        }

        if ($this->debugMode) {
            echo "📊 Total errors found: " . count($errors) . "\n";
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
            '/runtime error/i',
            '/failed to load/i',
            '/could not load/i',
            '/error loading/i',
            '/addon.*not found/i',
            '/script.*failed/i',
            '/function.*error/i',
            '/nil value/i',
            '/invalid.*argument/i',
            '/out of memory/i',
            '/segmentation fault/i',
            '/access violation/i'
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
     * Capture la stack trace complète après une erreur
     */
    private function captureStackTrace(array $lines, int $errorLineIndex, int $totalLines): string
    {
        $stackTrace = [];
        $maxLines = 20; // Limiter à 20 lignes pour éviter d'être trop verbeux
        
        // Capturer les lignes après l'erreur (stack trace)
        for ($i = $errorLineIndex + 1; $i < min($errorLineIndex + $maxLines, $totalLines); $i++) {
            $line = trim($lines[$i]);
            
            if (empty($line)) {
                continue;
            }
            
            // Arrêter si on trouve une ligne qui indique la fin de la stack trace
            if (preg_match('/^[a-zA-Z]/', $line) && !preg_match('/^\s*at\s+/', $line)) {
                // Si c'est une nouvelle ligne qui ne ressemble pas à une stack trace
                if (!preg_match('/error|exception|stack|trace/i', $line)) {
                    break;
                }
            }
            
            $stackTrace[] = $line;
        }
        
        return implode("\n", $stackTrace);
    }

    /**
     * Capture le contexte autour de l'erreur
     */
    private function captureErrorContext(array $lines, int $errorLineIndex, int $totalLines): string
    {
        $context = [];
        $contextLines = 5; // 5 lignes avant et après l'erreur
        
        // Lignes avant l'erreur
        $start = max(0, $errorLineIndex - $contextLines);
        for ($i = $start; $i < $errorLineIndex; $i++) {
            $line = trim($lines[$i]);
            if (!empty($line)) {
                $context[] = "  {$line}";
            }
        }
        
        // Ligne d'erreur (marquée)
        $context[] = "→ " . trim($lines[$errorLineIndex]);
        
        // Lignes après l'erreur
        for ($i = $errorLineIndex + 1; $i < min($errorLineIndex + $contextLines + 1, $totalLines); $i++) {
            $line = trim($lines[$i]);
            if (!empty($line)) {
                $context[] = "  {$line}";
            }
        }
        
        return implode("\n", $context);
    }

    /**
     * Extrait le nom de l'addon depuis le message d'erreur
     */
    private function extractAddonFromError(string $errorMessage): ?string
    {
        // Patterns pour détecter les noms d'addons dans les erreurs
        $addonPatterns = [
            '/addon\s+[\'"]([^\'"]+)[\'"]/i',
            '/workshop\s+addon\s+[\'"]([^\'"]+)[\'"]/i',
            '/addon\s+([a-zA-Z0-9_-]+)/i',
            '/script\s+[\'"]([^\'"]+\.lua)[\'"]/i',
            '/file\s+[\'"]([^\'"]+\.lua)[\'"]/i'
        ];
        
        foreach ($addonPatterns as $pattern) {
            if (preg_match($pattern, $errorMessage, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }

    /**
     * Extrait un timestamp depuis une ligne de log
     */
    private function extractTimestampFromLogLine(string $line): ?string
    {
        // Patterns courants pour les timestamps dans les logs
        $timestampPatterns = [
            // Format: [2024-01-15 14:30:25]
            '/\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]/',
            // Format: 2024-01-15 14:30:25
            '/(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})/',
            // Format: [15/01/2024 14:30:25]
            '/\[(\d{2}\/\d{2}\/\d{4}\s+\d{2}:\d{2}:\d{2})\]/',
            // Format: [14:30:25]
            '/\[(\d{2}:\d{2}:\d{2})\]/',
            // Format: 14:30:25
            '/(\d{2}:\d{2}:\d{2})/'
        ];
        
        foreach ($timestampPatterns as $pattern) {
            if (preg_match($pattern, $line, $matches)) {
                $timestamp = $matches[1];
                
                // Convertir en format standard si nécessaire
                if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $timestamp)) {
                    // Ajouter la date d'aujourd'hui
                    $timestamp = date('Y-m-d') . ' ' . $timestamp;
                } elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}/', $timestamp)) {
                    // Convertir DD/MM/YYYY en YYYY-MM-DD
                    $timestamp = \DateTime::createFromFormat('d/m/Y H:i:s', $timestamp);
                    if ($timestamp) {
                        $timestamp = $timestamp->format('Y-m-d H:i:s');
                    }
                }
                
                return $timestamp;
            }
        }
        
        return null;
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
                    'addon' => $this->extractAddonFromError($error['content']),
                    'stack_trace' => $error['stack_trace'] ?? null,
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
        // En mode debug, accepter nos serveurs de test
        if ($this->debugMode && !($server instanceof Server)) {
            // C'est un serveur de test, vérifier son egg
            if (!$server->egg) {
                if ($this->debugMode) {
                    echo "    DEBUG: Test server has no egg\n";
                }
                return false;
            }

            $eggName = strtolower($server->egg->name ?? '');
            
            if ($this->debugMode) {
                echo "    DEBUG: Checking test server egg name: '{$eggName}'\n";
            }
            
            // Patterns plus stricts pour Garry's Mod uniquement
            $gmodPatterns = [
                'garry\'s mod',
                'gmod',
                'garrysmod',
                'garry\'s mod server',
                'gmod server'
            ];
            
            foreach ($gmodPatterns as $pattern) {
                if (Str::contains($eggName, $pattern)) {
                    if ($this->debugMode) {
                        echo "    DEBUG: Test server matched GMod pattern: '{$pattern}'\n";
                    }
                    return true;
                }
            }
            
            if ($this->debugMode) {
                echo "    DEBUG: Test server no GMod pattern matched\n";
            }
            return false;
        }

        // Vérifier que c'est bien un modèle Server en production
        if (!$server instanceof Server) {
            if ($this->debugMode) {
                echo "    DEBUG: Server is not Server instance\n";
            }
            return false;
        }

        if (!$server->egg) {
            if ($this->debugMode) {
                echo "    DEBUG: Server has no egg\n";
            }
            return false;
        }

        $eggName = strtolower($server->egg->name ?? '');
        
        if ($this->debugMode) {
            echo "    DEBUG: Checking egg name: '{$eggName}'\n";
        }
        
        // Patterns plus stricts pour Garry's Mod uniquement
        $gmodPatterns = [
            'garry\'s mod',
            'gmod',
            'garrysmod',
            'garry\'s mod server',
            'gmod server'
        ];
        
        foreach ($gmodPatterns as $pattern) {
            if (Str::contains($eggName, $pattern)) {
                if ($this->debugMode) {
                    echo "    DEBUG: Server matched GMod pattern: '{$pattern}'\n";
                }
                return true;
            }
        }
        
        if ($this->debugMode) {
            echo "    DEBUG: Server no GMod pattern matched\n";
        }
        return false;
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
        // Si un serveur spécifique est ciblé, créer seulement celui-ci
        if ($this->hasTargetServer()) {
            $targetId = $this->targetServerId;
            echo "🎯 Creating specific test server ID: {$targetId}\n";
            
            $server = $this->createMockServer($targetId, "Test GMod Server {$targetId}", 'Garry\'s Mod');
            echo "✅ Created target test server ID: {$targetId}\n";
            
            return collect([$server]);
        }
        
        echo "🎮 Creating Garry's Mod test servers for demonstration...\n";
        
        // Créer seulement des serveurs Garry's Mod pour les tests
        $testServers = collect([
            $this->createMockServer(1, 'Test GMod Server 1', 'Garry\'s Mod'),
            $this->createMockServer(2, 'Test GMod Server 2', 'GMod'),
            $this->createMockServer(3, 'Test GMod Server 3', 'GarrysMod')
        ]);

        echo "✅ Created " . count($testServers) . " Garry's Mod test servers\n";
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

        // Lignes spécifiques à Garry's Mod pour chaque serveur avec timestamps et stack traces
        $gmodLines1 = [
            "[2024-01-15 14:30:25] Garry's Mod server starting up...",
            "[2024-01-15 14:30:26] Loading Lua scripts and addons...",
            "[2024-01-15 14:30:26] [ERROR] Lua script failed to load: addon 'wiremod' not found",
            "    at addon_loader.lua:45",
            "    at workshop_manager.lua:123",
            "    at server_init.lua:67",
            "[2024-01-15 14:30:27] Addon 'sandbox' loaded successfully",
            "[2024-01-15 14:30:28] Gamemode 'sandbox' initialized",
            "[2024-01-15 14:30:29] Server ready for connections",
            "[2024-01-15 14:30:30] [ERROR] Attempt to call nil value in function 'player_initial_spawn'",
            "    at player_manager.lua:89",
            "    at spawn_system.lua:156",
            "    at gamemode.lua:34",
            "[2024-01-15 14:30:31] Player connected: TestPlayer",
            "[2024-01-15 14:30:32] [ERROR] Bad argument #1 to 'print' (string expected, got nil)",
            "    at debug.lua:23",
            "    at player_utils.lua:67",
            "[2024-01-15 14:30:33] Map loaded: gm_construct"
        ];

        $gmodLines2 = [
            "[2024-01-15 14:30:25] GMod server initializing...",
            "[2024-01-15 14:30:26] Loading workshop content...",
            "[2024-01-15 14:30:27] Server configuration loaded",
            "[2024-01-15 14:30:27] [ERROR] Failed to load workshop addon '123456789'",
            "    at workshop_loader.lua:78",
            "    at content_manager.lua:145",
            "    at startup.lua:56",
            "[2024-01-15 14:30:28] Workshop addon loaded successfully",
            "[2024-01-15 14:30:29] Lua environment initialized",
            "[2024-01-15 14:30:30] [ERROR] Memory allocation failed for texture loading",
            "    at texture_manager.lua:92",
            "    at resource_loader.lua:178",
            "    at graphics.lua:45",
            "[2024-01-15 14:30:31] Server ready for players",
            "[2024-01-15 14:30:32] Player joined: Builder",
            "[2024-01-15 14:30:33] Gamemode started"
        ];

        $gmodLines3 = [
            "[2024-01-15 14:30:25] GarrysMod server starting...",
            "[2024-01-15 14:30:26] Loading custom Lua scripts...",
            "[2024-01-15 14:30:27] Server settings applied",
            "[2024-01-15 14:30:27] [ERROR] Lua script 'custom_script.lua' syntax error",
            "    at lua_parser.lua:34",
            "    at script_loader.lua:89",
            "    at custom_loader.lua:123",
            "[2024-01-15 14:30:28] Script loaded successfully",
            "[2024-01-15 14:30:29] Physics engine initialized",
            "[2024-01-15 14:30:30] [ERROR] Failed to load custom model",
            "    at model_loader.lua:67",
            "    at asset_manager.lua:134",
            "    at resource_system.lua:78",
            "[2024-01-15 14:30:31] Server ready",
            "[2024-01-15 14:30:32] Player connected: Developer",
            "[2024-01-15 14:30:33] Custom gamemode loaded"
        ];

        // Choisir les lignes selon l'ID du serveur
        $lines = match($server->id) {
            1 => $gmodLines1,
            2 => $gmodLines2,
            3 => $gmodLines3,
            default => $gmodLines1
        };
        
        // Retourner seulement le nombre de lignes demandées
        $linesToShow = array_slice($lines, 0, $lineCounters[$serverId]);
        
        return implode("\n", $linesToShow) . "\n";
    }
}
