<?php

namespace App\Console\Commands;

use App\Services\Servers\LuaConsoleHookService;
use Illuminate\Console\Command;

class LuaConsoleDaemon extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:daemon 
                            {action : start|stop|status|restart}
                            {--server= : Monitor specific server by ID}
                            {--interval=5 : Check interval in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Lua Console Hook daemon service for background monitoring';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $hookService = app(LuaConsoleHookService::class);

        switch ($action) {
            case 'start':
                $this->startDaemon($hookService);
                break;
            case 'stop':
                $this->stopDaemon($hookService);
                break;
            case 'status':
                $this->showStatus($hookService);
                break;
            case 'restart':
                $this->restartDaemon($hookService);
                break;
            default:
                $this->error('Invalid action. Use: start, stop, status, or restart');
                return 1;
        }

        return 0;
    }

    /**
     * Démarre le daemon
     */
    private function startDaemon(LuaConsoleHookService $hookService): void
    {
        $this->info('🚀 Starting Lua Console Hook Daemon...');
        
        // Configurer le serveur ciblé si spécifié
        if ($this->option('server')) {
            $serverId = (int) $this->option('server');
            $hookService->setTargetServerId($serverId);
            $this->info("🎯 Monitoring specific server ID: {$serverId}");
        }
        
        // Configurer l'intervalle de vérification
        if ($this->option('interval')) {
            $interval = (int) $this->option('interval');
            $hookService->setCheckInterval($interval);
            $this->info("⏱️ Check interval set to: {$interval} seconds");
        }
        
        try {
            $hookService->startHooking();
            $this->info('✅ Daemon started successfully!');
            $this->info('💡 Use "php artisan lua:daemon status" to check status');
            $this->info('💡 Use "php artisan lua:daemon stop" to stop the daemon');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to start daemon: ' . $e->getMessage());
        }
    }

    /**
     * Arrête le daemon
     */
    private function stopDaemon(LuaConsoleHookService $hookService): void
    {
        $this->info('🛑 Stopping Lua Console Hook Daemon...');
        
        try {
            $hookService->stopDaemon();
            $this->info('✅ Daemon stopped successfully!');
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to stop daemon: ' . $e->getMessage());
        }
    }

    /**
     * Affiche le statut du daemon
     */
    private function showStatus(LuaConsoleHookService $hookService): void
    {
        $this->info('📊 Lua Console Hook Daemon Status');
        $this->info('================================');
        
        $pidFile = storage_path('lua-console-hook.pid');
        
        if (file_exists($pidFile)) {
            $pid = file_get_contents($pidFile);
            $this->info("🟢 Daemon is running (PID: {$pid})");
            
            // Vérifier si le processus existe vraiment
            if (posix_kill($pid, 0)) {
                $this->info('✅ Process is alive');
                
                // Afficher les statistiques si possible
                if ($hookService->isRunning()) {
                    $this->info('📈 Service is active');
                } else {
                    $this->info('⚠️ Service is not active');
                }
            } else {
                $this->info('❌ Process is dead (PID file orphaned)');
                $this->warn('💡 Consider removing the PID file');
            }
        } else {
            $this->info('🔴 Daemon is not running');
        }
        
        $this->info('');
        $this->info('Commands:');
        $this->info('  php artisan lua:daemon start   - Start the daemon');
        $this->info('  php artisan lua:daemon stop    - Stop the daemon');
        $this->info('  php artisan lua:daemon restart - Restart the daemon');
        $this->info('  php artisan lua:daemon status - Show this status');
    }

    /**
     * Redémarre le daemon
     */
    private function restartDaemon(LuaConsoleHookService $hookService): void
    {
        $this->info('🔄 Restarting Lua Console Hook Daemon...');
        
        try {
            $this->stopDaemon($hookService);
            sleep(2); // Attendre que le processus s'arrête
            $this->startDaemon($hookService);
            
        } catch (\Exception $e) {
            $this->error('❌ Failed to restart daemon: ' . $e->getMessage());
        }
    }
}
