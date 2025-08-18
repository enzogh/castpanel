<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Vérifier si des tickets existent déjà
        if (DB::table('tickets')->count() > 0) {
            return;
        }

        // Récupérer le premier utilisateur et serveur
        $user = DB::table('users')->first();
        $server = DB::table('servers')->first();

        if (!$user || !$server) {
            return;
        }

        // Insérer un ticket par défaut
        DB::table('tickets')->insert([
            'id' => 1,
            'user_id' => $user->id,
            'server_id' => $server->id,
            'title' => 'Bienvenue dans le système de tickets',
            'description' => 'Ce ticket a été créé automatiquement pour vous permettre de commencer à utiliser le système de support.',
            'status' => 'open',
            'priority' => 'medium',
            'category' => 'general',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insérer un message initial
        DB::table('ticket_messages')->insert([
            'ticket_id' => 1,
            'user_id' => $user->id,
            'message' => 'Bienvenue ! Ce ticket a été créé automatiquement.',
            'is_internal' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Supprimer le ticket par défaut
        DB::table('ticket_messages')->where('ticket_id', 1)->delete();
        DB::table('tickets')->where('id', 1)->delete();
    }
};
