<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\Addons\GmodAddonScannerService;
use Illuminate\Console\Command;

class ScanGmodAddons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addon:scan-gmod {server_id? : ID du serveur à scanner (optionnel)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scanner les addons Garry\'s Mod installés dans le répertoire garrysmod/addons';

    protected GmodAddonScannerService $scanner;

    public function __construct(GmodAddonScannerService $scanner)
    {
        parent::__construct();
        $this->scanner = $scanner;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serverId = $this->argument('server_id');

        if ($serverId) {
            // Scanner un serveur spécifique
            $server = Server::find($serverId);
            if (!$server) {
                $this->error("Serveur avec l'ID {$serverId} introuvable.");
                return 1;
            }

            return $this->scanServer($server);
        } else {
            // Scanner tous les serveurs Garry's Mod
            return $this->scanAllGmodServers();
        }
    }

    protected function scanServer(Server $server): int
    {
        if (!$this->scanner->isGmodServer($server)) {
            $this->warn("Le serveur '{$server->name}' n'est pas un serveur Garry's Mod. Ignoré.");
            return 0;
        }

        $this->info("Scan du serveur '{$server->name}' (ID: {$server->id})...");

        try {
            // Scanner les addons installés
            $detectedAddons = $this->scanner->scanInstalledAddons($server);
            $this->line("  → {count($detectedAddons)} addons détectés dans garrysmod/addons");

            // Synchroniser avec la base de données
            $syncResults = $this->scanner->syncDetectedAddons($server, $detectedAddons);

            $this->info("  → Synchronisation terminée :");
            $this->line("    - {$syncResults['added']} addons ajoutés");
            $this->line("    - {$syncResults['updated']} addons mis à jour");
            $this->line("    - {$syncResults['removed']} addons supprimés");

            if (!empty($syncResults['errors'])) {
                $this->warn("  → Erreurs rencontrées :");
                foreach ($syncResults['errors'] as $error) {
                    $this->line("    - {$error}");
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error("  → Erreur lors du scan : {$e->getMessage()}");
            return 1;
        }
    }

    protected function scanAllGmodServers(): int
    {
        $servers = Server::all();
        $gmodServers = $servers->filter(function ($server) {
            return $this->scanner->isGmodServer($server);
        });

        if ($gmodServers->isEmpty()) {
            $this->warn('Aucun serveur Garry\'s Mod trouvé.');
            return 0;
        }

        $this->info("Scan de {$gmodServers->count()} serveur(s) Garry's Mod...");
        $this->newLine();

        $totalAdded = 0;
        $totalUpdated = 0;
        $totalRemoved = 0;
        $totalErrors = 0;
        $failedServers = 0;

        foreach ($gmodServers as $server) {
            $result = $this->scanServer($server);
            if ($result !== 0) {
                $failedServers++;
            }
            $this->newLine();
        }

        $this->info('=== Résumé du scan ===');
        $this->line("Serveurs scannés : {$gmodServers->count()}");
        if ($failedServers > 0) {
            $this->warn("Serveurs en erreur : {$failedServers}");
        }

        return $failedServers > 0 ? 1 : 0;
    }
}
