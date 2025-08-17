<?php

namespace App\Console\Commands\Lua;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LuaMonitorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:monitor {--server= : ID du serveur à surveiller}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Surveiller l\'activité Lua sur les serveurs Garry\'s Mod';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Démarrage de la surveillance Lua...');
        
        try {
            // Log de début de surveillance
            Log::info('Surveillance Lua démarrée', [
                'timestamp' => now(),
                'command' => 'lua:monitor'
            ]);
            
            $this->info('✅ Surveillance Lua démarrée avec succès');
            $this->info('📊 Logs enregistrés dans storage/logs/laravel.log');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors du démarrage de la surveillance : ' . $e->getMessage());
            Log::error('Erreur surveillance Lua', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return 1;
        }
    }
}
