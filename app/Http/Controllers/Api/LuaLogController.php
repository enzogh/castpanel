<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Server;
use App\Services\Servers\LuaLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LuaLogController extends Controller
{
    public function __construct(
        private LuaLogService $luaLogService
    ) {}

    /**
     * Récupère les logs Lua d'un serveur
     */
    public function getLogs(Request $request, Server $server): JsonResponse
    {
        // Vérifier les permissions
        if (!Auth::user()->can('view server', $server)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return response()->json(['error' => 'This endpoint is only available for Garry\'s Mod servers'], 400);
        }

        $filters = $request->only(['level', 'search', 'time']);
        $logs = $this->luaLogService->getLogs($server, $filters);

        return response()->json([
            'success' => true,
            'data' => $logs,
            'total' => count($logs)
        ]);
    }

    /**
     * Ajoute un nouveau log Lua
     */
    public function addLog(Request $request, Server $server): JsonResponse
    {
        // Vérifier les permissions
        if (!Auth::user()->can('update server', $server)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return response()->json(['error' => 'This endpoint is only available for Garry\'s Mod servers'], 400);
        }

        $request->validate([
            'level' => 'required|in:error,warning,info',
            'message' => 'required|string|max:1000',
            'addon' => 'nullable|string|max:255',
            'stack_trace' => 'nullable|string|max:5000',
        ]);

        $this->luaLogService->addLog(
            $server,
            $request->level,
            $request->message,
            $request->addon,
            $request->stack_trace
        );

        return response()->json([
            'success' => true,
            'message' => 'Log added successfully'
        ]);
    }

    /**
     * Efface les logs d'un serveur
     */
    public function clearLogs(Request $request, Server $server): JsonResponse
    {
        // Vérifier les permissions
        if (!Auth::user()->can('update server', $server)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return response()->json(['error' => 'This endpoint is only available for Garry\'s Mod servers'], 400);
        }

        $success = $this->luaLogService->clearLogs($server);

        if ($success) {
            return response()->json([
                'success' => true,
                'message' => 'Logs cleared successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Failed to clear logs'
        ], 500);
    }

    /**
     * Exporte les logs d'un serveur
     */
    public function exportLogs(Request $request, Server $server): JsonResponse
    {
        // Vérifier les permissions
        if (!Auth::user()->can('view server', $server)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return response()->json(['error' => 'This endpoint is only available for Garry\'s Mod servers'], 400);
        }

        $request->validate([
            'format' => 'nullable|in:json,csv,txt'
        ]);

        $format = $request->get('format', 'json');
        $exportData = $this->luaLogService->exportLogs($server, $format);

        if (empty($exportData)) {
            return response()->json([
                'success' => false,
                'message' => 'No logs to export'
            ], 404);
        }

        $filename = "lua_logs_server_{$server->id}_" . now()->format('Y-m-d_H-i-s');
        
        switch ($format) {
            case 'csv':
                $filename .= '.csv';
                $contentType = 'text/csv';
                break;
            case 'txt':
                $filename .= '.txt';
                $contentType = 'text/plain';
                break;
            default:
                $filename .= '.json';
                $contentType = 'application/json';
        }

        return response($exportData)
            ->header('Content-Type', $contentType)
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Récupère les statistiques des logs
     */
    public function getStats(Request $request, Server $server): JsonResponse
    {
        // Vérifier les permissions
        if (!Auth::user()->can('view server', $server)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return response()->json(['error' => 'This endpoint is only available for Garry\'s Mod servers'], 400);
        }

        $stats = $this->luaLogService->getLogStats($server);

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Récupère le top des addons avec erreurs
     */
    public function getTopAddonErrors(Request $request, Server $server): JsonResponse
    {
        // Vérifier les permissions
        if (!Auth::user()->can('view server', $server)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return response()->json(['error' => 'This endpoint is only available for Garry\'s Mod servers'], 400);
        }

        $limit = $request->get('limit', 10);
        $topAddons = $this->luaLogService->getTopAddonErrors($server, $limit);

        return response()->json([
            'success' => true,
            'data' => $topAddons
        ]);
    }

    /**
     * Récupère le top des types d'erreurs
     */
    public function getTopErrorTypes(Request $request, Server $server): JsonResponse
    {
        // Vérifier les permissions
        if (!Auth::user()->can('view server', $server)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Vérifier que c'est un serveur Garry's Mod
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            return response()->json(['error' => 'This endpoint is only available for Garry\'s Mod servers'], 400);
        }

        $limit = $request->get('limit', 10);
        $topErrors = $this->luaLogService->getTopErrorTypes($server, $limit);

        return response()->json([
            'success' => true,
            'data' => $topErrors
        ]);
    }
}
