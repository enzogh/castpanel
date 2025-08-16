<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Servers\LuaConsoleMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MonitorLuaErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:monitor-servers {--server-id= : Monitor specific server} {--all : Monitor all Garry\'s Mod servers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Garry\'s Mod servers for Lua errors in background';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Lua error monitoring...');
        
        try {
            $monitorService = app(LuaConsoleMonitorService::class);
            
            if ($this->option('server-id')) {
                // Monitor specific server
                $serverId = $this->option('server-id');
                $server = Server::find($serverId);
                
                if (!$server) {
                    $this->error("❌ Server with ID {$serverId} not found");
                    return 1;
                }
                
                $this->monitorServer($server, $monitorService);
                
            } elseif ($this->option('all')) {
                // Monitor all Garry's Mod servers
                $this->monitorAllServers($monitorService);
                
            } else {
                // Monitor all Garry's Mod servers by default
                $this->monitorAllServers($monitorService);
            }
            
            $this->info('✅ Lua error monitoring completed successfully');
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Error during monitoring: " . $e->getMessage());
            Log::error('LuaMonitor: Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
    
    /**
     * Monitor a specific server
     */
    private function monitorServer(Server $server, LuaConsoleMonitorService $monitorService): void
    {
        $this->info("🔍 Monitoring server: {$server->name} (ID: {$server->id})");
        
        if (!$server->egg || $server->egg->name !== 'Garrys Mod') {
            $this->warn("⚠️  Server {$server->name} is not a Garry's Mod server");
            return;
        }
        
        $this->info("✅ Server {$server->name} is Garry's Mod, starting monitoring...");
        
        // Mettre à jour les compteurs des erreurs existantes
        $this->info("📊 Updating existing error counts...");
        $monitorService->updateExistingErrorCounts($server);
        
        // Surveiller la console pour de nouvelles erreurs
        $this->info("🔍 Monitoring console for new errors...");
        $newErrors = $monitorService->monitorConsole($server);
        
        if (count($newErrors) > 0) {
            $this->info("🚨 Found " . count($newErrors) . " new/updated Lua error(s) on server {$server->name}");
            
            foreach ($newErrors as $error) {
                $countText = isset($error->count) ? " (Count: {$error->count})" : "";
                $this->line("  • " . substr($error->message ?? $error['message'] ?? 'Unknown error', 0, 80) . "..." . $countText);
            }
        } else {
            $this->info("✅ No new Lua errors found on server {$server->name}");
        }
    }
    
    /**
     * Monitor all Garry's Mod servers
     */
    private function monitorAllServers(LuaConsoleMonitorService $monitorService): void
    {
        $this->info("🔍 Finding all Garry's Mod servers...");
        
        $servers = Server::with('egg')
            ->whereHas('egg', function ($query) {
                $query->where('name', 'Garrys Mod');
            })
            ->get();
        
        if ($servers->isEmpty()) {
            $this->warn("⚠️  No Garry's Mod servers found");
            return;
        }
        
        $this->info("✅ Found " . $servers->count() . " Garry's Mod server(s)");
        
        $totalErrors = 0;
        $monitoredServers = 0;
        
        foreach ($servers as $server) {
            try {
                $this->line("🔍 Monitoring {$server->name}...");
                
                $newErrors = $monitorService->monitorConsole($server);
                $totalErrors += count($newErrors);
                $monitoredServers++;
                
                if (count($newErrors) > 0) {
                    $this->line("  🚨 Found " . count($newErrors) . " new error(s)");
                } else {
                    $this->line("  ✅ No new errors");
                }
                
            } catch (\Exception $e) {
                $this->error("  ❌ Error monitoring {$server->name}: " . $e->getMessage());
                Log::error('LuaMonitor: Server monitoring failed', [
                    'server_id' => $server->id,
                    'server_name' => $server->name,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        $this->info("📊 Monitoring summary:");
        $this->line("  • Servers monitored: {$monitoredServers}");
        $this->line("  • Total new errors: {$totalErrors}");
    }
}
