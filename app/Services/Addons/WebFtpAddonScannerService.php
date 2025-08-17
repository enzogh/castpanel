<?php

namespace App\Services\Addons;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class WebFtpAddonScannerService
{
    /**
     * Vérifie si un serveur est un serveur Garry's Mod
     */
    public function isGmodServer($server): bool
    {
        if (!$server->egg) {
            Log::warning("Le serveur {$server->name} n'a pas d'egg associé");
            return false;
        }
        
        $isGmod = Str::contains(strtolower($server->egg->name), 'garry') || 
                  Str::contains(strtolower($server->egg->name), 'gmod');
        
        Log::info("Vérification du type de serveur pour {$server->name}: " . ($isGmod ? 'Garry\'s Mod' : 'Autre type'));
        
        return $isGmod;
    }

    /**
     * Scanne les addons installés sur un serveur en utilisant l'API des fichiers
     */
    public function scanAddons($server): array
    {
        if (!$this->isGmodServer($server)) {
            Log::warning("Tentative de scan d'addons sur un serveur non-Garry's Mod: {$server->name}");
            return [];
        }

        Log::info("Début du scan d'addons via WebFTP pour le serveur {$server->name} (ID: {$server->id})");

        try {
            // Chemins typiques des addons Garry's Mod
            $addonPaths = [
                'garrysmod/addons',
                'addons',
                'garrysmod/lua/autorun',
                'lua/autorun'
            ];

            $detectedAddons = [];

            foreach ($addonPaths as $path) {
                Log::info("Scan du chemin via WebFTP: {$path}");
                
                try {
                    // Utiliser l'API des fichiers pour lister le contenu du répertoire
                    $files = $this->listDirectoryViaWebFtp($server, $path);
                    
                    if (!empty($files)) {
                        Log::info("Fichiers trouvés dans {$path} via WebFTP: " . count($files));
                        
                        foreach ($files as $file) {
                            $addon = $this->analyzeAddonFile($server, $file, $path);
                            if ($addon) {
                                $detectedAddons[] = $addon;
                                Log::info("Addon détecté via WebFTP: {$addon['name']} dans {$path}");
                            }
                        }
                    } else {
                        Log::info("Aucun fichier trouvé dans {$path} via WebFTP");
                    }
                    
                } catch (\Exception $e) {
                    Log::warning("Erreur lors du scan du chemin {$path} via WebFTP: " . $e->getMessage());
                    continue;
                }
            }

            Log::info("Scan via WebFTP terminé pour {$server->name}. Addons détectés: " . count($detectedAddons));
            return $detectedAddons;

        } catch (\Exception $e) {
            Log::error("Erreur lors du scan des addons via WebFTP pour {$server->name}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Liste le contenu d'un répertoire via l'API WebFTP
     */
    protected function listDirectoryViaWebFtp($server, string $path): array
    {
        try {
            Log::info("Listage du répertoire {$path} via WebFTP sur le serveur {$server->name}");
            
            // Construire l'URL de l'API des fichiers (pas de nom de route, on utilise l'URL directe)
            $apiUrl = url("/api/client/servers/{$server->uuid}/files/list") . '?directory=' . urlencode($path);
            
            Log::info("URL de l'API WebFTP: {$apiUrl}");
            
            // Faire la requête HTTP à l'API des fichiers
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getApiToken($server),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ])->get($apiUrl);
            
            if ($response->successful()) {
                $contents = $response->json();
                Log::info("Contenu récupéré via WebFTP pour {$path}: " . count($contents) . " éléments");
                return is_array($contents) ? $contents : [];
            } else {
                Log::warning("Erreur HTTP lors du listage via WebFTP: " . $response->status() . " - " . $response->body());
                return [];
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur lors du listage du répertoire {$path} via WebFTP: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lit le contenu d'un fichier via l'API WebFTP
     */
    protected function readFileViaWebFtp($server, string $filePath): ?string
    {
        try {
            Log::info("Lecture du fichier {$filePath} via WebFTP sur le serveur {$server->name}");
            
            // Construire l'URL de l'API des fichiers (pas de nom de route, on utilise l'URL directe)
            $apiUrl = url("/api/client/servers/{$server->uuid}/files/contents") . '?file=' . urlencode($filePath);
            
            // Faire la requête HTTP à l'API des fichiers
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->getApiToken($server),
                'Accept' => 'text/plain',
            ])->get($apiUrl);
            
            if ($response->successful()) {
                $content = $response->body();
                Log::info("Fichier {$filePath} lu via WebFTP, taille: " . strlen($content) . " octets");
                return $content;
            } else {
                Log::warning("Erreur HTTP lors de la lecture via WebFTP: " . $response->status() . " - " . $response->body());
                return null;
            }
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la lecture du fichier {$filePath} via WebFTP: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Analyse un fichier/dossier pour détecter s'il s'agit d'un addon
     */
    protected function analyzeAddonFile($server, array $fileInfo, string $basePath): ?array
    {
        try {
            if (!isset($fileInfo['name']) || !isset($fileInfo['type'])) {
                return null;
            }

            $fileName = $fileInfo['name'];
            $fileType = $fileInfo['type'];
            $filePath = $fileInfo['path'] ?? $basePath . '/' . $fileName;

            // Ignorer les fichiers cachés et les fichiers temporaires
            if (Str::startsWith($fileName, '.') || Str::contains($fileName, '~')) {
                return null;
            }

            // Vérifier si c'est un répertoire (potentiel addon)
            if ($fileType === 'directory') {
                Log::info("Analyse du répertoire potentiel addon: {$fileName}");
                
                // Essayer de lire addon.json
                $addonJson = $this->readFileViaWebFtp($server, $filePath . '/addon.json');
                if ($addonJson) {
                    return $this->parseAddonJson($addonJson, $filePath, $fileName);
                }

                // Essayer de lire workshop.txt
                $workshopTxt = $this->readFileViaWebFtp($server, $filePath . '/workshop.txt');
                if ($workshopTxt) {
                    return $this->parseWorkshopTxt($workshopTxt, $filePath, $fileName);
                }

                // Essayer de lire un fichier Lua principal
                $luaFile = $this->readFileViaWebFtp($server, $filePath . '/init.lua');
                if ($luaFile) {
                    return $this->parseLuaFile($luaFile, $filePath, $fileName);
                }

                // Vérifier s'il y a des fichiers .lua dans le répertoire
                $luaFiles = $this->listDirectoryViaWebFtp($server, $filePath);
                foreach ($luaFiles as $luaFile) {
                    if (isset($luaFile['name']) && Str::endsWith($luaFile['name'], '.lua')) {
                        $luaContent = $this->readFileViaWebFtp($server, $filePath . '/' . $luaFile['name']);
                        if ($luaContent) {
                            return $this->parseLuaFile($luaContent, $filePath, $fileName);
                        }
                    }
                }

                // Addon basique détecté par la présence du répertoire
                Log::info("Addon basique détecté par la présence du répertoire: {$fileName}");
                return [
                    'name' => $fileName,
                    'version' => '1.0.0',
                    'author' => 'Unknown',
                    'description' => 'Addon détecté par la présence du répertoire',
                    'file_path' => $filePath,
                    'type' => 'basic_addon'
                ];
            }

            // Vérifier si c'est un fichier Lua dans autorun
            if ($fileType === 'file' && Str::endsWith($fileName, '.lua')) {
                Log::info("Analyse du fichier Lua potentiel addon: {$fileName}");
                
                $luaContent = $this->readFileViaWebFtp($server, $filePath);
                if ($luaContent) {
                    return $this->parseLuaFile($luaContent, $filePath, $fileName);
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'analyse du fichier {$fileName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse le contenu d'un fichier addon.json
     */
    protected function parseAddonJson(string $content, string $filePath, string $fileName): ?array
    {
        try {
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning("Erreur JSON dans addon.json pour {$fileName}: " . json_last_error_msg());
                return null;
            }

            return [
                'name' => $data['title'] ?? $data['name'] ?? $fileName,
                'version' => $data['version'] ?? '1.0.0',
                'author' => $data['author'] ?? $data['author_name'] ?? 'Unknown',
                'description' => $data['description'] ?? 'Addon Garry\'s Mod',
                'file_path' => $filePath,
                'type' => 'addon'
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors du parsing de addon.json pour {$fileName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse le contenu d'un fichier workshop.txt
     */
    protected function parseWorkshopTxt(string $content, string $filePath, string $fileName): ?array
    {
        try {
            $lines = explode("\n", trim($content));
            $workshopId = null;
            $title = null;

            foreach ($lines as $line) {
                $line = trim($line);
                if (Str::startsWith($line, 'workshop_id=')) {
                    $workshopId = Str::after($line, 'workshop_id=');
                } elseif (Str::startsWith($line, 'title=')) {
                    $title = Str::after($line, 'title=');
                }
            }

            if ($workshopId) {
                return [
                    'name' => $title ?: $fileName,
                    'version' => '1.0.0',
                    'author' => 'Workshop',
                    'description' => "Addon Workshop ID: {$workshopId}",
                    'file_path' => $filePath,
                    'type' => 'workshop_addon',
                    'workshop_id' => $workshopId
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error("Erreur lors du parsing de workshop.txt pour {$fileName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse le contenu d'un fichier Lua
     */
    protected function parseLuaFile(string $content, string $filePath, string $fileName): ?array
    {
        try {
            // Rechercher des métadonnées dans le fichier Lua
            $name = null;
            $version = null;
            $author = null;

            // Rechercher des commentaires avec des métadonnées
            if (preg_match('/--\s*@name\s+(.+)/i', $content, $matches)) {
                $name = trim($matches[1]);
            }
            if (preg_match('/--\s*@version\s+(.+)/i', $content, $matches)) {
                $version = trim($matches[1]);
            }
            if (preg_match('/--\s*@author\s+(.+)/i', $content, $matches)) {
                $author = trim($matches[1]);
            }

            // Si aucune métadonnée trouvée, utiliser le nom du fichier
            if (!$name) {
                $name = Str::before($fileName, '.lua');
            }

            return [
                'name' => $name,
                'version' => $version ?: '1.0.0',
                'author' => $author ?: 'Unknown',
                'description' => 'Script Lua détecté',
                'file_path' => $filePath,
                'type' => 'lua_addon'
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors du parsing du fichier Lua {$fileName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchronise les addons détectés avec la base de données
     */
    public function syncAddons($server, array $detectedAddons): array
    {
        Log::info("Synchronisation des addons détectés avec la base de données pour {$server->name}");
        
        // Pour l'instant, retourner un résultat basique
        // TODO: Implémenter la vraie synchronisation avec la base de données
        return [
            'added' => count($detectedAddons),
            'updated' => 0,
            'removed' => 0,
            'errors' => []
        ];
    }

    /**
     * Récupère le token API pour le serveur
     */
    protected function getApiToken($server): string
    {
        // TODO: Implémenter la récupération du vrai token API
        // Pour l'instant, retourner un token factice
        return 'fake-token-for-testing';
    }

    /**
     * Test du scan d'addons sans accès à la base de données
     */
    public function testScanAddonsWithoutDatabase(): array
    {
        Log::info("Test du scan d'addons via WebFTP sans base de données");
        
        // Créer un serveur mock
        $mockServer = new \stdClass();
        $mockServer->id = 1;
        $mockServer->name = 'Serveur Test GMod WebFTP';
        $mockServer->egg_id = 1;
        $mockServer->uuid = 'test-uuid-webftp-123';
        
        // Créer un egg mock
        $mockEgg = new \stdClass();
        $mockEgg->id = 1;
        $mockEgg->name = 'Garry\'s Mod';
        
        $mockServer->egg = $mockEgg;
        
        // Vérifier si c'est un serveur Garry's Mod
        if (!$this->isGmodServer($mockServer)) {
            Log::warning("Le serveur mock n'est pas reconnu comme Garry's Mod");
            return [];
        }
        
        Log::info("Début du test de scan d'addons via WebFTP pour le serveur {$mockServer->name}");
        
        // Retourner des addons mockés pour le test
        return [
            [
                'name' => 'Wiremod WebFTP',
                'version' => '1.0.0',
                'author' => 'Wire Team',
                'description' => 'Système de câblage avancé pour Garry\'s Mod (détecté via WebFTP)',
                'file_path' => 'garrysmod/addons/wiremod',
                'type' => 'addon'
            ],
            [
                'name' => 'DarkRP WebFTP',
                'version' => '2.0.0',
                'author' => 'DarkRP Team',
                'description' => 'Mode de jeu Roleplay (détecté via WebFTP)',
                'file_path' => 'garrysmod/addons/darkrp',
                'type' => 'addon'
            ],
            [
                'name' => 'Test Addon WebFTP',
                'version' => '1.0.0',
                'author' => 'Test Author',
                'description' => 'Addon de test (détecté via WebFTP)',
                'file_path' => 'addons/test_addon',
                'type' => 'basic_addon'
            ],
            [
                'name' => 'Lua Script WebFTP',
                'version' => '1.0.0',
                'author' => 'Lua Author',
                'description' => 'Script Lua automatique (détecté via WebFTP)',
                'file_path' => 'garrysmod/lua/autorun/lua_script',
                'type' => 'lua_addon'
            ]
        ];
    }
}
