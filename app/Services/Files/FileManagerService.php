<?php

namespace App\Services\Files;

use App\Models\Server;
use Illuminate\Support\Facades\Log;
use App\Repositories\Daemon\DaemonFileRepository;

class FileManagerService
{
    protected $daemonFileRepository;

    public function __construct(DaemonFileRepository $daemonFileRepository)
    {
        $this->daemonFileRepository = $daemonFileRepository;
    }

    /**
     * Liste le contenu d'un répertoire sur le serveur
     */
    public function listDirectory($server, string $path): array
    {
        try {
            Log::info("Listage du répertoire {$path} sur le serveur {$server->name}");
            
            // Utiliser le repository Daemon pour lister le contenu
            $contents = $this->daemonFileRepository->setServer($server)->getDirectory($path);
            
            if (is_array($contents)) {
                Log::info("Contenu récupéré pour {$path}: " . count($contents) . " éléments");
                return $contents;
            }
            
            Log::warning("Contenu invalide récupéré pour {$path}");
            return [];
            
        } catch (\Exception $e) {
            Log::error("Erreur lors du listage du répertoire {$path}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lit le contenu d'un fichier sur le serveur
     */
    public function readFile($server, string $filePath): ?string
    {
        try {
            Log::info("Lecture du fichier {$filePath} sur le serveur {$server->name}");
            
            // Utiliser le repository Daemon pour lire le fichier
            $content = $this->daemonFileRepository->setServer($server)->getContent($filePath);
            
            if ($content !== null) {
                Log::info("Fichier {$filePath} lu avec succès, taille: " . strlen($content) . " octets");
                return $content;
            }
            
            Log::warning("Contenu vide ou null pour le fichier {$filePath}");
            return null;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la lecture du fichier {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Vérifie si un fichier existe sur le serveur
     */
    public function fileExists($server, string $filePath): bool
    {
        try {
            Log::debug("Vérification de l'existence du fichier {$filePath} sur le serveur {$server->name}");
            
            // Essayer de lister le répertoire parent pour vérifier l'existence
            $directory = dirname($filePath);
            $fileName = basename($filePath);
            
            $contents = $this->listDirectory($server, $directory);
            
            foreach ($contents as $item) {
                if (isset($item['name']) && $item['name'] === $fileName) {
                    Log::debug("Fichier {$filePath} trouvé sur le serveur");
                    return true;
                }
            }
            
            Log::debug("Fichier {$filePath} non trouvé sur le serveur");
            return false;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la vérification de l'existence du fichier {$filePath}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un répertoire existe sur le serveur
     */
    public function directoryExists($server, string $directoryPath): bool
    {
        try {
            Log::debug("Vérification de l'existence du répertoire {$directoryPath} sur le serveur {$server->name}");
            
            // Essayer de lister le répertoire
            $contents = $this->listDirectory($server, $directoryPath);
            
            // Si on peut lister le contenu, le répertoire existe
            $exists = is_array($contents);
            Log::debug("Répertoire {$directoryPath} " . ($exists ? "existe" : "n'existe pas") . " sur le serveur");
            
            return $exists;
            
        } catch (\Exception $e) {
            Log::debug("Répertoire {$directoryPath} n'existe pas sur le serveur (erreur: " . $e->getMessage() . ")");
            return false;
        }
    }

    /**
     * Récupère les informations d'un fichier sur le serveur
     */
    public function getFileInfo($server, string $filePath): ?array
    {
        try {
            Log::debug("Récupération des informations du fichier {$filePath} sur le serveur {$server->name}");
            
            $directory = dirname($filePath);
            $fileName = basename($filePath);
            
            $contents = $this->listDirectory($server, $directory);
            
            foreach ($contents as $item) {
                if (isset($item['name']) && $item['name'] === $fileName) {
                    Log::debug("Informations du fichier {$filePath} récupérées");
                    return $item;
                }
            }
            
            Log::debug("Fichier {$filePath} non trouvé pour récupération des informations");
            return null;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération des informations du fichier {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère la taille d'un fichier sur le serveur
     */
    public function getFileSize($server, string $filePath): ?int
    {
        try {
            $fileInfo = $this->getFileInfo($server, $filePath);
            
            if ($fileInfo && isset($fileInfo['size'])) {
                return (int) $fileInfo['size'];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération de la taille du fichier {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère la date de modification d'un fichier sur le serveur
     */
    public function getFileModifiedDate($server, string $filePath): ?string
    {
        try {
            $fileInfo = $this->getFileInfo($server, $filePath);
            
            if ($fileInfo && isset($fileInfo['modified_at'])) {
                return $fileInfo['modified_at'];
            }
            
            return null;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la récupération de la date de modification du fichier {$filePath}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Recherche des fichiers sur le serveur selon un pattern
     */
    public function searchFiles($server, string $searchPattern, string $startPath = '/'): array
    {
        try {
            Log::info("Recherche de fichiers avec le pattern '{$searchPattern}' dans {$startPath} sur le serveur {$server->name}");
            
            $results = [];
            $this->searchFilesRecursive($server, $startPath, $searchPattern, $results);
            
            Log::info("Recherche terminée, " . count($results) . " fichiers trouvés");
            return $results;
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la recherche de fichiers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Recherche récursive de fichiers
     */
    protected function searchFilesRecursive($server, string $currentPath, string $searchPattern, array &$results, int $maxDepth = 10, int $currentDepth = 0): void
    {
        if ($currentDepth >= $maxDepth) {
            return;
        }

        try {
            $contents = $this->listDirectory($server, $currentPath);
            
            foreach ($contents as $item) {
                $itemPath = $currentPath . '/' . $item['name'];
                
                // Vérifier si l'élément correspond au pattern de recherche
                if (isset($item['type']) && $item['type'] === 'file' && 
                    fnmatch($searchPattern, $item['name'])) {
                    $results[] = [
                        'path' => $itemPath,
                        'name' => $item['name'],
                        'size' => $item['size'] ?? 0,
                        'modified_at' => $item['modified_at'] ?? null
                    ];
                }
                
                // Continuer la recherche dans les sous-répertoires
                if (isset($item['type']) && $item['type'] === 'directory' && 
                    !in_array($item['name'], ['.', '..', 'cache', 'temp', 'logs'])) {
                    $this->searchFilesRecursive($server, $itemPath, $searchPattern, $results, $maxDepth, $currentDepth + 1);
                }
            }
            
        } catch (\Exception $e) {
            Log::debug("Erreur lors de la recherche dans {$currentPath}: " . $e->getMessage());
        }
    }
}
