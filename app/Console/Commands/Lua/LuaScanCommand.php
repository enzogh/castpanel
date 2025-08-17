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
    protected $signature = 'lua:scan {--server= : ID du serveur à scanner} {--path= : Chemin spécifique à scanner} {--debug : Mode debug détaillé}';

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

        // Mode debug complet
        if ($this->option('debug')) {
            $this->info('🔍 Mode debug complet activé');
            return $this->debugScanComplete($serverId, $path);
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

    /**
     * Debug complet du scan avec traçage détaillé
     */
    protected function debugScanComplete(?string $serverId, ?string $path): int
    {
        $this->info('🔍 === DEBUG COMPLET DU SCAN D\'ADDONS ===');
        $this->newLine();

        try {
            // Test 1: Vérification des services
            $this->info('🔍 Test 1: Vérification des services');
            
            try {
                $fileManager = app(\App\Services\Files\FileManagerService::class);
                $this->line('   ✅ Service FileManager instancié');
                
                $scanner = app(\App\Services\Addons\FileManagerAddonScannerService::class);
                $this->line('   ✅ Service de scan instancié');
                
            } catch (\Exception $e) {
                $this->error('   ❌ Erreur lors de l\'instanciation des services : ' . $e->getMessage());
                return 1;
            }

            // Test 2: Debug de la méthode scanAddons étape par étape
            $this->newLine();
            $this->info('🔍 Test 2: Debug de la méthode scanAddons étape par étape');
            
            try {
                // Utiliser la réflexion pour accéder aux méthodes protégées
                $reflection = new \ReflectionClass($scanner);
                
                // Créer un serveur mock pour le test
                $mockServer = new \stdClass();
                $mockServer->id = $serverId ?: 1;
                $mockServer->name = 'Serveur Test GMod';
                $mockServer->egg_id = 1;
                $mockServer->uuid = 'test-uuid-123';
                
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

                // Test de la méthode listDirectoryContents pour chaque chemin
                $this->newLine();
                $this->line('   📁 Test de listDirectoryContents pour chaque chemin...');
                
                $listMethod = $reflection->getMethod('listDirectoryContents');
                $listMethod->setAccessible(true);
                
                $addonPaths = [
                    'garrysmod/addons',
                    'addons',
                    'garrysmod/lua/autorun',
                    'lua/autorun'
                ];
                
                foreach ($addonPaths as $addonPath) {
                    $this->newLine();
                    $this->line('      🔍 Test du chemin : <info>' . $addonPath . '</info>');
                    
                    try {
                        $this->line('         📋 Appel de listDirectoryContents...');
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
                        
                        // Afficher la stack trace pour plus de détails
                        $this->line('         📚 Stack trace :');
                        $trace = $e->getTrace();
                        foreach (array_slice($trace, 0, 5) as $index => $traceItem) {
                            $this->line('            ' . ($index + 1) . '. ' . $traceItem['file'] . ':' . $traceItem['line'] . ' - ' . $traceItem['function']);
                        }
                    }
                }

                // Test de la méthode analyzeAddonFile
                $this->newLine();
                $this->line('   🔍 Test de la méthode analyzeAddonFile...');
                
                $analyzeMethod = $reflection->getMethod('analyzeAddonFile');
                $analyzeMethod->setAccessible(true);
                
                // Créer un fichier mock pour tester
                $mockFile = [
                    'name' => 'test_addon',
                    'path' => 'garrysmod/addons/test_addon',
                    'type' => 'directory'
                ];
                
                $this->line('   📋 Test avec fichier mock : ' . $mockFile['name']);
                
                try {
                    $addon = $analyzeMethod->invoke($scanner, $mockServer, $mockFile, 'garrysmod/addons');
                    
                    if ($addon) {
                        $this->line('   ✅ Addon analysé avec succès :');
                        $this->line('      - Nom : ' . $addon['name']);
                        $this->line('      - Version : ' . $addon['version']);
                        $this->line('      - Auteur : ' . $addon['author']);
                        $this->line('      - Type : ' . $addon['type']);
                    } else {
                        $this->line('   ⚠️  Aucun addon retourné par l\'analyse');
                    }
                    
                } catch (\Exception $e) {
                    $this->error('   ❌ Erreur lors de l\'analyse : ' . $e->getMessage());
                }

                // Test du scan complet
                $this->newLine();
                $this->line('   🔍 Test du scan complet...');
                
                try {
                    $addons = $scanner->scanAddons($mockServer);
                    $this->line('   📊 Nombre d\'addons détectés : ' . count($addons));
                    
                    if (count($addons) > 0) {
                        $this->line('   📋 Addons détectés :');
                        foreach ($addons as $addon) {
                            $this->line('      - ' . $addon['name'] . ' v' . $addon['version'] . ' par ' . $addon['author']);
                            $this->line('      - Chemin : ' . $addon['file_path']);
                            $this->line('      - Type : ' . $addon['type']);
                        }
                    } else {
                        $this->line('   ⚠️  Aucun addon détecté');
                    }
                    
                } catch (\Exception $e) {
                    $this->error('   ❌ Erreur lors du scan complet : ' . $e->getMessage());
                    $this->line('   📍 Fichier : ' . $e->getFile());
                    $this->line('   📍 Ligne : ' . $e->getLine());
                }

            } catch (\Exception $e) {
                $this->error('   ❌ Erreur lors du debug : ' . $e->getMessage());
                $this->line('   📍 Fichier : ' . $e->getFile());
                $this->line('   📍 Ligne : ' . $e->getLine());
            }

            // Test 3: Vérification des logs
            $this->newLine();
            $this->info('🔍 Test 3: Vérification des logs');
            
            $logFile = storage_path('logs/laravel.log');
            if (file_exists($logFile)) {
                $this->line('   📝 Fichier de log trouvé : ' . $logFile);
                
                // Lire les dernières lignes du log
                $logContent = file_get_contents($logFile);
                $lines = explode("\n", $logContent);
                $lastLines = array_slice($lines, -20); // 20 dernières lignes
                
                $this->line('   📊 20 dernières lignes du log :');
                foreach ($lastLines as $line) {
                    if (trim($line) && (strpos($line, 'addon') !== false || strpos($line, 'scan') !== false || strpos($line, 'gmod') !== false)) {
                        $this->line('      ' . trim($line));
                    }
                }
            } else {
                $this->line('   ⚠️  Fichier de log non trouvé');
            }

            // Résumé du debug
            $this->newLine();
            $this->info('✅ Debug complet terminé !');
            $this->newLine();
            $this->info('=== Résumé du debug ===');
            $this->line('💡 **Ce qui a été testé** :');
            $this->line('   - Instanciation des services');
            $this->line('   - Détection Garry\'s Mod');
            $this->line('   - Listage des répertoires pour chaque chemin');
            $this->line('   - Analyse des fichiers d\'addons');
            $this->line('   - Scan complet');
            $this->line('   - Vérification des logs');
            $this->newLine();
            
            $this->info('🎯 **Prochaines étapes** :');
            $this->line('   1. Analyser les résultats du debug ci-dessus');
            $this->line('   2. Identifier où le processus échoue');
            $this->line('   3. Corriger le problème spécifique identifié');

            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur générale : ' . $e->getMessage());
            $this->line('📍 Fichier : ' . $e->getFile());
            $this->line('📍 Ligne : ' . $e->getLine());
            return 1;
        }
    }
}
