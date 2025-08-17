<?php

namespace App\Console\Commands\Lua;

use Illuminate\Console\Command;

class LuaListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lister toutes les commandes Lua disponibles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📋 Commandes Lua disponibles :');
        $this->newLine();
        
        $commands = [
            [
                'command' => 'lua:monitor',
                'description' => 'Surveiller l\'activité Lua sur les serveurs Garry\'s Mod',
                'usage' => 'php artisan lua:monitor [--server=ID]'
            ],
            [
                'command' => 'lua:scan',
                'description' => 'Scanner les addons et scripts Lua sur les serveurs (--server=test pour mode test, --debug pour debug complet)',
                'usage' => 'php artisan lua:scan [--server=ID|test] [--path=chemin] [--debug]'
            ],
            [
                'command' => 'lua:scan-webftp',
                'description' => 'Scanner les addons via WebFTP/API des fichiers (--server=test pour mode test, --debug pour debug complet)',
                'usage' => 'php artisan lua:scan-webftp [--server=ID|test] [--debug]'
            ],
            [
                'command' => 'lua:list',
                'description' => 'Lister toutes les commandes Lua disponibles',
                'usage' => 'php artisan lua:list'
            ]
        ];
        
        foreach ($commands as $cmd) {
            $this->line("🔹 <info>{$cmd['command']}</info>");
            $this->line("   {$cmd['description']}");
            $this->line("   Usage : <comment>{$cmd['usage']}</comment>");
            $this->newLine();
        }
        
        $this->info('💡 Exemples d\'utilisation :');
        $this->line('   php artisan lua:monitor --server=1');
        $this->line('   php artisan lua:scan --server=1');
        $this->line('   php artisan lua:scan --server=test  # Mode test sans base de données');
        $this->line('   php artisan lua:scan --debug         # Debug complet détaillé');
        $this->line('   php artisan lua:scan --path=garrysmod/addons');
        $this->newLine();
        $this->line('   # Nouvelles commandes WebFTP :');
        $this->line('   php artisan lua:scan-webftp --server=1');
        $this->line('   php artisan lua:scan-webftp --server=test  # Mode test WebFTP');
        $this->line('   php artisan lua:scan-webftp --debug         # Debug complet WebFTP');
        
        return 0;
    }
}
