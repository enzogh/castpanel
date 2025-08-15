<?php

namespace App\Services\Servers;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LuaLogService
{
    /**
     * Récupère les logs Lua d'un serveur
     */
    public function getLogs(Server $server, array $filters = []): array
    {
        // Vérifier que c'est un serveur Garry's Mod
        if (!$this->isGarrysModServer($server)) {
            return [];
        }

        $logPath = $this->getLogPath($server);
        
        if (!Storage::disk('local')->exists($logPath)) {
            return [];
        }

        $logs = $this->parseLogFile($logPath);
        
        return $this->applyFilters($logs, $filters);
    }

    /**
     * Ajoute un nouveau log
     */
    public function addLog(Server $server, string $level, string $message, ?string $addon = null, ?string $stackTrace = null): void
    {
        if (!$this->isGarrysModServer($server)) {
            return;
        }

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
        Log::channel('lua')->info('Lua log added', $logEntry);
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
}
