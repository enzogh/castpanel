<?php

namespace App\Console\Commands;

use App\Models\LuaError;
use App\Models\Server;
use Illuminate\Console\Command;

class InsertTestLuaErrors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lua:insert-test-errors {server_id} {--count=5 : Number of test errors to insert}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert test Lua errors into the database for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $serverId = (int) $this->argument('server_id');
        $count = (int) $this->option('count');

        $this->info("🧪 Inserting {$count} test Lua errors for server ID: {$serverId}");

        try {
            // Vérifier que le serveur existe
            $server = Server::find($serverId);
            if (!$server) {
                $this->error("❌ Server with ID {$serverId} not found");
                return 1;
            }

            $this->info("✅ Server found: {$server->name}");

            // Insérer des erreurs de test
            for ($i = 1; $i <= $count; $i++) {
                $errorKey = md5("test_error_{$i}_{$serverId}");
                
                $luaError = LuaError::create([
                    'server_id' => $serverId,
                    'error_key' => $errorKey,
                    'level' => 'ERROR',
                    'message' => "[ERROR] Test Lua error #{$i} - This is a test error message for testing purposes",
                    'addon' => "test_addon_{$i}",
                    'stack_trace' => "at test_function_{$i}.lua:123\nat main.lua:456\nat server_init.lua:789",
                    'count' => rand(1, 5),
                    'first_seen' => now()->subMinutes(rand(1, 60)),
                    'last_seen' => now()->subMinutes(rand(0, 10)),
                    'status' => 'open',
                    'resolved' => false
                ]);

                $this->info("✅ Inserted error #{$i}: {$luaError->message}");
            }

            $this->info("\n🎉 Successfully inserted {$count} test errors!");
            $this->info("💡 You can now check the Lua Error Logger page to see these errors");

        } catch (\Exception $e) {
            $this->error('❌ Failed to insert test errors: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
