<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            // Étape 1: Modifier la colonne id de la table users pour utiliser BIGINT
            if (Schema::hasTable('users')) {
                Schema::table('users', function (Blueprint $table) {
                    // Modifier la colonne id de INTEGER UNSIGNED à BIGINT UNSIGNED
                    $table->bigIncrements('id')->change();
                });
            }

            // Étape 2: Modifier toutes les colonnes qui référencent users.id pour utiliser BIGINT UNSIGNED
            // Table tickets
            if (Schema::hasTable('tickets')) {
                Schema::table('tickets', function (Blueprint $table) {
                    if (Schema::hasColumn('tickets', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                    if (Schema::hasColumn('tickets', 'assigned_to')) {
                        $table->unsignedBigInteger('assigned_to')->change();
                    }
                });
            }

            // Table ticket_messages
            if (Schema::hasTable('ticket_messages')) {
                Schema::table('ticket_messages', function (Blueprint $table) {
                    if (Schema::hasColumn('ticket_messages', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                });
            }

            // Table announcements - corrigée par une migration séparée

            // Table servers
            if (Schema::hasTable('servers')) {
                Schema::table('servers', function (Blueprint $table) {
                    if (Schema::hasColumn('servers', 'owner_id')) {
                        $table->unsignedBigInteger('owner_id')->change();
                    }
                });
            }

            // Table server_addons
            if (Schema::hasTable('server_addons')) {
                Schema::table('server_addons', function (Blueprint $table) {
                    if (Schema::hasColumn('server_addons', 'server_id')) {
                        $table->unsignedBigInteger('server_id')->change();
                    }
                });
            }

            // Table recovery_tokens
            if (Schema::hasTable('recovery_tokens')) {
                Schema::table('recovery_tokens', function (Blueprint $table) {
                    if (Schema::hasColumn('recovery_tokens', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                });
            }

            // Table user_ssh_keys
            if (Schema::hasTable('user_ssh_keys')) {
                Schema::table('user_ssh_keys', function (Blueprint $table) {
                    if (Schema::hasColumn('user_ssh_keys', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                });
            }

            // Table daemon_keys
            if (Schema::hasTable('daemon_keys')) {
                Schema::table('daemon_keys', function (Blueprint $table) {
                    if (Schema::hasColumn('daemon_keys', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                });
            }

            // Table audit_logs
            if (Schema::hasTable('audit_logs')) {
                Schema::table('audit_logs', function (Blueprint $table) {
                    if (Schema::hasColumn('audit_logs', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                });
            }

            // Table tasks
            if (Schema::hasTable('tasks')) {
                Schema::table('tasks', function (Blueprint $table) {
                    if (Schema::hasColumn('tasks', 'user_id')) {
                        $table->unsignedBigInteger('user_id')->change();
                    }
                });
            }

            // Étape 3: Ajouter les contraintes de clé étrangère après avoir corrigé les types
            // Table tickets
            if (Schema::hasTable('tickets')) {
                Schema::table('tickets', function (Blueprint $table) {
                    if (Schema::hasColumn('tickets', 'user_id') && !$this->foreignKeyExists('tickets', 'tickets_user_id_foreign')) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                    if (Schema::hasColumn('tickets', 'server_id') && !$this->foreignKeyExists('tickets', 'tickets_server_id_foreign')) {
                        $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
                    }
                    if (Schema::hasColumn('tickets', 'assigned_to') && !$this->foreignKeyExists('tickets', 'tickets_assigned_to_foreign')) {
                        $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
                    }
                });
            }

            // Table ticket_messages
            if (Schema::hasTable('ticket_messages')) {
                Schema::table('ticket_messages', function (Blueprint $table) {
                    if (Schema::hasColumn('ticket_messages', 'ticket_id') && !$this->foreignKeyExists('ticket_messages', 'ticket_messages_ticket_id_foreign')) {
                        $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
                    }
                    if (Schema::hasColumn('ticket_messages', 'user_id') && !$this->foreignKeyExists('ticket_messages', 'ticket_messages_user_id_foreign')) {
                        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                    }
                });
            }

            // Table announcements - contrainte de clé étrangère ajoutée par une migration séparée

            // Table server_addons
            if (Schema::hasTable('server_addons')) {
                Schema::table('server_addons', function (Blueprint $table) {
                    if (Schema::hasColumn('server_addons', 'server_id') && !$this->foreignKeyExists('server_addons', 'server_addons_server_id_foreign')) {
                        $table->foreign('server_id')->references('id')->on('servers')->onDelete('cascade');
                    }
                    if (Schema::hasColumn('server_addons', 'addon_id') && !$this->foreignKeyExists('server_addons', 'server_addons_addon_id_foreign')) {
                        $table->foreign('addon_id')->references('id')->on('addons')->onDelete('cascade');
                    }
                });
            }

        } catch (Exception $e) {
            // Log l'erreur mais ne pas faire échouer la migration
            \Log::error('Erreur lors de la correction des types de colonnes: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Supprimer les contraintes de clé étrangère ajoutées
        if (Schema::hasTable('tickets')) {
            Schema::table('tickets', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropForeign(['server_id']);
                $table->dropForeign(['assigned_to']);
            });
        }

        if (Schema::hasTable('ticket_messages')) {
            Schema::table('ticket_messages', function (Blueprint $table) {
                $table->dropForeign(['ticket_id']);
                $table->dropForeign(['user_id']);
            });
        }

        // Table announcements - gérée par une migration séparée

        // Table server_addons
        if (Schema::hasTable('server_addons')) {
            Schema::table('server_addons', function (Blueprint $table) {
                try {
                    $table->dropForeign(['server_id']);
                } catch (Exception $e) {
                    // Ignorer l'erreur si la contrainte n'existe pas
                }
                try {
                    $table->dropForeign(['addon_id']);
                } catch (Exception $e) {
                    // Ignorer l'erreur si la contrainte n'existe pas
                }
            });
        }
    }

    /**
     * Vérifier si une contrainte de clé étrangère existe
     */
    private function foreignKeyExists(string $table, string $constraint): bool
    {
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
            ", [$table, $constraint]);
            
            return !empty($foreignKeys);
        } catch (Exception $e) {
            return false;
        }
    }
};
