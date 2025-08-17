<?php

namespace App\Services\Servers;

use App\Models\Server;
use App\Models\Addon;
use App\Models\ServerAddon;
use App\Repositories\Daemon\DaemonFileRepository;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class AddonManagementService
{
    public function __construct(
        private DaemonFileRepository $fileRepository
    ) {}

    public function installAddon(Server $server, Addon $addon): ServerAddon
    {
        try {
            if ($this->isAddonInstalled($server, $addon)) {
                throw new Exception("L'addon {$addon->name} est déjà installé sur ce serveur.");
            }

            $serverAddon = $this->createServerAddonRecord($server, $addon);
            $serverAddon->update(['status' => ServerAddon::STATUS_UPDATING]);

            $downloadPath = $this->downloadAddon($addon);
            $this->uploadAddonToServer($server, $addon, $downloadPath);
            $this->configureAddon($server, $addon, $serverAddon);

            $serverAddon->update([
                'status' => ServerAddon::STATUS_INSTALLED,
                'installation_date' => now(),
                'last_update' => now(),
            ]);

            $addon->incrementDownloads();

            Log::info("Addon {$addon->name} installé avec succès sur le serveur {$server->name}");

            return $serverAddon;
        } catch (Exception $e) {
            if (isset($serverAddon)) {
                $serverAddon->update(['status' => ServerAddon::STATUS_FAILED]);
            }
            
            Log::error("Erreur lors de l'installation de l'addon {$addon->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function uninstallAddon(Server $server, Addon $addon): void
    {
        try {
            $serverAddon = ServerAddon::where('server_id', $server->id)
                ->where('addon_id', $addon->id)
                ->first();

            if (!$serverAddon) {
                throw new Exception("L'addon {$addon->name} n'est pas installé sur ce serveur.");
            }

            $this->removeAddonFromServer($server, $addon, $serverAddon);
            $serverAddon->delete();

            Log::info("Addon {$addon->name} désinstallé avec succès du serveur {$server->name}");
        } catch (Exception $e) {
            Log::error("Erreur lors de la désinstallation de l'addon {$addon->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function updateAddon(Server $server, Addon $addon): ServerAddon
    {
        try {
            $serverAddon = ServerAddon::where('server_id', $server->id)
                ->where('addon_id', $addon->id)
                ->first();

            if (!$serverAddon) {
                throw new Exception("L'addon {$addon->name} n'est pas installé sur ce serveur.");
            }

            $serverAddon->update(['status' => ServerAddon::STATUS_UPDATING]);

            $this->removeAddonFromServer($server, $addon, $serverAddon);
            
            $downloadPath = $this->downloadAddon($addon);
            $this->uploadAddonToServer($server, $addon, $downloadPath);
            $this->configureAddon($server, $addon, $serverAddon);

            $serverAddon->update([
                'status' => ServerAddon::STATUS_INSTALLED,
                'version' => $addon->version,
                'last_update' => now(),
            ]);

            Log::info("Addon {$addon->name} mis à jour avec succès sur le serveur {$server->name}");

            return $serverAddon;
        } catch (Exception $e) {
            if (isset($serverAddon)) {
                $serverAddon->update(['status' => ServerAddon::STATUS_FAILED]);
            }
            
            Log::error("Erreur lors de la mise à jour de l'addon {$addon->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    public function getServerAddons(Server $server): \Illuminate\Database\Eloquent\Collection
    {
        return ServerAddon::where('server_id', $server->id)
            ->with('addon')
            ->orderBy('installation_date', 'desc')
            ->get();
    }

    public function isAddonInstalled(Server $server, Addon $addon): bool
    {
        return ServerAddon::where('server_id', $server->id)
            ->where('addon_id', $addon->id)
            ->exists();
    }

    private function createServerAddonRecord(Server $server, Addon $addon): ServerAddon
    {
        return ServerAddon::create([
            'server_id' => $server->id,
            'addon_id' => $addon->id,
            'name' => $addon->name,
            'description' => $addon->description,
            'version' => $addon->version,
            'author' => $addon->author,
            'url' => $addon->download_url,
            'status' => ServerAddon::STATUS_UPDATING,
        ]);
    }

    private function downloadAddon(Addon $addon): string
    {
        $tempPath = storage_path('app/temp/addons/');
        
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $fileName = $addon->slug . '_' . $addon->version . '.zip';
        $filePath = $tempPath . $fileName;

        $response = Http::timeout(300)->get($addon->download_url);
        
        if (!$response->successful()) {
            throw new Exception("Impossible de télécharger l'addon depuis {$addon->download_url}");
        }

        file_put_contents($filePath, $response->body());

        return $filePath;
    }

    private function uploadAddonToServer(Server $server, Addon $addon, string $downloadPath): void
    {
        $targetPath = $this->getAddonInstallPath($server, $addon);
        
        $zip = new \ZipArchive();
        if ($zip->open($downloadPath) === TRUE) {
            $extractPath = storage_path('app/temp/extract_' . $addon->slug);
            $zip->extractTo($extractPath);
            $zip->close();

            $this->uploadDirectoryToServer($server, $extractPath, $targetPath);

            $this->cleanupTempFiles($downloadPath, $extractPath);
        } else {
            throw new Exception("Impossible d'extraire l'archive de l'addon");
        }
    }

    private function uploadDirectoryToServer(Server $server, string $localPath, string $remotePath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($localPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            $relativePath = str_replace($localPath, '', $file->getPathname());
            $serverPath = $remotePath . $relativePath;

            if ($file->isDir()) {
                continue;
            }

            $this->fileRepository->putContent($server, $serverPath, file_get_contents($file->getPathname()));
        }
    }

    private function removeAddonFromServer(Server $server, Addon $addon, ServerAddon $serverAddon): void
    {
        if ($serverAddon->file_path) {
            try {
                $this->fileRepository->deleteFiles($server, [$serverAddon->file_path]);
            } catch (Exception $e) {
                Log::warning("Impossible de supprimer les fichiers de l'addon {$addon->name}: {$e->getMessage()}");
            }
        }
    }

    private function configureAddon(Server $server, Addon $addon, ServerAddon $serverAddon): void
    {
        if (!$addon->requires_config) {
            return;
        }

        $configPath = $this->getAddonConfigPath($server, $addon);
        $defaultConfig = $this->getDefaultAddonConfig($addon);

        if ($defaultConfig) {
            $this->fileRepository->putContent($server, $configPath, $defaultConfig);
            $serverAddon->update(['configuration' => json_decode($defaultConfig, true)]);
        }
    }

    private function getAddonInstallPath(Server $server, Addon $addon): string
    {
        return match ($addon->category) {
            Addon::CATEGORY_GAMEPLAY => '/garrysmod/addons/' . $addon->slug . '/',
            Addon::CATEGORY_ADMINISTRATION => '/garrysmod/addons/' . $addon->slug . '/',
            Addon::CATEGORY_UI => '/garrysmod/addons/' . $addon->slug . '/',
            default => '/garrysmod/addons/' . $addon->slug . '/',
        };
    }

    private function getAddonConfigPath(Server $server, Addon $addon): string
    {
        return '/garrysmod/data/' . $addon->slug . '_config.txt';
    }

    private function getDefaultAddonConfig(Addon $addon): ?string
    {
        if (!$addon->requires_config || !$addon->requirements) {
            return null;
        }

        $config = [];
        foreach ($addon->requirements as $key => $value) {
            if (is_array($value) && isset($value['default'])) {
                $config[$key] = $value['default'];
            }
        }

        return json_encode($config, JSON_PRETTY_PRINT);
    }

    private function cleanupTempFiles(string ...$paths): void
    {
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $this->deleteDirectory($path);
            } elseif (file_exists($path)) {
                unlink($path);
            }
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}