<?php

namespace App\Console\Commands;

use App\Services\Servers\LuaConsoleHookService;
use Illuminate\Console\Command;

class MonitorLuaConsole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:monitor 
                            {--server= : Monitor specific server by ID}
                            {--stream : Enable live streaming mode}
                            {--debug : Enable debug mode with test servers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor Lua console output from Garry\'s Mod servers and detect errors with stack traces';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🎮 Starting Lua Console Monitor for Garry\'s Mod servers...');
        $this->info('📡 This will monitor ONLY Garry\'s Mod servers and capture ERROR messages with stack traces');
        
        try {
            $hookService = app(LuaConsoleHookService::class);
            
            // Configurer le serveur ciblé si spécifié
            if ($this->option('server')) {
                $serverId = (int) $this->option('server');
                $hookService->setTargetServerId($serverId);
                $this->info("🎯 Monitoring specific server ID: {$serverId}");
            }
            
            // Activer le mode streaming si demandé
            if ($this->option('stream')) {
                $hookService->enableStreamingMode();
                $this->info('📡 Live streaming mode enabled - Real-time console output');
            }
            
            // Activer le mode debug si demandé
            if ($this->option('debug')) {
                $hookService->setDebugMode(true);
                $this->info('🐛 Debug mode enabled - Using test servers');
            }
            
            $this->info('🔄 Starting monitoring service...');
            $this->info('💡 Press Ctrl+C to stop');
            
            // Démarrer le service
            $hookService->startHooking();
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to start monitoring service: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
