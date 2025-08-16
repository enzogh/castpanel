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
    private int $checkInterval = 5; // secondes

    /**
     * @var int
     */
    private int $maxRetries = 3;

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
     * Charge la liste des serveurs à surveiller
     */
    private function loadServers(): void
    {
        try {
            $servers = Server::with(['egg', 'node'])
                ->where('installed', true)
                ->where('suspended', false)
                ->get();

            $this->monitoredServers = $servers->filter(function ($server) {
                return $this->isGarrysModServer($server);
            })->values()->all();

            Log::info('LuaConsoleHook: Loaded servers for monitoring', [
                'total_servers' => count($this->monitoredServers)
            ]);

        } catch (\Exception $e) {
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
                $this->checkAllServers();
                sleep($this->checkInterval);
            } catch (\Exception $e) {
                Log::error('LuaConsoleHook: Error in monitoring loop', [
                    'error' => $e->getMessage()
                ]);
                
                // Continuer malgré l'erreur
                sleep($this->checkInterval);
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
    private function checkServerConsole(Server $server): void
    {
        try {
            $consoleOutput = $this->getConsoleOutput($server);
            
            if (empty($consoleOutput)) {
                return;
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
    private function getConsoleOutput(Server $server): string
    {
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
    private function processNewErrors(Server $server, array $errors): void
    {
        foreach ($errors as $error) {
            try {
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
    private function sendErrorNotification(Server $server, LuaError $luaError): void
    {
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
    private function isGarrysModServer(Server $server): bool
    {
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
    private function getDaemonUrl(Server $server): string
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
    private function getDaemonToken(Server $server): string
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
        return [
            'is_running' => $this->isRunning,
            'monitored_servers' => count($this->monitoredServers),
            'check_interval' => $this->checkInterval,
            'last_check' => Cache::get('lua_hook_last_check'),
            'errors_detected_today' => LuaError::where('created_at', '>=', today())->count()
        ];
    }
}
