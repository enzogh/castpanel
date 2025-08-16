<?php

namespace App\Console\Commands;

use App\Services\Servers\LuaConsoleHookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class StartLuaConsoleHook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:hook-console {--daemon : Run as daemon process} {--stop : Stop running hook}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start real-time Lua console hook service';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('stop')) {
            return $this->stopHook();
        }

        $this->info('🚀 Starting Lua Console Hook Service...');
        $this->info('📡 This will monitor all Garry\'s Mod servers in real-time');
        $this->info('🔍 Detecting [ERROR] messages immediately as they appear');
        
        try {
            $hookService = app(LuaConsoleHookService::class);
            
            if ($hookService->isRunning()) {
                $this->warn('⚠️  Hook service is already running');
                return 0;
            }

            if ($this->option('daemon')) {
                $this->info('🔄 Running as daemon process...');
                $this->info('💡 Use "php artisan lua:hook-console --stop" to stop the service');
                
                // Démarrer en arrière-plan
                $this->startDaemonHook($hookService);
                
            } else {
                $this->info('🔄 Starting hook service in foreground...');
                $this->info('💡 Press Ctrl+C to stop');
                
                // Démarrer en premier plan
                $this->startForegroundHook($hookService);
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to start hook service: " . $e->getMessage());
            Log::error('LuaConsoleHook: Command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Démarre le hook en mode daemon
     */
    private function startDaemonHook(LuaConsoleHookService $hookService): void
    {
        // Créer un processus en arrière-plan
        $pid = pcntl_fork();
        
        if ($pid === -1) {
            throw new \Exception('Failed to fork process');
        }
        
        if ($pid === 0) {
            // Processus enfant
            $this->info('🔄 Hook service started in background (PID: ' . getmypid() . ')');
            
            // Démarrer le service
            $hookService->startHooking();
            
            exit(0);
        } else {
            // Processus parent
            $this->info("✅ Hook service started with PID: {$pid}");
            $this->info("💾 PID saved to: " . storage_path('lua-hook.pid'));
            
            // Sauvegarder le PID
            file_put_contents(storage_path('lua-hook.pid'), $pid);
        }
    }

    /**
     * Démarre le hook en premier plan
     */
    private function startForegroundHook(LuaConsoleHookService $hookService): void
    {
        // Gérer l'interruption
        pcntl_signal(SIGINT, function () use ($hookService) {
            $this->info("\n🛑 Stopping hook service...");
            $hookService->stopHooking();
            $this->info("✅ Hook service stopped");
            exit(0);
        });

        // Démarrer le service
        $hookService->startHooking();
    }

    /**
     * Arrête le hook en cours d'exécution
     */
    private function stopHook(): int
    {
        $pidFile = storage_path('lua-hook.pid');
        
        if (!file_exists($pidFile)) {
            $this->warn('⚠️  No PID file found. Hook service may not be running.');
            return 0;
        }
        
        $pid = (int) file_get_contents($pidFile);
        
        if (!$pid || !posix_kill($pid, 0)) {
            $this->warn("⚠️  Process {$pid} is not running. Cleaning up PID file.");
            unlink($pidFile);
            return 0;
        }
        
        $this->info("🛑 Stopping hook service (PID: {$pid})...");
        
        // Envoyer le signal SIGTERM
        if (posix_kill($pid, SIGTERM)) {
            $this->info("✅ Signal sent to process {$pid}");
            
            // Attendre un peu puis vérifier
            sleep(2);
            
            if (posix_kill($pid, 0)) {
                $this->warn("⚠️  Process still running, sending SIGKILL...");
                posix_kill($pid, SIGKILL);
            }
            
            // Nettoyer le fichier PID
            unlink($pidFile);
            $this->info("✅ Hook service stopped and PID file cleaned");
            
        } else {
            $this->error("❌ Failed to stop process {$pid}");
            return 1;
        }
        
        return 0;
    }
}
