<?php
/**
 * Script pour créer un fichier .env correct
 */

echo "🔧 Création du fichier .env...\n";

$envContent = <<<ENV
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:your-app-key-here
APP_URL=http://panel.test
APP_INSTALLED=false
APP_LOCALE=fr

# Configuration de la base de données SQLite
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
DB_FOREIGN_KEYS=true

# Configuration des logs
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Configuration de la session
SESSION_DRIVER=file
SESSION_COOKIE=pelican_session

# Configuration de l'authentification
APP_2FA_REQUIRED=0

# Configuration du cache
CACHE_DRIVER=file

# Configuration des sessions
SESSION_DRIVER=file
SESSION_LIFETIME=120

# Configuration des cookies
COOKIE_SECURE=false
COOKIE_HTTPONLY=true
ENV;

$envFile = __DIR__ . '/.env';
if (file_put_contents($envFile, $envContent)) {
    echo "✅ Fichier .env créé avec succès\n";
    echo "📁 Chemin: {$envFile}\n";
    
    echo "\n📋 Contenu créé:\n";
    echo "----------------------------------------\n";
    echo $envContent;
    echo "----------------------------------------\n";
    
    echo "\n🎯 Prochaines étapes:\n";
    echo "1. Redémarrez votre serveur web\n";
    echo "2. Testez /debug/tickets dans le navigateur\n";
    echo "3. Essayez d'accéder à /server/1/tickets/1\n";
    echo "4. Vérifiez les logs dans storage/logs/laravel.log\n";
    
} else {
    echo "❌ Erreur lors de la création du fichier .env\n";
    echo "💡 Vérifiez les permissions du dossier\n";
}
