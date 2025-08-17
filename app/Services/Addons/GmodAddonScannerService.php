<?php

namespace App\Services\Addons;

use App\Models\Server;
use App\Models\Addon;
use App\Models\ServerAddon;
use App\Repositories\Daemon\DaemonFileRepository;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GmodAddonScannerService
{
    protected DaemonFileRepository $fileRepository;

    public function __construct(DaemonFileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Vérifie si le serveur est compatible avec Garry's Mod
     */
    public function isGmodServer(Server $server): bool
    {
        return $server->egg && 
               (Str::contains(strtolower($server->egg->name), 'garry') || 
                Str::contains(strtolower($server->egg->name), 'gmod'));
    }

    /**
     * Scanne le répertoire garrysmod/addons pour détecter les addons installés
     */
    public function scanInstalledAddons(Server $server): array
    {
        if (!$this->isGmodServer($server)) {
            throw new Exception('Ce serveur n\'est pas un serveur Garry\'s Mod');
        }

        try {
            $this->fileRepository->setServer($server);
            
            // Vérifier si le répertoire garrysmod/addons existe
            $addonsPath = 'garrysmod/addons';
            
            if (!$this->directoryExists($addonsPath)) {
                Log::info("Le répertoire $addonsPath n'existe pas sur le serveur {$server->name}");
                return [];
            }

            // Lister les dossiers dans garrysmod/addons
            $addonFolders = $this->getAddonFolders($addonsPath);
            $detectedAddons = [];

            foreach ($addonFolders as $folder) {
                $addonInfo = $this->analyzeAddonFolder($server, $addonsPath . '/' . $folder);
                if ($addonInfo) {
                    $detectedAddons[] = $addonInfo;
                }
            }

            return $detectedAddons;

        } catch (Exception $e) {
            Log::error("Erreur lors du scan des addons pour le serveur {$server->name}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Synchronise les addons détectés avec la base de données
     */
    public function syncDetectedAddons(Server $server, array $detectedAddons): array
    {
        $syncResults = [
            'added' => 0,
            'updated' => 0,
            'removed' => 0,
            'errors' => []
        ];

        try {
            // Marquer tous les addons actuels comme potentiellement supprimés
            $currentAddons = ServerAddon::where('server_id', $server->id)->get();
            $detectedPaths = collect($detectedAddons)->pluck('file_path')->toArray();

            // Supprimer les addons qui ne sont plus présents
            foreach ($currentAddons as $currentAddon) {
                if (!in_array($currentAddon->file_path, $detectedPaths)) {
                    $currentAddon->delete();
                    $syncResults['removed']++;
                }
            }

            // Ajouter ou mettre à jour les addons détectés
            foreach ($detectedAddons as $addonData) {
                $existing = ServerAddon::where('server_id', $server->id)
                    ->where('file_path', $addonData['file_path'])
                    ->first();

                if ($existing) {
                    // Mettre à jour l'addon existant
                    $existing->update([
                        'name' => $addonData['name'],
                        'description' => $addonData['description'],
                        'version' => $addonData['version'],
                        'author' => $addonData['author'],
                        'last_update' => now(),
                    ]);
                    $syncResults['updated']++;
                } else {
                    // Créer un nouvel addon
                    ServerAddon::create([
                        'server_id' => $server->id,
                        'addon_id' => null, // Pas lié à un addon du catalogue
                        'name' => $addonData['name'],
                        'description' => $addonData['description'],
                        'version' => $addonData['version'],
                        'author' => $addonData['author'],
                        'file_path' => $addonData['file_path'],
                        'status' => ServerAddon::STATUS_INSTALLED,
                        'installation_date' => now(),
                        'last_update' => now(),
                    ]);
                    $syncResults['added']++;
                }
            }

        } catch (Exception $e) {
            $syncResults['errors'][] = $e->getMessage();
            Log::error("Erreur lors de la synchronisation: " . $e->getMessage());
        }

        return $syncResults;
    }

    /**
     * Vérifie si un répertoire existe
     */
    protected function directoryExists(string $path): bool
    {
        try {
            $contents = $this->fileRepository->getDirectory($path);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Récupère la liste des dossiers d'addons
     */
    protected function getAddonFolders(string $addonsPath): array
    {
        try {
            $contents = $this->fileRepository->getDirectory($addonsPath);
            $folders = [];

            foreach ($contents as $item) {
                if ($item['is_file'] === false && $item['name'] !== '..' && $item['name'] !== '.') {
                    $folders[] = $item['name'];
                }
            }

            return $folders;
        } catch (Exception $e) {
            Log::error("Impossible de lister les dossiers d'addons: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Analyse un dossier d'addon pour extraire les informations
     */
    protected function analyzeAddonFolder(Server $server, string $addonPath): ?array
    {
        try {
            $addonInfo = [
                'name' => basename($addonPath),
                'description' => '',
                'version' => '1.0.0',
                'author' => 'Inconnu',
                'file_path' => $addonPath,
            ];

            // Chercher le fichier addon.txt pour les informations
            $addonTxtPath = $addonPath . '/addon.txt';
            if ($this->fileExists($addonTxtPath)) {
                $addonTxtContent = $this->getFileContent($addonTxtPath);
                $addonInfo = array_merge($addonInfo, $this->parseAddonTxt($addonTxtContent));
            }

            // Chercher le fichier addon.json pour les workshops addons
            $addonJsonPath = $addonPath . '/addon.json';
            if ($this->fileExists($addonJsonPath)) {
                $addonJsonContent = $this->getFileContent($addonJsonPath);
                $jsonInfo = $this->parseAddonJson($addonJsonContent);
                if ($jsonInfo) {
                    $addonInfo = array_merge($addonInfo, $jsonInfo);
                }
            }

            // Si pas d'informations trouvées, utiliser le nom du dossier
            if (empty($addonInfo['name']) || $addonInfo['name'] === basename($addonPath)) {
                $addonInfo['name'] = $this->formatAddonName(basename($addonPath));
            }

            return $addonInfo;

        } catch (Exception $e) {
            Log::warning("Impossible d'analyser l'addon dans $addonPath: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si un fichier existe
     */
    protected function fileExists(string $filePath): bool
    {
        try {
            $this->fileRepository->getContent($filePath);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Récupère le contenu d'un fichier
     */
    protected function getFileContent(string $filePath): string
    {
        try {
            return $this->fileRepository->getContent($filePath);
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Parse le fichier addon.txt de Garry's Mod
     */
    protected function parseAddonTxt(string $content): array
    {
        $info = [];
        $lines = explode("\n", $content);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '"') === false) continue;

            // Format: "key" "value"
            if (preg_match('/"([^"]+)"\s+"([^"]+)"/', $line, $matches)) {
                $key = strtolower($matches[1]);
                $value = $matches[2];

                switch ($key) {
                    case 'name':
                    case 'title':
                        $info['name'] = $value;
                        break;
                    case 'description':
                    case 'info':
                        $info['description'] = $value;
                        break;
                    case 'author':
                    case 'author_name':
                        $info['author'] = $value;
                        break;
                    case 'version':
                        $info['version'] = $value;
                        break;
                }
            }
        }

        return $info;
    }

    /**
     * Parse le fichier addon.json
     */
    protected function parseAddonJson(string $content): ?array
    {
        try {
            $data = json_decode($content, true);
            if (!$data) return null;

            $info = [];
            if (isset($data['title'])) $info['name'] = $data['title'];
            if (isset($data['description'])) $info['description'] = $data['description'];
            if (isset($data['author'])) $info['author'] = $data['author'];
            if (isset($data['version'])) $info['version'] = $data['version'];

            return $info;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Formate le nom d'un addon basé sur le nom du dossier
     */
    protected function formatAddonName(string $folderName): string
    {
        // Supprimer les chiffres en début (workshop IDs)
        $name = preg_replace('/^\d+[_-]?/', '', $folderName);
        
        // Remplacer les underscores et tirets par des espaces
        $name = str_replace(['_', '-'], ' ', $name);
        
        // Mettre en forme (première lettre en majuscule)
        $name = ucwords(strtolower($name));
        
        return $name ?: $folderName;
    }
}