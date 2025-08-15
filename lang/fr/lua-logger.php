<?php

return [
    'title' => 'Logger d\'erreur Lua',
    'subtitle' => 'Surveillez et analysez les erreurs Lua de votre serveur Garry\'s Mod',
    
    'stats' => [
        'critical_errors' => 'Erreurs critiques',
        'warnings' => 'Avertissements',
        'info' => 'Informations',
        'total' => 'Total',
    ],
    
    'filters' => [
        'search' => 'Rechercher dans les logs',
        'search_placeholder' => 'Rechercher une erreur, un addon, etc...',
        'level' => 'Niveau',
        'level_all' => 'Tous les niveaux',
        'level_error' => 'Erreurs',
        'level_warning' => 'Avertissements',
        'level_info' => 'Informations',
        'time' => 'Période',
        'time_1h' => 'Dernière heure',
        'time_24h' => 'Dernières 24h',
        'time_7d' => '7 derniers jours',
        'time_30d' => '30 derniers jours',
        'time_all' => 'Tout',
    ],
    
    'actions' => [
        'refresh' => 'Actualiser',
        'clear_logs' => 'Effacer les logs',
        'export_logs' => 'Exporter les logs',
        'auto_scroll' => 'Auto-scroll',
        'auto_scroll_disabled' => 'Auto-scroll désactivé',
        'pause' => 'Pause',
        'resume' => 'Reprendre',
    ],
    
    'sections' => [
        'real_time_logs' => 'Logs en temps réel',
        'error_analysis' => 'Analyse des erreurs',
        'top_addons' => 'Top des addons avec erreurs',
        'top_errors' => 'Top des erreurs',
    ],
    
    'messages' => [
        'loading' => 'Chargement des logs...',
        'no_logs' => 'Aucun log disponible pour le moment',
        'no_logs_description' => 'Les logs apparaîtront ici en temps réel',
        'no_search_results' => 'Aucun log ne correspond aux critères de recherche',
        'logs_cleared' => 'Logs effacés avec succès',
        'no_data' => 'Aucune donnée disponible',
    ],
    
    'export' => [
        'formats' => [
            'json' => 'JSON',
            'csv' => 'CSV',
            'txt' => 'TXT',
        ],
        'filename' => 'lua_logs_server_{server_id}_{timestamp}',
    ],
    
    'errors' => [
        'not_gmod_server' => 'Cette fonctionnalité n\'est disponible que pour les serveurs Garry\'s Mod',
        'unauthorized' => 'Vous n\'avez pas les permissions pour accéder à cette fonctionnalité',
        'failed_to_clear' => 'Échec de l\'effacement des logs',
        'no_logs_to_export' => 'Aucun log à exporter',
    ],
];
