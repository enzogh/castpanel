<?php

namespace App\Services\Servers;

use App\Models\LuaError;
use App\Models\Server;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LuaConsoleMonitorService
{
    /**
     * Surveille la console et capture les erreurs Lua
     */
    public function monitorConsole(Server $server): array
    {
        if (!$this->isGarrysModServer($server)) {
            Log::info('LuaConsoleMonitor: Server is not Garry\'s Mod', [
                'server_id' => $server->id,
                'egg_name' => $server->egg->name ?? 'no egg'
            ]);
            return [];
        }

        try {
            // Récupérer les logs de la console via l'API du daemon
            $consoleOutput = $this->getConsoleOutput($server);
            
            if (empty($consoleOutput)) {
                Log::info('LuaConsoleMonitor: No console output available', [
                    'server_id' => $server->id
                ]);
                return [];
            }

            // Parser la sortie pour détecter les erreurs Lua
            $errors = $this->parseConsoleForLuaErrors($consoleOutput);
            
            // Enregistrer les nouvelles erreurs en base
            $newErrors = $this->saveNewErrors($server, $errors);
            
            // Sécuriser le comptage des lignes
            $lines = explode("\n", $consoleOutput);
            $lineCount = is_array($lines) ? count($lines) : 0;
            
            Log::info('LuaConsoleMonitor: Console monitoring completed', [
                'server_id' => $server->id,
                'console_lines' => $lineCount,
                'errors_found' => count($errors),
                'new_errors_saved' => count($newErrors)
            ]);

            return $newErrors;

        } catch (\Exception $e) {
            Log::error('LuaConsoleMonitor: Failed to monitor console', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [];
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
            
            Log::info('LuaConsoleMonitor: Attempting to get console output', [
                'server_id' => $server->id,
                'server_uuid' => $server->uuid,
                'daemon_url' => $daemonUrl,
                'has_token' => !empty($token)
            ]);
            
            if (empty($token)) {
                Log::warning('LuaConsoleMonitor: No daemon token available', [
                    'server_id' => $server->id
                ]);
                return '';
            }
            
            $fullUrl = $daemonUrl . '/api/servers/' . $server->uuid . '/logs';
            Log::info('LuaConsoleMonitor: Making request to daemon', [
                'server_id' => $server->id,
                'full_url' => $fullUrl
            ]);
            
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ])
                ->get($fullUrl);

            Log::info('LuaConsoleMonitor: Daemon response received', [
                'server_id' => $server->id,
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body_length' => strlen($response->body())
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $consoleData = $data['data'] ?? '';
                
                Log::info('LuaConsoleMonitor: Console data extracted', [
                    'server_id' => $server->id,
                    'data_type' => gettype($data),
                    'console_data_type' => gettype($consoleData),
                    'console_data_length' => is_string($consoleData) ? strlen($consoleData) : (is_array($consoleData) ? count($consoleData) : 'N/A'),
                    'data_keys' => is_array($data) ? array_keys($data) : 'N/A'
                ]);
                
                // S'assurer que c'est une chaîne
                if (is_array($consoleData)) {
                    $consoleData = implode("\n", $consoleData);
                }
                
                $finalOutput = is_string($consoleData) ? $consoleData : '';
                
                Log::info('LuaConsoleMonitor: Final console output', [
                    'server_id' => $server->id,
                    'final_output_length' => strlen($finalOutput),
                    'final_output_preview' => substr($finalOutput, 0, 200)
                ]);
                
                return $finalOutput;
            }

            Log::warning('LuaConsoleMonitor: Daemon response not successful', [
                'server_id' => $server->id,
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 200),
                'full_url' => $fullUrl,
                'headers' => $response->headers()
            ]);

            return '';

        } catch (\Exception $e) {
            Log::warning('LuaConsoleMonitor: Failed to get console output', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);

            return '';
        }
    }

    /**
     * Parse la sortie console pour détecter les erreurs Lua
     */
    private function parseConsoleForLuaErrors(string $consoleOutput): array
    {
        if (empty($consoleOutput) || !is_string($consoleOutput)) {
            Log::warning('LuaConsoleMonitor: Invalid console output', [
                'console_output_type' => gettype($consoleOutput),
                'console_output_length' => is_string($consoleOutput) ? strlen($consoleOutput) : 'N/A'
            ]);
            return [];
        }
        
        $errors = [];
        $lines = explode("\n", $consoleOutput);
        
        // S'assurer que explode() a retourné un tableau
        if (!is_array($lines)) {
            Log::warning('LuaConsoleMonitor: explode() failed to return array', [
                'console_output_type' => gettype($consoleOutput),
                'console_output_length' => strlen($consoleOutput),
                'explode_result_type' => gettype($lines)
            ]);
            return [];
        }
        
        foreach ($lines as $lineNumber => $line) {
            if (!is_string($line)) {
                Log::warning('LuaConsoleMonitor: Non-string line found', [
                    'line_number' => $lineNumber,
                    'line_type' => gettype($line),
                    'line_value' => $line
                ]);
                continue;
            }
            
            $line = trim($line);
            
            // Détecter les erreurs Lua qui commencent par [ERROR]
            if ($this->isLuaError($line)) {
                $error = $this->extractLuaError($line, $lines, $lineNumber);
                if ($error && is_array($error)) {
                    $errors[] = $error;
                }
            }
        }

        return $errors;
    }

    /**
     * Vérifie si une ligne contient une erreur Lua
     */
    private function isLuaError(string $line): bool
    {
        // Les erreurs Lua commencent par [ERROR]
        return Str::startsWith($line, '[ERROR]');
    }

    /**
     * Extrait les détails d'une erreur Lua
     */
    private function extractLuaError(string $errorLine, array $allLines, int $lineNumber): ?array
    {
        $error = [
            'message' => $errorLine,
            'addon' => $this->extractAddonName($errorLine),
            'stack_trace' => $this->extractStackTrace($allLines, $lineNumber),
            'level' => 'error',
            'timestamp' => now()
        ];

        return $error;
    }

    /**
     * Extrait le nom de l'addon depuis l'erreur
     */
    private function extractAddonName(string $errorLine): string
    {
        // Patterns pour détecter le nom de l'addon
        if (preg_match('/lua_run:(\d+):/', $errorLine)) {
            return 'Console Command';
        }
        
        if (preg_match('/attempt to call global \'([^\']+)\'/', $errorLine, $matches)) {
            return 'Global Function: ' . $matches[1];
        }
        
        if (preg_match('/attempt to index ([^\s]+)/', $errorLine, $matches)) {
            return 'Index Error: ' . $matches[1];
        }
        
        return 'Unknown Addon';
    }

    /**
     * Extrait la stack trace depuis les lignes suivantes
     */
    private function extractStackTrace(array $allLines, int $errorLineNumber): string
    {
        if (!is_array($allLines) || empty($allLines)) {
            return '';
        }
        
        $stackTrace = [];
        $maxLines = 10;
        
        // Prendre quelques lignes avant et après l'erreur pour le contexte
        $startLine = max(0, $errorLineNumber - 2);
        $endLine = min(count($allLines) - 1, $errorLineNumber + $maxLines);
        
        for ($i = $startLine; $i <= $endLine; $i++) {
            if (isset($allLines[$i]) && is_string($allLines[$i])) {
                $line = trim($allLines[$i]);
                if (!empty($line)) {
                    $prefix = ($i === $errorLineNumber) ? '>>> ' : '    ';
                    $stackTrace[] = $prefix . $line;
                }
            }
        }

        return implode("\n", $stackTrace);
    }

    /**
     * Enregistre les nouvelles erreurs en base de données
     */
    private function saveNewErrors(Server $server, array $errors): array
    {
        $newErrors = [];
        
        foreach ($errors as $error) {
            try {
                // Créer une clé unique pour cette erreur
                $errorKey = md5($error['message'] . '|' . $error['addon']);
                
                // Vérifier si l'erreur existe déjà
                $existingError = LuaError::where('error_key', $errorKey)
                    ->where('server_id', $server->id)
                    ->where('status', 'open')
                    ->first();
                
                if ($existingError) {
                    // Erreur existante, incrémenter le compteur
                    $existingError->increment('count');
                    $existingError->update(['last_seen' => now()]);
                    
                    Log::info('LuaConsoleMonitor: Existing error updated', [
                        'server_id' => $server->id,
                        'error_key' => $errorKey,
                        'new_count' => $existingError->count + 1
                    ]);
                } else {
                    // Nouvelle erreur, la créer
                    $luaError = LuaError::create([
                        'server_id' => $server->id,
                        'error_key' => $errorKey,
                        'level' => $error['level'],
                        'message' => $error['message'],
                        'addon' => $error['addon'],
                        'stack_trace' => $error['stack_trace'],
                        'first_seen' => now(),
                        'last_seen' => now(),
                        'count' => 1,
                        'status' => 'open',
                        'resolved' => false,
                        'resolved_at' => null,
                        'closed_at' => null
                    ]);
                    
                    $newErrors[] = $luaError;
                    
                    Log::info('LuaConsoleMonitor: New error created', [
                        'server_id' => $server->id,
                        'error_key' => $errorKey,
                        'message' => substr($error['message'], 0, 100)
                    ]);
                }
                
            } catch (\Exception $e) {
                Log::error('LuaConsoleMonitor: Failed to save error', [
                    'server_id' => $server->id,
                    'error' => $e->getMessage(),
                    'error_data' => $error
                ]);
            }
        }
        
        return $newErrors;
    }

    /**
     * Vérifie si c'est un serveur Garry's Mod
     */
    private function isGarrysModServer(Server $server): bool
    {
        return $server->egg && $server->egg->name === 'Garrys Mod';
    }

    /**
     * Récupère l'URL du daemon pour le serveur
     */
    private function getDaemonUrl(Server $server): string
    {
        $node = $server->node;
        $scheme = $node->scheme ?? 'http';
        $host = $node->fqdn ?? $node->ip;
        $port = $node->daemon_listen ?? 8080;
        
        return "{$scheme}://{$host}:{$port}";
    }

    /**
     * Récupère le token d'authentification pour le daemon
     */
    private function getDaemonToken(Server $server): string
    {
        $node = $server->node;
        
        if (isset($node->daemon_token)) {
            return $node->daemon_token;
        }
        
        // Fallback vers la configuration globale
        return config('panel.daemon.token') ?? env('DAEMON_TOKEN', '');
    }
}
