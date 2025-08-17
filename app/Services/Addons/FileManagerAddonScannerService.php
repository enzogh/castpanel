<?php

namespace App\Services\Addons;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FileManagerAddonScannerService
{
    /**
     * Vérifie si le serveur est un serveur Garry's Mod
     */
    public function isGmodServer(Server $server): bool
    {
        Log::info("Vérification du type de serveur pour {$server->name}", [
            'server_id' => $server->id,
            'has_egg' => $server->egg ? 'yes' : 'no',
            'egg_name' => $server->egg ? $server->egg->name : 'null',
            'egg_id' => $server->egg ? $server->egg->id : 'null'
        ]);
        
        if (!$server->egg) {
            Log::warning("Le serveur {$server->name} n'a pas d'egg associé");
            return false;
        }
        
        $isGmod = Str::contains(strtolower($server->egg->name), 'garry') || 
                  Str::contains(strtolower($server->egg->name), 'gmod');
        
        Log::info("Résultat de la vérification pour {$server->name}: " . ($isGmod ? 'Garry\'s Mod' : 'Autre type'));
        
        return $isGmod;
    }

    /**
     * Scanne les addons Garry's Mod en utilisant FileManager
     */
    public function scanAddons(Server $server): array
    {
        if (!$this->isGmodServer($server)) {
            Log::warning("Tentative de scan d'addons sur un serveur non-Garry's Mod: {$server->name}");
            return [];
        }

        Log::info("Début du scan d'addons pour le serveur {$server->name} (ID: {$server->id})");

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
                Log::info("Scan du chemin: {$path}");
                
                try {
                    // Utiliser FileManager pour lister le contenu du répertoire
                    $files = $this->listDirectoryContents($server, $path);
                    
                    if (!empty($files)) {
                        Log::info("Fichiers trouvés dans {$path}: " . count($files));
                        
                        foreach ($files as $file) {
                            $addon = $this->analyzeAddonFile($server, $file, $path);
                            if ($addon) {
                                $detectedAddons[] = $addon;
                                Log::info("Addon détecté: {$addon['name']} dans {$path}");
                            }
                        }
                    } else {
                        Log::info("Aucun fichier trouvé dans {$path}");
                    }
                    
                } catch (\Exception $e) {
                    Log::warning("Erreur lors du scan du chemin {$path}: " . $e->getMessage());
                    continue;
                }
            }

            Log::info("Scan terminé pour {$server->name}. Addons détectés: " . count($detectedAddons));
            return $detectedAddons;

        } catch (\Exception $e) {
            Log::error("Erreur lors du scan des addons pour {$server->name}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Liste le contenu d'un répertoire via FileManager
     */
    protected function listDirectoryContents(Server $server, string $path): array
    {
        try {
            // Utiliser le service FileManager pour lister le contenu
            $fileManager = app(\App\Services\Files\FileManagerService::class);
            
            // Lister les fichiers et dossiers dans le répertoire
            $contents = $fileManager->listDirectory($server, $path);
            
            if (is_array($contents)) {
                return array_filter($contents, function($item) {
                    // Filtrer pour ne garder que les dossiers d'addons
                    return isset($item['type']) && $item['type'] === 'directory';
                });
            }
            
            return [];
            
        } catch (\Exception $e) {
            Log::warning("Impossible de lister le contenu de {$path}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Analyse un fichier/dossier pour détecter s'il s'agit d'un addon
     */
    protected function analyzeAddonFile(Server $server, array $fileInfo, string $basePath): ?array
    {
        try {
            $fileName = $fileInfo['name'] ?? '';
            $filePath = $fileInfo['path'] ?? '';
            
            // Vérifier si c'est un dossier d'addon valide
            if (!$this->isValidAddonDirectory($fileName, $filePath)) {
                return null;
            }

            // Lire le fichier addon.json s'il existe
            $addonJson = $this->readAddonJson($server, $filePath);
            
            if ($addonJson) {
                return [
                    'name' => $addonJson['title'] ?? $fileName,
                    'description' => $addonJson['description'] ?? 'Addon Garry\'s Mod',
                    'version' => $addonJson['version'] ?? '1.0.0',
                    'author' => $addonJson['author'] ?? 'Unknown',
                    'file_path' => $filePath,
                    'type' => 'addon',
                    'metadata' => $addonJson
                ];
            }

            // Si pas d'addon.json, essayer de lire le fichier lua principal
            $luaInfo = $this->readLuaFile($server, $filePath);
            
            if ($luaInfo) {
                return [
                    'name' => $luaInfo['name'] ?? $fileName,
                    'description' => $luaInfo['description'] ?? 'Addon Lua Garry\'s Mod',
                    'version' => $luaInfo['version'] ?? '1.0.0',
                    'author' => $luaInfo['author'] ?? 'Unknown',
                    'file_path' => $filePath,
                    'type' => 'lua_addon',
                    'metadata' => $luaInfo
                ];
            }

            // Addon basique si aucune métadonnée n'est trouvée
            return [
                'name' => $fileName,
                'description' => 'Addon Garry\'s Mod détecté',
                'version' => '1.0.0',
                'author' => 'Unknown',
                'file_path' => $filePath,
                'type' => 'basic_addon',
                'metadata' => []
            ];

        } catch (\Exception $e) {
            Log::warning("Erreur lors de l'analyse de {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si un dossier est un addon valide
     */
    protected function isValidAddonDirectory(string $name, string $path): bool
    {
        // Exclure les dossiers système
        $excludedDirs = ['workshop', 'downloads', 'cache', 'temp', 'logs'];
        
        if (in_array(strtolower($name), $excludedDirs)) {
            return false;
        }

        // Vérifier que le nom ne contient pas de caractères suspects
        if (preg_match('/[<>:"|?*]/', $name)) {
            return false;
        }

        return true;
    }

    /**
     * Lit le fichier addon.json d'un addon
     */
    protected function readAddonJson(Server $server, string $addonPath): ?array
    {
        try {
            $fileManager = app(\App\Services\Files\FileManagerService::class);
            
            // Essayer de lire addon.json
            $jsonPath = $addonPath . '/addon.json';
            $content = $fileManager->readFile($server, $jsonPath);
            
            if ($content) {
                $data = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }

            // Essayer de lire workshop.txt
            $workshopPath = $addonPath . '/workshop.txt';
            $content = $fileManager->readFile($server, $workshopPath);
            
            if ($content) {
                return $this->parseWorkshopTxt($content);
            }

            return null;

        } catch (\Exception $e) {
            Log::debug("Impossible de lire addon.json pour {$addonPath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Lit et analyse un fichier Lua principal
     */
    protected function readLuaFile(Server $server, string $addonPath): ?array
    {
        try {
            $fileManager = app(\App\Services\Files\FileManagerService::class);
            
            // Chercher des fichiers .lua dans le dossier
            $files = $fileManager->listDirectory($server, $addonPath);
            
            foreach ($files as $file) {
                if (isset($file['type']) && $file['type'] === 'file' && 
                    Str::endsWith(strtolower($file['name']), '.lua')) {
                    
                    $content = $fileManager->readFile($server, $file['path']);
                    if ($content) {
                        $info = $this->parseLuaFile($content);
                        if ($info) {
                            return $info;
                        }
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::debug("Impossible de lire les fichiers Lua pour {$addonPath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse le contenu d'un fichier workshop.txt
     */
    protected function parseWorkshopTxt(string $content): array
    {
        $info = [
            'title' => 'Unknown Addon',
            'description' => 'Addon Garry\'s Mod',
            'version' => '1.0.0',
            'author' => 'Unknown'
        ];

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if (Str::startsWith($line, 'title=')) {
                $info['title'] = trim(substr($line, 6));
            } elseif (Str::startsWith($line, 'description=')) {
                $info['description'] = trim(substr($line, 12));
            } elseif (Str::startsWith($line, 'version=')) {
                $info['version'] = trim(substr($line, 8));
            } elseif (Str::startsWith($line, 'author=')) {
                $info['author'] = trim(substr($line, 7));
            }
        }

        return $info;
    }

    /**
     * Parse le contenu d'un fichier Lua pour extraire les métadonnées
     */
    protected function parseLuaFile(string $content): ?array
    {
        $info = [
            'name' => 'Unknown Addon',
            'description' => 'Addon Lua Garry\'s Mod',
            'version' => '1.0.0',
            'author' => 'Unknown'
        ];

        // Chercher des commentaires avec des métadonnées
        if (preg_match('/--\s*Name:\s*(.+)/i', $content, $matches)) {
            $info['name'] = trim($matches[1]);
        }
        
        if (preg_match('/--\s*Description:\s*(.+)/i', $content, $matches)) {
            $info['description'] = trim($matches[1]);
        }
        
        if (preg_match('/--\s*Version:\s*(.+)/i', $content, $matches)) {
            $info['version'] = trim($matches[1]);
        }
        
        if (preg_match('/--\s*Author:\s*(.+)/i', $content, $matches)) {
            $info['author'] = trim($matches[1]);
        }

        // Si aucune métadonnée n'est trouvée, retourner null
        if ($info['name'] === 'Unknown Addon' && $info['description'] === 'Addon Lua Garry\'s Mod') {
            return null;
        }

        return $info;
    }

    /**
     * Synchronise les addons détectés avec la base de données
     */
    public function syncAddons(Server $server, array $detectedAddons): array
    {
        Log::info("Début de la synchronisation des addons pour {$server->name}");

        try {
            // Récupérer les addons existants en base
            $existingAddons = \App\Models\ServerAddon::where('server_id', $server->id)->get();
            $existingPaths = $existingAddons->pluck('file_path')->toArray();
            $detectedPaths = array_column($detectedAddons, 'file_path');

            $added = 0;
            $updated = 0;
            $removed = 0;

            // Supprimer les addons qui ne sont plus présents
            foreach ($existingAddons as $existingAddon) {
                if (!in_array($existingAddon->file_path, $detectedPaths)) {
                    $existingAddon->delete();
                    $removed++;
                    Log::info("Addon supprimé: {$existingAddon->name}");
                }
            }

            // Ajouter ou mettre à jour les addons détectés
            foreach ($detectedAddons as $addonData) {
                $existingAddon = \App\Models\ServerAddon::where('server_id', $server->id)
                    ->where('file_path', $addonData['file_path'])
                    ->first();

                if ($existingAddon) {
                    // Mise à jour
                    $existingAddon->update([
                        'name' => $addonData['name'],
                        'description' => $addonData['description'],
                        'version' => $addonData['version'],
                        'author' => $addonData['author'],
                        'last_update' => now(),
                    ]);
                    $updated++;
                    Log::info("Addon mis à jour: {$addonData['name']}");
                } else {
                    // Ajout
                    \App\Models\ServerAddon::create([
                        'server_id' => $server->id,
                        'name' => $addonData['name'],
                        'description' => $addonData['description'],
                        'version' => $addonData['version'],
                        'author' => $addonData['author'],
                        'file_path' => $addonData['file_path'],
                        'status' => 'installed',
                        'installation_date' => now(),
                    ]);
                    $added++;
                    Log::info("Addon ajouté: {$addonData['name']}");
                }
            }

            Log::info("Synchronisation terminée pour {$server->name}: {$added} ajoutés, {$updated} mis à jour, {$removed} supprimés");

            return [
                'added' => $added,
                'updated' => $updated,
                'removed' => $removed,
                'total' => count($detectedAddons)
            ];

        } catch (\Exception $e) {
            Log::error("Erreur lors de la synchronisation des addons pour {$server->name}: " . $e->getMessage());
            throw $e;
        }
    }
}
