<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Models\LuaError;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class LuaLogService
{
    /**
     * Surveille la console en temps réel et capture les erreurs Lua
     */
    public function monitorConsole(Server $server, ?string $lastCheckTime = null): array
    {
        if (!$this->isGarrysModServer($server)) {
            Log::channel('lua')->info('Server is not Garry\'s Mod', ['server_id' => $server->id]);
            return [];
        }

        try {
            // Construire l'URL avec un paramètre de temps pour ne récupérer que les nouveaux logs
            $daemonUrl = $this->getDaemonUrl($server) . '/api/servers/' . $server->uuid . '/logs';
            if ($lastCheckTime) {
                $daemonUrl .= '?since=' . urlencode($lastCheckTime);
            }
            
            Log::channel('lua')->info('Monitoring console', [
                'server_id' => $server->id,
                'daemon_url' => $daemonUrl,
                'last_check_time' => $lastCheckTime
            ]);

            // Récupérer le token d'authentification
            $token = $this->getDaemonToken($server);
            Log::channel('lua')->info('Using daemon token', [
                'server_id' => $server->id,
                'token_length' => strlen($token),
                'token_preview' => $token ? substr($token, 0, 10) . '...' : 'empty'
            ]);
            
            // Récupérer les logs de la console via l'API du daemon
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->get($daemonUrl);
            
            if ($response->successful()) {
                $consoleOutput = $response->body();
                Log::channel('lua')->info('Console output received', [
                    'server_id' => $server->id,
                    'output_length' => strlen($consoleOutput),
                    'first_100_chars' => substr($consoleOutput, 0, 100)
                ]);
                
                // Parser la réponse JSON et extraire le contenu de la console
                $parsedOutput = $this->parseDaemonResponse($consoleOutput);
                Log::channel('lua')->info('Console output parsed', [
                    'server_id' => $server->id,
                    'parsed_length' => strlen($parsedOutput),
                    'first_100_chars_parsed' => substr($parsedOutput, 0, 100)
                ]);
                
                $errors = $this->parseConsoleForLuaErrors($parsedOutput);
                Log::channel('lua')->info('Lua errors parsed', [
                    'server_id' => $server->id,
                    'errors_count' => count($errors)
                ]);
                
                return $errors;
            } else {
                Log::channel('lua')->warning('Daemon API response not successful', [
                    'server_id' => $server->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('lua')->error('Failed to monitor console', [
                'server_id' => $server->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return [];
    }

    /**
     * Parse la réponse JSON du daemon et extrait le contenu de la console
     */
    private function parseDaemonResponse(string $responseBody): string
    {
        try {
            $data = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::channel('lua')->warning('Failed to parse JSON response', [
                    'json_error' => json_last_error_msg(),
                    'response_preview' => substr($responseBody, 0, 200)
                ]);
                return $responseBody; // Retourner le contenu brut si JSON invalide
            }
            
            // Extraire le tableau 'data' qui contient les lignes de la console
            if (isset($data['data']) && is_array($data['data'])) {
                $consoleLines = $data['data'];
                Log::channel('lua')->info('JSON response parsed successfully', [
                    'data_lines_count' => count($consoleLines)
                ]);
                
                // Joindre les lignes avec des retours à la ligne
                return implode("\n", $consoleLines);
            }
            
            Log::channel('lua')->warning('No data array found in JSON response', [
                'response_keys' => array_keys($data ?? [])
            ]);
            return $responseBody;
            
        } catch (\Exception $e) {
            Log::channel('lua')->error('Exception parsing daemon response', [
                'error' => $e->getMessage(),
                'response_preview' => substr($responseBody, 0, 200)
            ]);
            return $responseBody;
        }
    }

    /**
     * Parse la sortie console pour détecter les erreurs Lua
     */
    private function parseConsoleForLuaErrors(string $consoleOutput): array
    {
        $errors = [];
        $lines = explode("\n", $consoleOutput);
        
        Log::channel('lua')->info('Parsing console output', [
            'total_lines' => count($lines),
            'output_length' => strlen($consoleOutput)
        ]);
        
        foreach ($lines as $lineNumber => $line) {
            $line = trim($line);
            
            // Debug: logger TOUTES les lignes pour voir ce qui arrive
            if (!empty($line)) {
                Log::channel('lua')->info('Processing line', [
                    'line_number' => $lineNumber + 1,
                    'line_content' => $line,
                    'line_length' => strlen($line),
                    'starts_with_error' => preg_match('/^\[ERROR\]/i', $line) ? 'YES' : 'NO'
                ]);
            }
            
            // Détecter les erreurs Lua
            if ($this->isLuaError($line)) {
                Log::channel('lua')->info('Lua error detected', [
                    'line_number' => $lineNumber + 1,
                    'line_content' => $line
                ]);
                
                $error = $this->extractLuaError($line, $lines, $lineNumber);
                if ($error) {
                    $errors[] = $error;
                    Log::channel('lua')->info('Error extracted successfully', [
                        'line_number' => $lineNumber + 1,
                        'error_type' => $error['error_type'] ?? 'unknown',
                        'addon' => $error['addon'] ?? 'unknown'
                    ]);
                }
            } else {
                // Debug: logger pourquoi la ligne n'est PAS détectée comme erreur
                if (!empty($line)) {
                    Log::channel('lua')->info('Line NOT detected as Lua error', [
                        'line_number' => $lineNumber + 1,
                        'line_content' => $line,
                        'reason' => 'isLuaError() returned false'
                    ]);
                }
            }
        }

        Log::channel('lua')->info('Console parsing completed', [
            'total_errors_found' => count($errors)
        ]);

        return $errors;
    }

    /**
     * Vérifie si une ligne contient une erreur Lua
     */
    /**
     * Vérifie si une ligne contient une erreur Lua
     * Les erreurs Lua commencent toujours par [ERROR]
     */
    private function isLuaError(string $line): bool
    {
        // Vérifier d'abord le pattern principal [ERROR]
        if (!preg_match('/^\[ERROR\]/i', $line)) {
            Log::channel('lua')->debug('Line does not start with [ERROR]', [
                'line_content' => $line
            ]);
            return false;
        }

        Log::channel('lua')->debug('Line starts with [ERROR], checking Lua patterns', [
            'line_content' => $line
        ]);

        // Vérifier que c'est bien une erreur Lua (pas juste un [ERROR] générique)
        $luaErrorPatterns = [
            '/lua_run:\d+:/',           // lua_run:1: attempt to call...
            '/attempt to call global/',  // attempt to call global 'caca'
            '/attempt to index/',        // attempt to index nil value
            '/bad argument/',            // bad argument #1
            '/syntax error/',            // syntax error
            '/nil value/',               // nil value
            '/missing dependency/',      // missing dependency
            '/failed to load/',          // failed to load
            '/error in addon/',          // error in addon
            '/stack traceback:/',        // stack traceback
            '/lua error/',               // lua error
        ];

        foreach ($luaErrorPatterns as $pattern) {
            if (preg_match($pattern, $line)) {
                Log::channel('lua')->debug('Lua pattern matched', [
                    'line_content' => $line,
                    'pattern' => $pattern
                ]);
                return true;
            }
        }

        // Si on a [ERROR] mais pas de pattern Lua spécifique, on considère quand même que c'est une erreur
        // car [ERROR] indique généralement une erreur Lua dans Garry's Mod
        Log::channel('lua')->debug('No specific Lua pattern matched, but [ERROR] present - treating as Lua error', [
            'line_content' => $line
        ]);
        return true;
    }

    /**
     * Extrait les détails d'une erreur Lua
     */
    private function extractLuaError(string $errorLine, array $allLines, int $lineNumber): ?array
    {
        $error = [
            'timestamp' => now()->toISOString(),
            'level' => 'error',
            'message' => $errorLine,
            'addon' => $this->extractAddonName($errorLine),
            'stack_trace' => $this->extractStackTrace($allLines, $lineNumber),
            'line_number' => $lineNumber + 1,
            'error_type' => $this->categorizeLuaError($errorLine),
        ];

        return $error;
    }

    /**
     * Extrait le nom de l'addon depuis l'erreur
     */
    private function extractAddonName(string $errorLine): ?string
    {
        // Patterns pour détecter le nom de l'addon
        $patterns = [
            '/addon[:\s]+([^\s,]+)/i',
            '/lua_run:(\d+):/',
            '/in addon[:\s]+([^\s,]+)/i',
            '/addon\s+([^\s,]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $errorLine, $matches)) {
                return $matches[1] ?? 'Unknown';
            }
        }

        // Si pas de nom d'addon trouvé, essayer de l'extraire du contexte
        if (strpos($errorLine, 'lua_run:') !== false) {
            return 'Console Command';
        }

        // Essayer de détecter le nom de l'addon depuis le message d'erreur
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
        $stackTrace = [];
        $maxLines = 10; // Limiter le nombre de lignes pour la stack trace
        
        // Prendre quelques lignes avant l'erreur pour le contexte
        $startLine = max(0, $errorLineNumber - 2);
        $endLine = min(count($allLines) - 1, $errorLineNumber + $maxLines);
        
        for ($i = $startLine; $i <= $endLine; $i++) {
            $line = trim($allLines[$i]);
            if (!empty($line)) {
                $prefix = ($i === $errorLineNumber) ? '>>> ' : '    ';
                $stackTrace[] = $prefix . $line;
            }
        }

        return implode("\n", $stackTrace);
    }

    /**
     * Catégorise le type d'erreur Lua
     */
    private function categorizeLuaError(string $errorLine): string
    {
        $errorLine = strtolower($errorLine);
        
        if (strpos($errorLine, 'attempt to call global') !== false) {
            return 'Call nil value';
        }
        
        if (strpos($errorLine, 'attempt to index') !== false) {
            return 'Index nil value';
        }
        
        if (strpos($errorLine, 'bad argument') !== false) {
            return 'Bad argument';
        }
        
        if (strpos($errorLine, 'syntax error') !== false) {
            return 'Syntax error';
        }
        
        if (strpos($errorLine, 'nil value') !== false) {
            return 'Nil value error';
        }
        
        if (strpos($errorLine, 'missing dependency') !== false) {
            return 'Missing dependency';
        }
        
        if (strpos($errorLine, 'failed to load') !== false) {
            return 'Load failure';
        }
        
        return 'Other error';
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
        // Le token est généralement stocké dans la configuration du nœud
        $node = $server->node;
        
        // Essayer de récupérer le token depuis la configuration du nœud
        if (isset($node->daemon_token)) {
            return $node->daemon_token;
        }
        
        // Fallback : utiliser un token par défaut ou depuis l'environnement
        $defaultToken = config('panel.daemon.token') ?? env('DAEMON_TOKEN', '');
        
        if (empty($defaultToken)) {
            Log::channel('lua')->warning('No daemon token found', [
                'server_id' => $server->id,
                'node_id' => $node->id
            ]);
            return '';
        }
        
        return $defaultToken;
    }

    /**
     * Récupère les logs Lua d'un serveur (base de données ou fichiers selon disponibilité)
     */
    public function getLogs(Server $server, array $filters = []): array
    {
        // Vérifier que c'est un serveur Garry's Mod
        if (!$this->isGarrysModServer($server)) {
            return [];
        }

        try {
            // Récupérer seulement les erreurs ouvertes (status = 'open') directement en base
            $query = LuaError::forServer($server->id)
                ->where('status', 'open')
                ->whereNull('closed_at');

            // Appliquer les filtres
            if (isset($filters['resolved'])) {
                if ($filters['resolved']) {
                    $query->resolved();
                } else {
                    $query->unresolved();
                }
            }

            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhere('addon', 'like', "%{$search}%");
                });
            }

            if (isset($filters['time']) && !empty($filters['time'])) {
                $query = $this->applyTimeFilter($query, $filters['time']);
            }

            $logs = $query->orderBy('last_seen', 'desc')->get();

            \Log::channel('lua')->info('Open logs retrieved from database (status = open only)', [
                'server_id' => $server->id,
                'logs_count' => $logs->count(),
                'filters' => $filters,
                'query_conditions' => 'status = "open" AND closed_at IS NULL'
            ]);

            return $logs->map(function($log) {
                return [
                    'id' => $log->id,
                    'error_key' => $log->error_key,
                    'level' => $log->level,
                    'message' => $log->message,
                    'addon' => $log->addon,
                    'stack_trace' => $log->stack_trace,
                    'count' => $log->count,
                    'first_seen' => $log->first_seen,
                    'last_seen' => $log->last_seen,
                    'status' => $log->status,
                    'resolved' => $log->resolved,
                    'resolved_at' => $log->resolved_at,
                    'closed_at' => $log->closed_at,
                    'server_id' => $log->server_id,
                ];
            })->toArray();

        } catch (\Exception $e) {
            \Log::channel('lua')->warning('Database unavailable, falling back to file system', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback vers les fichiers
            return $this->getLogsFromFiles($server, $filters);
        }
    }

    /**
     * Récupère les logs depuis les fichiers (fallback)
     */
    private function getLogsFromFiles(Server $server, array $filters = []): array
    {
        $logPath = $this->getLogPath($server);
        
        if (!Storage::disk('local')->exists($logPath)) {
            return [];
        }

        $logs = $this->parseLogFile($logPath);
        
        return $this->applyFilters($logs, $filters);
    }

    /**
     * Ajoute un nouveau log (base de données ou fichiers selon disponibilité)
     */
    public function addLog(Server $server, string $level, string $message, ?string $addon = null, ?string $stackTrace = null): void
    {
        if (!$this->isGarrysModServer($server)) {
            return;
        }

        try {
            // Créer un hash unique pour cette erreur
            $errorKey = md5($message . '|' . ($addon ?? 'unknown'));

            // Créer directement l'erreur sans vérification en base
            $luaError = LuaError::create([
                'server_id' => $server->id,
                'error_key' => $errorKey,
                'level' => $level,
                'message' => $message,
                'addon' => $addon,
                'stack_trace' => $stackTrace,
                'first_seen' => now(),
                'last_seen' => now(),
                'count' => 1,
                'status' => 'open',
                'resolved' => false,
            ]);

            Log::channel('lua')->info('New Lua error created in database', [
                'server_id' => $server->id,
                'error_key' => $errorKey,
                'message' => $message,
                'addon' => $addon,
                'count' => 1,
            ]);

        } catch (\Exception $e) {
            Log::channel('lua')->warning('Database unavailable, falling back to file system', [
                'server_id' => $server->id,
                'message' => $message,
                'addon' => $addon,
                'error' => $e->getMessage()
            ]);
            
            // Fallback vers les fichiers
            $this->addLogToFiles($server, $level, $message, $addon, $stackTrace);
        }
    }

    /**
     * Ajoute un log aux fichiers (fallback)
     */
    private function addLogToFiles(Server $server, string $level, string $message, ?string $addon = null, ?string $stackTrace = null): void
    {
        $logEntry = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message,
            'addon' => $addon,
            'stack_trace' => $stackTrace,
            'server_id' => $server->id,
        ];

        $logPath = $this->getLogPath($server);
        
        // Ajouter le log au fichier
        $this->appendToLogFile($logPath, $logEntry);
        
        // Logger aussi dans Laravel pour le debugging
        Log::channel('lua')->info('Lua log added to file', $logEntry);
    }

    /**
     * Efface les logs d'un serveur
     */
    public function clearLogs(Server $server): bool
    {
        if (!$this->isGarrysModServer($server)) {
            return false;
        }

        $logPath = $this->getLogPath($server);
        
        if (Storage::disk('local')->exists($logPath)) {
            return Storage::disk('local')->delete($logPath);
        }

        return true;
    }

    /**
     * Exporte les logs d'un serveur
     */
    public function exportLogs(Server $server, string $format = 'json'): string
    {
        if (!$this->isGarrysModServer($server)) {
            return '';
        }

        $logs = $this->getLogs($server);
        
        switch ($format) {
            case 'json':
                return json_encode($logs, JSON_PRETTY_PRINT);
            case 'csv':
                return $this->exportToCsv($logs);
            case 'txt':
                return $this->exportToTxt($logs);
            default:
                return json_encode($logs);
        }
    }

    /**
     * Récupère les statistiques des logs
     */
    public function getLogStats(Server $server): array
    {
        if (!$this->isGarrysModServer($server)) {
            return [
                'critical_errors' => 0,
                'warnings' => 0,
                'info' => 0,
                'total' => 0,
            ];
        }

        $logs = $this->getLogs($server);
        
        $stats = [
            'critical_errors' => 0,
            'warnings' => 0,
            'info' => 0,
            'total' => count($logs),
        ];

        foreach ($logs as $log) {
            switch ($log['level']) {
                case 'error':
                    $stats['critical_errors']++;
                    break;
                case 'warning':
                    $stats['warnings']++;
                    break;
                case 'info':
                    $stats['info']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Récupère le top des addons avec erreurs
     */
    public function getTopAddonErrors(Server $server, int $limit = 10): array
    {
        if (!$this->isGarrysModServer($server)) {
            return [];
        }

        $logs = $this->getLogs($server);
        $addonErrors = [];

        foreach ($logs as $log) {
            if ($log['level'] === 'error' && $log['addon']) {
                $addon = $log['addon'];
                if (!isset($addonErrors[$addon])) {
                    $addonErrors[$addon] = 0;
                }
                $addonErrors[$addon]++;
            }
        }

        arsort($addonErrors);
        
        return array_slice($addonErrors, 0, $limit, true);
    }

    /**
     * Récupère le top des types d'erreurs
     */
    public function getTopErrorTypes(Server $server, int $limit = 10): array
    {
        if (!$this->isGarrysModServer($server)) {
            return [];
        }

        $logs = $this->getLogs($server);
        $errorTypes = [];

        foreach ($logs as $log) {
            if ($log['level'] === 'error') {
                $message = $log['message'];
                $key = $this->categorizeError($message);
                
                if (!isset($errorTypes[$key])) {
                    $errorTypes[$key] = 0;
                }
                $errorTypes[$key]++;
            }
        }

        arsort($errorTypes);
        
        return array_slice($errorTypes, 0, $limit, true);
    }

    /**
     * Vérifie si un serveur est un serveur Garry's Mod
     */
    private function isGarrysModServer(Server $server): bool
    {
        return $server->egg && $server->egg->name === 'Garrys Mod';
    }

    /**
     * Récupère le chemin du fichier de log
     */
    private function getLogPath(Server $server): string
    {
        return "lua_logs/server_{$server->id}.log";
    }

    /**
     * Parse le fichier de log
     */
    private function parseLogFile(string $logPath): array
    {
        $content = Storage::disk('local')->get($logPath);
        
        if (!$content) {
            return [];
        }

        $lines = explode("\n", $content);
        $logs = [];

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $log = json_decode($line, true);
            if ($log && is_array($log)) {
                $logs[] = $log;
            }
        }

        return $logs;
    }

    /**
     * Ajoute une entrée au fichier de log
     */
    private function appendToLogFile(string $logPath, array $logEntry): void
    {
        $logLine = json_encode($logEntry) . "\n";
        
        if (Storage::disk('local')->exists($logPath)) {
            Storage::disk('local')->append($logPath, $logLine);
        } else {
            Storage::disk('local')->put($logPath, $logLine);
        }
    }

    /**
     * Écrit un fichier de log complet (remplace le contenu existant)
     */
    private function writeLogFile(string $logPath, array $logs): void
    {
        $content = '';
        foreach ($logs as $log) {
            $content .= json_encode($log) . "\n";
        }
        
        Storage::disk('local')->put($logPath, $content);
    }

    /**
     * Applique les filtres aux logs
     */
    private function applyFilters(array $logs, array $filters): array
    {
        $filteredLogs = $logs;

        // Filtre par niveau
        if (isset($filters['level']) && !empty($filters['level'])) {
            $filteredLogs = array_filter($filteredLogs, function ($log) use ($filters) {
                return $log['level'] === $filters['level'];
            });
        }

        // Filtre par recherche
        if (isset($filters['search']) && !empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $filteredLogs = array_filter($filteredLogs, function ($log) use ($search) {
                return Str::contains(strtolower($log['message']), $search) ||
                       Str::contains(strtolower($log['addon'] ?? ''), $search);
            });
        }

        // Filtre par temps
        if (isset($filters['time']) && !empty($filters['time'])) {
            $filteredLogs = $this->filterByTime($filteredLogs, $filters['time']);
        }

        return array_values($filteredLogs);
    }

    /**
     * Applique un filtre de temps à une requête Eloquent
     */
    private function applyTimeFilter($query, string $timeFilter)
    {
        $now = now();
        
        switch ($timeFilter) {
            case '1h':
                $cutoff = $now->subHour();
                break;
            case '24h':
                $cutoff = $now->subDay();
                break;
            case '7d':
                $cutoff = $now->subWeek();
                break;
            case '30d':
                $cutoff = $now->subMonth();
                break;
            default:
                return $query;
        }

        return $query->where('last_seen', '>=', $cutoff);
    }

    /**
     * Filtre les logs par temps
     */
    private function filterByTime(array $logs, string $timeFilter): array
    {
        $now = now();
        
        switch ($timeFilter) {
            case '1h':
                $cutoff = $now->subHour();
                break;
            case '24h':
                $cutoff = $now->subDay();
                break;
            case '7d':
                $cutoff = $now->subWeek();
                break;
            case '30d':
                $cutoff = $now->subMonth();
                break;
            default:
                return $logs;
        }

        return array_filter($logs, function ($log) use ($cutoff) {
            $logTime = \Carbon\Carbon::parse($log['timestamp']);
            return $logTime->gte($cutoff);
        });
    }

    /**
     * Catégorise une erreur
     */
    private function categorizeError(string $message): string
    {
        $message = strtolower($message);
        
        if (Str::contains($message, 'attempt to index a nil value')) {
            return 'Index nil value';
        }
        
        if (Str::contains($message, 'attempt to call a nil value')) {
            return 'Call nil value';
        }
        
        if (Str::contains($message, 'bad argument')) {
            return 'Bad argument';
        }
        
        if (Str::contains($message, 'missing dependency')) {
            return 'Missing dependency';
        }
        
        if (Str::contains($message, 'syntax error')) {
            return 'Syntax error';
        }
        
        return 'Other error';
    }

    /**
     * Exporte les logs en CSV
     */
    private function exportToCsv(array $logs): string
    {
        if (empty($logs)) {
            return '';
        }

        $headers = ['Timestamp', 'Level', 'Addon', 'Message', 'Stack Trace'];
        $csv = implode(',', $headers) . "\n";

        foreach ($logs as $log) {
            $row = [
                $log['timestamp'] ?? '',
                $log['level'] ?? '',
                $log['addon'] ?? '',
                str_replace('"', '""', $log['message'] ?? ''),
                str_replace('"', '""', $log['stack_trace'] ?? ''),
            ];
            
            $csv .= '"' . implode('","', $row) . '"' . "\n";
        }

        return $csv;
    }

    /**
     * Exporte les logs en TXT
     */
    private function exportToTxt(array $logs): string
    {
        if (empty($logs)) {
            return '';
        }

        $output = "Lua Error Logs\n";
        $output .= "Generated: " . now()->toDateTimeString() . "\n";
        $output .= str_repeat("=", 50) . "\n\n";

        foreach ($logs as $log) {
            $output .= "[" . ($log['timestamp'] ?? 'N/A') . "] ";
            $output .= strtoupper($log['level'] ?? 'UNKNOWN') . " ";
            $output .= "(" . ($log['addon'] ?? 'Unknown Addon') . "): ";
            $output .= $log['message'] ?? 'No message' . "\n";
            
            if (!empty($log['stack_trace'])) {
                $output .= "Stack Trace:\n" . $log['stack_trace'] . "\n";
            }
            
            $output .= "\n";
        }

        return $output;
    }

    /**
     * Ferme un log (suppression soft) au lieu de le supprimer physiquement
     */
    public function deleteLog(string $errorKey, int $serverId): bool
    {
        try {
            $luaError = LuaError::where('error_key', $errorKey)
                ->where('server_id', $serverId)
                ->where('status', '!=', 'closed')
                ->whereNull('closed_at')
                ->first();

            if (!$luaError) {
                \Log::warning('LuaLogService: Cannot find error to close', [
                    'error_key' => $errorKey,
                    'server_id' => $serverId
                ]);
                return false;
            }

            // Fermer l'erreur directement en base (soft delete)
            $luaError->update([
                'status' => 'closed',
                'closed_at' => now()
            ]);

            \Log::info('LuaLogService: Error closed in database', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'status' => 'closed',
                'closed_at' => now()
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('LuaLogService: Error closing error', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function markAsResolved(string $errorKey, int $serverId): bool
    {
        try {
            $luaError = LuaError::where('error_key', $errorKey)
                ->where('server_id', $serverId)
                ->where('status', '!=', 'closed')
                ->whereNull('closed_at')
                ->first();

            if (!$luaError) {
                \Log::warning('LuaLogService: Cannot find error to resolve', [
                    'error_key' => $errorKey,
                    'server_id' => $serverId
                ]);
                return false;
            }

            // Marquer comme résolu directement en base
            $luaError->update([
                'status' => 'resolved',
                'resolved' => true,
                'resolved_at' => now()
            ]);

            \Log::info('LuaLogService: Error marked as resolved in database', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'status' => 'resolved'
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('LuaLogService: Error marking as resolved', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function markAsUnresolved(string $errorKey, int $serverId): bool
    {
        try {
            $luaError = LuaError::where('error_key', $errorKey)
                ->where('server_id', $serverId)
                ->where('status', '!=', 'closed')
                ->whereNull('closed_at')
                ->first();

            if (!$luaError) {
                \Log::warning('LuaLogService: Cannot find error to mark as unresolved', [
                    'error_key' => $errorKey,
                    'server_id' => $serverId
                ]);
                return false;
            }

            // Marquer comme non résolu directement en base
            $luaError->update([
                'status' => 'open',
                'resolved' => false,
                'resolved_at' => null
            ]);

            \Log::info('LuaLogService: Error marked as unresolved in database', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'status' => 'open'
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('LuaLogService: Error marking as unresolved', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function reopenError(string $errorKey, int $serverId): bool
    {
        try {
            $luaError = LuaError::where('error_key', $errorKey)
                ->where('server_id', $serverId)
                ->where('status', 'closed')
                ->whereNotNull('closed_at')
                ->first();

            if (!$luaError) {
                \Log::warning('LuaLogService: Cannot find closed error to reopen', [
                    'error_key' => $errorKey,
                    'server_id' => $serverId
                ]);
                return false;
            }

            // Rouvrir l'erreur directement en base
            $luaError->update([
                'status' => 'open',
                'closed_at' => null
            ]);

            \Log::info('LuaLogService: Error reopened in database', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'status' => 'open'
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('LuaLogService: Error reopening error', [
                'error_key' => $errorKey,
                'server_id' => $serverId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
