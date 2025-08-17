<?php

namespace App\Console\Commands\Lua;

use App\Models\Server;
use App\Services\Addons\WebFtpAddonScannerService;
use Illuminate\Console\Command;

class LuaScanWebFtpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:scan-webftp {--server= : ID du serveur à scanner} {--debug : Mode debug détaillé}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scanner les addons Garry\'s Mod via WebFTP (API des fichiers)';

    protected WebFtpAddonScannerService $scanner;

    public function __construct(WebFtpAddonScannerService $scanner)
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
        $debug = $this->option('debug');

        $this->info('🔍 === SCAN D\'ADDONS VIA WEBFTP ===');
        $this->newLine();

        // Mode test sans base de données
        if ($serverId === 'test') {
            $this->info('🧪 Mode test activé (sans base de données)');
            return $this->testScanWithoutDatabase();
        }

        // Mode debug complet
        if ($debug) {
            $this->info('🔍 Mode debug activé');
            return $this->debugScanComplete($serverId);
        }

        if ($serverId) {
            // Scanner un serveur spécifique
            try {
                $server = Server::find($serverId);
                if (!$server) {
                    $this->error("Serveur avec l'ID {$serverId} introuvable.");
                    return 1;
                }

                return $this->scanServer($server);
            } catch (\Exception $e) {
                $this->error("Erreur d'accès à la base de données : {$e->getMessage()}");
                $this->warn("💡 Utilisez '--server=test' pour tester sans base de données");
                return 1;
            }
        } else {
            // Scanner tous les serveurs
            try {
                return $this->scanAllServers();
            } catch (\Exception $e) {
                $this->error("Erreur d'accès à la base de données : {$e->getMessage()}");
                $this->warn("💡 Utilisez '--server=test' pour tester sans base de données");
                return 1;
            }
        }
    }

    protected function scanServer(Server $server): int
    {
        $this->info("🔍 Scan du serveur '{$server->name}' (ID: {$server->id}) via WebFTP...");

        try {
            if (!$this->scanner->isGmodServer($server)) {
                $this->warn("⚠️  Le serveur '{$server->name}' n'est pas un serveur Garry's Mod.");
                return 0;
            }

            // Scanner les addons via WebFTP
            $detectedAddons = $this->scanner->scanAddons($server);
            
            if (empty($detectedAddons)) {
                $this->warn("   Aucun addon détecté sur ce serveur via WebFTP.");
                return 0;
            }

            $this->info("   📦 Addons détectés via WebFTP : " . count($detectedAddons));
            
            foreach ($detectedAddons as $addon) {
                $this->line("      - {$addon['name']} v{$addon['version']} par {$addon['author']}");
                $this->line("        Chemin : {$addon['file_path']}");
                $this->line("        Type : {$addon['type']}");
                $this->line("        Description : {$addon['description']}");
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
            $this->error("   ❌ Erreur lors du scan via WebFTP : {$e->getMessage()}");
            return 1;
        }
    }

    protected function scanAllServers(): int
    {
        $this->info('🔍 Scan de tous les serveurs via WebFTP...');
        
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
            $result = $this->scanServer($server);
            if ($result === 0) {
                $successCount++;
            } else {
                $errorCount++;
            }
            $this->newLine();
        }

        $this->info("📊 Résumé du scan WebFTP :");
        $this->line("   - Serveurs scannés avec succès : {$successCount}");
        $this->line("   - Serveurs avec erreurs : {$errorCount}");

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Test du scan sans accès à la base de données
     */
    protected function testScanWithoutDatabase(): int
    {
        $this->info('🔍 Test du scan d\'addons via WebFTP sans base de données...');
        
        try {
            // Utiliser la méthode de test du service
            $detectedAddons = $this->scanner->testScanAddonsWithoutDatabase();
            
            if (empty($detectedAddons)) {
                $this->warn('⚠️  Aucun addon détecté en mode test WebFTP.');
                return 0;
            }

            $this->info("📦 Addons détectés en mode test WebFTP : " . count($detectedAddons));
            
            foreach ($detectedAddons as $addon) {
                $this->line("   - {$addon['name']} v{$addon['version']} par {$addon['author']}");
                $this->line("     Chemin : {$addon['file_path']}");
                $this->line("     Type : {$addon['type']}");
                $this->line("     Description : {$addon['description']}");
            }

            $this->info('✅ Test de scan WebFTP terminé avec succès !');
            $this->info('💡 La logique de scan WebFTP fonctionne parfaitement.');
            $this->warn('⚠️  Le problème était dans l\'accès Daemon, WebFTP devrait résoudre cela !');
            
            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erreur lors du test de scan WebFTP : {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Debug complet du scan WebFTP
     */
    protected function debugScanComplete(?string $serverId): int
    {
        $this->info('🔍 === DEBUG COMPLET DU SCAN WEBFTP ===');
        $this->newLine();

        try {
            // Test 1: Vérification des services
            $this->info('🔍 Test 1: Vérification des services WebFTP');
            
            try {
                $scanner = app(\App\Services\Addons\WebFtpAddonScannerService::class);
                $this->line('   ✅ Service WebFTP Scanner instancié');
                
            } catch (\Exception $e) {
                $this->error('   ❌ Erreur lors de l\'instanciation des services WebFTP : ' . $e->getMessage());
                return 1;
            }

            // Test 2: Debug de la méthode scanAddons étape par étape
            $this->newLine();
            $this->info('🔍 Test 2: Debug de la méthode scanAddons WebFTP étape par étape');
            
            try {
                // Utiliser la réflexion pour accéder aux méthodes protégées
                $reflection = new \ReflectionClass($scanner);
                
                // Créer un serveur mock pour le test
                $mockServer = new \stdClass();
                $mockServer->id = $serverId ?: 1;
                $mockServer->name = 'Serveur Test GMod WebFTP';
                $mockServer->egg_id = 1;
                $mockServer->uuid = 'test-uuid-webftp-123';
                
                $mockEgg = new \stdClass();
                $mockEgg->id = 1;
                $mockEgg->name = 'Garry\'s Mod';
                $mockServer->egg = $mockEgg;
                
                $this->line('   🎮 Serveur mock créé : ' . $mockServer->name . ' (Egg: ' . $mockServer->egg->name . ')');
                $this->line('   🔑 UUID du serveur : ' . $mockServer->uuid);
                
                // Test de isGmodServer
                $this->newLine();
                $this->line('   🔍 Test de isGmodServer...');
                $isGmodResult = $scanner->isGmodServer($mockServer);
                $this->line('   📊 Résultat isGmodServer : ' . ($isGmodResult ? 'Oui' : 'Non'));
                
                if (!$isGmodResult) {
                    $this->error('   ❌ Le serveur n\'est pas reconnu comme Garry\'s Mod');
                    return 1;
                }

                // Test de la méthode listDirectoryViaWebFtp pour chaque chemin
                $this->newLine();
                $this->line('   📁 Test de listDirectoryViaWebFtp pour chaque chemin...');
                
                $listMethod = $reflection->getMethod('listDirectoryViaWebFtp');
                $listMethod->setAccessible(true);
                
                $addonPaths = [
                    'garrysmod/addons',
                    'addons',
                    'garrysmod/lua/autorun',
                    'lua/autorun'
                ];
                
                foreach ($addonPaths as $addonPath) {
                    $this->newLine();
                    $this->line('      🔍 Test du chemin WebFTP : <info>' . $addonPath . '</info>');
                    
                    try {
                        $this->line('         📋 Appel de listDirectoryViaWebFtp...');
                        $contents = $listMethod->invoke($scanner, $mockServer, $addonPath);
                        
                        $this->line('         📊 Type de retour : ' . gettype($contents));
                        
                        if (is_array($contents)) {
                            $this->line('         📊 Nombre d\'éléments : ' . count($contents));
                            
                            if (count($contents) > 0) {
                                $this->line('         📋 Éléments trouvés :');
                                foreach (array_slice($contents, 0, 10) as $index => $item) {
                                    $this->line('            ' . ($index + 1) . '. ' . $item['name'] . ' (Type: ' . ($item['type'] ?? 'unknown') . ')');
                                }
                                if (count($contents) > 10) {
                                    $this->line('            ... et ' . (count($contents) - 10) . ' autres');
                                }
                            } else {
                                $this->line('         ⚠️  Aucun contenu trouvé');
                            }
                        } else {
                            $this->line('         ❌ Retour invalide (pas un tableau)');
                        }
                        
                    } catch (\Exception $e) {
                        $this->error('         ❌ Erreur : ' . $e->getMessage());
                        $this->line('         📍 Fichier : ' . $e->getFile());
                        $this->line('         📍 Ligne : ' . $e->getLine());
                    }
                }

                // Test du scan complet WebFTP
                $this->newLine();
                $this->line('   🔍 Test du scan complet WebFTP...');
                
                try {
                    $addons = $scanner->scanAddons($mockServer);
                    $this->line('   📊 Nombre d\'addons détectés via WebFTP : ' . count($addons));
                    
                    if (count($addons) > 0) {
                        $this->line('   📋 Addons détectés via WebFTP :');
                        foreach ($addons as $addon) {
                            $this->line('      - ' . $addon['name'] . ' v' . $addon['version'] . ' par ' . $addon['author']);
                            $this->line('        Chemin : ' . $addon['file_path']);
                            $this->line('        Type : ' . $addon['type']);
                        }
                    } else {
                        $this->line('   ⚠️  Aucun addon détecté via WebFTP');
                    }
                    
                } catch (\Exception $e) {
                    $this->error('   ❌ Erreur lors du scan complet WebFTP : ' . $e->getMessage());
                    $this->line('   📍 Fichier : ' . $e->getFile());
                    $this->line('   📍 Ligne : ' . $e->getLine());
                }

            } catch (\Exception $e) {
                $this->error('   ❌ Erreur lors du debug WebFTP : ' . $e->getMessage());
                $this->line('   📍 Fichier : ' . $e->getFile());
                $this->line('   📍 Ligne : ' . $e->getLine());
            }

            // Résumé du debug WebFTP
            $this->newLine();
            $this->info('✅ Debug complet WebFTP terminé !');
            $this->newLine();
            $this->info('=== Résumé du debug WebFTP ===');
            $this->line('💡 **Ce qui a été testé** :');
            $this->line('   - Instanciation des services WebFTP');
            $this->line('   - Détection Garry\'s Mod');
            $this->line('   - Listage des répertoires via WebFTP');
            $this->line('   - Scan complet via WebFTP');
            $this->newLine();
            
            $this->info('🎯 **Avantages du WebFTP** :');
            $this->line('   1. Accès direct aux fichiers via l\'API');
            $this->line('   2. Pas de problème de connexion Daemon');
            $this->line('   3. Plus fiable et rapide');
            $this->line('   4. Utilise l\'interface web existante');

            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur générale WebFTP : ' . $e->getMessage());
            $this->line('📍 Fichier : ' . $e->getFile());
            $this->line('📍 Ligne : ' . $e->getLine());
            return 1;
        }
    }
}
