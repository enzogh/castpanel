<?php

namespace App\Services\Servers;

use App\Models\LuaError;
use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LuaLogService
{
    /**
     * Vérifie si c'est un serveur Garry's Mod
     */
    private function isGarrysModServer(Server $server): bool
    {
        return $server->egg && $server->egg->name === 'Garrys Mod';
    }

    /**
     * Récupère les logs d'erreurs Lua
     */
    public function getLogs(Server $server, array $filters = []): array
    {
        if (!$this->isGarrysModServer($server)) {
            return [];
        }

        try {
            // Récupérer seulement les erreurs ouvertes
            $query = LuaError::forServer($server->id)
                ->where('status', 'open')
                ->whereNull('closed_at');

            // Appliquer les filtres
            if (isset($filters['search']) && !empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function($q) use ($search) {
                    $q->where('message', 'like', "%{$search}%")
                      ->orWhere('addon', 'like', "%{$search}%");
                });
            }

            if (isset($filters['level']) && $filters['level'] !== 'all') {
                $query->where('level', $filters['level']);
            }

            if (isset($filters['time']) && $filters['time'] !== 'all') {
                $query = $this->applyTimeFilter($query, $filters['time']);
            }

            if (isset($filters['show_resolved']) && !$filters['show_resolved']) {
                $query->where('resolved', false);
            }

            $logs = $query->orderBy('last_seen', 'desc')->get();

            Log::info('LuaLogService: Logs retrieved successfully', [
                'server_id' => $server->id,
                'logs_count' => $logs->count(),
                'filters' => $filters
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
            Log::error('LuaLogService: Failed to retrieve logs', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);

            return [];
        }
    }

    /**
     * Marque une erreur comme résolue
     */
    public function markAsResolved(string $errorKey, int $serverId): bool
    {
        try {
            $error = LuaError::where('error_key', $errorKey)
                ->where('server_id', $serverId)
                ->first();

            if ($error) {
                $error->update([
                    'status' => 'resolved',
                    'resolved' => true,
                    'resolved_at' => now()
                ]);

                Log::info('LuaLogService: Error marked as resolved', [
                    'server_id' => $serverId,
                    'error_key' => $errorKey
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('LuaLogService: Failed to mark error as resolved', [
                'server_id' => $serverId,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Marque une erreur comme non résolue
     */
    public function markAsUnresolved(string $errorKey, int $serverId): bool
    {
        try {
            $error = LuaError::where('error_key', $errorKey)
                ->where('server_id', $serverId)
                ->first();

            if ($error) {
                $error->update([
                    'status' => 'open',
                    'resolved' => false,
                    'resolved_at' => null
                ]);

                Log::info('LuaLogService: Error marked as unresolved', [
                    'server_id' => $serverId,
                    'error_key' => $errorKey
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('LuaLogService: Failed to mark error as unresolved', [
                'server_id' => $serverId,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Supprime (ferme) une erreur
     */
    public function deleteLog(string $errorKey, int $serverId): bool
    {
        try {
            $error = LuaError::where('error_key', $errorKey)
                ->where('server_id', $serverId)
                ->first();

            if ($error) {
                $error->update([
                    'status' => 'closed',
                    'closed_at' => now()
                ]);

                Log::info('LuaLogService: Error closed', [
                    'server_id' => $serverId,
                    'error_key' => $errorKey
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('LuaLogService: Failed to close error', [
                'server_id' => $serverId,
                'error_key' => $errorKey,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Efface tous les logs d'un serveur
     */
    public function clearLogs(Server $server): bool
    {
        try {
            $count = LuaError::where('server_id', $server->id)->delete();

            Log::info('LuaLogService: Logs cleared', [
                'server_id' => $server->id,
                'deleted_count' => $count
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('LuaLogService: Failed to clear logs', [
                'server_id' => $server->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Exporte les logs dans différents formats
     */
    public function exportLogs(Server $server, string $format = 'json'): string
    {
        try {
            $logs = $this->getLogs($server);

            switch ($format) {
                case 'json':
                    return json_encode($logs, JSON_PRETTY_PRINT);
                case 'csv':
                    return $this->toCsv($logs);
                case 'txt':
                    return $this->toText($logs);
                default:
                    return json_encode($logs);
            }

        } catch (\Exception $e) {
            Log::error('LuaLogService: Failed to export logs', [
                'server_id' => $server->id,
                'format' => $format,
                'error' => $e->getMessage()
            ]);

            return '';
        }
    }

    /**
     * Applique un filtre de temps
     */
    private function applyTimeFilter($query, string $timeFilter)
    {
        $now = now();

        switch ($timeFilter) {
            case '1h':
                return $query->where('last_seen', '>=', $now->subHour());
            case '24h':
                return $query->where('last_seen', '>=', $now->subDay());
            case '7d':
                return $query->where('last_seen', '>=', $now->subWeek());
            case '30d':
                return $query->where('last_seen', '>=', $now->subMonth());
            default:
                return $query;
        }
    }

    /**
     * Convertit les logs en CSV
     */
    private function toCsv(array $logs): string
    {
        if (empty($logs)) {
            return '';
        }

        $headers = array_keys($logs[0]);
        $csv = implode(',', $headers) . "\n";

        foreach ($logs as $log) {
            $csv .= implode(',', array_map(function($value) {
                return '"' . str_replace('"', '""', $value) . '"';
            }, $log)) . "\n";
        }

        return $csv;
    }

    /**
     * Convertit les logs en texte
     */
    private function toText(array $logs): string
    {
        if (empty($logs)) {
            return 'Aucun log trouvé.';
        }

        $text = "Logs d'erreurs Lua\n";
        $text .= str_repeat('=', 50) . "\n\n";

        foreach ($logs as $log) {
            $text .= "Erreur #{$log['id']}\n";
            $text .= "Message: {$log['message']}\n";
            $text .= "Addon: {$log['addon']}\n";
            $text .= "Première fois: {$log['first_seen']}\n";
            $text .= "Dernière fois: {$log['last_seen']}\n";
            $text .= "Compteur: {$log['count']}x\n";
            $text .= str_repeat('-', 30) . "\n\n";
        }

        return $text;
    }
}
