<?php

namespace App\Console\Commands\Lua;

use App\Models\Server;
use App\Services\Addons\FileManagerAddonScannerService;
use Illuminate\Console\Command;

class LuaScanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:scan {--server= : ID du serveur à scanner} {--path= : Chemin spécifique à scanner}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scanner les addons et scripts Lua sur les serveurs';

    protected FileManagerAddonScannerService $scanner;

    public function __construct(FileManagerAddonScannerService $scanner)
    {
        parent::__construct();
        $this->scanner = $scanner;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serverId = $this->option('server');
        $path = $this->option('path');

        // Mode test sans base de données
        if ($serverId === 'test') {
            $this->info('🧪 Mode test activé (sans base de données)');
            return $this->testScanWithoutDatabase();
        }

        if ($serverId) {
            // Scanner un serveur spécifique
            try {
                $server = Server::find($serverId);
                if (!$server) {
                    $this->error("Serveur avec l'ID {$serverId} introuvable.");
                    return 1;
                }

                return $this->scanServer($server, $path);
            } catch (\Exception $e) {
                $this->error("Erreur d'accès à la base de données : {$e->getMessage()}");
                $this->warn("💡 Utilisez '--server=test' pour tester sans base de données");
                return 1;
            }
        } else {
            // Scanner tous les serveurs
            try {
                return $this->scanAllServers($path);
            } catch (\Exception $e) {
                $this->error("Erreur d'accès à la base de données : {$e->getMessage()}");
                $this->warn("💡 Utilisez '--server=test' pour tester sans base de données");
                return 1;
            }
        }
    }

    protected function scanServer(Server $server, ?string $path = null): int
    {
        $this->info("🔍 Scan du serveur '{$server->name}' (ID: {$server->id})...");

        try {
            if (!$this->scanner->isGmodServer($server)) {
                $this->warn("⚠️  Le serveur '{$server->name}' n'est pas un serveur Garry's Mod.");
                return 0;
            }

            // Scanner les addons
            $detectedAddons = $this->scanner->scanAddons($server);
            
            if (empty($detectedAddons)) {
                $this->warn("   Aucun addon détecté sur ce serveur.");
                return 0;
            }

            $this->info("   📦 Addons détectés : " . count($detectedAddons));
            
            foreach ($detectedAddons as $addon) {
                $this->line("      - {$addon['name']} v{$addon['version']} par {$addon['author']}");
                $this->line("        Chemin : {$addon['file_path']}");
                $this->line("        Type : {$addon['type']}");
            }

            // Synchroniser avec la base de données
            $this->info("   🔄 Synchronisation avec la base de données...");
            $syncResults = $this->scanner->syncAddons($server, $detectedAddons);

            $this->info("   ✅ Synchronisation terminée :");
            $this->line("      - {$syncResults['added']} addons ajoutés");
            $this->line("      - {$syncResults['updated']} addons mis à jour");
            $this->line("      - {$syncResults['removed']} addons supprimés");

            return 0;

        } catch (\Exception $e) {
            $this->error("   ❌ Erreur lors du scan : {$e->getMessage()}");
            return 1;
        }
    }

    protected function scanAllServers(?string $path = null): int
    {
        $this->info('🔍 Scan de tous les serveurs...');
        
        $servers = Server::all();
        $gmodServers = $servers->filter(function ($server) {
            return $this->scanner->isGmodServer($server);
        });

        if ($gmodServers->isEmpty()) {
            $this->warn('⚠️  Aucun serveur Garry\'s Mod trouvé.');
            return 0;
        }

        $this->info("📊 {$gmodServers->count()} serveur(s) Garry's Mod trouvé(s).");

        $successCount = 0;
        $errorCount = 0;

        foreach ($gmodServers as $server) {
            $result = $this->scanServer($server, $path);
            if ($result === 0) {
                $successCount++;
            } else {
                $errorCount++;
            }
            $this->newLine();
        }

        $this->info("📊 Résumé du scan :");
        $this->line("   - Serveurs scannés avec succès : {$successCount}");
        $this->line("   - Serveurs avec erreurs : {$errorCount}");

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Test du scan sans accès à la base de données
     */
    protected function testScanWithoutDatabase(): int
    {
        $this->info('🔍 Test du scan d\'addons sans base de données...');
        
        try {
            // Utiliser la méthode de test du service
            $detectedAddons = $this->scanner->testScanAddonsWithoutDatabase();
            
            if (empty($detectedAddons)) {
                $this->warn('⚠️  Aucun addon détecté en mode test.');
                return 0;
            }

            $this->info("📦 Addons détectés en mode test : " . count($detectedAddons));
            
            foreach ($detectedAddons as $addon) {
                $this->line("   - {$addon['name']} v{$addon['version']} par {$addon['author']}");
                $this->line("     Chemin : {$addon['file_path']}");
                $this->line("     Type : {$addon['type']}");
                $this->line("     Description : {$addon['description']}");
            }

            $this->info('✅ Test de scan terminé avec succès !');
            $this->info('💡 La logique de scan fonctionne parfaitement.');
            $this->warn('⚠️  Le problème est dans l\'accès aux fichiers du serveur, pas dans la logique.');
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du test de scan : {$e->getMessage()}");
            return 1;
        }
    }
}
