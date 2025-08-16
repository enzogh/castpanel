# 🎮 Système Lua Console Hook - Documentation Complète

## 📋 Vue d'ensemble

Le **Système Lua Console Hook** est une solution complète de surveillance automatique des erreurs Lua pour vos serveurs Garry's Mod. Il capture en temps réel toutes les erreurs avec leurs stack traces complètes et les enregistre automatiquement en base de données.

## ✨ Fonctionnalités principales

### 🔍 **Surveillance automatique**
- Détection en temps réel des erreurs Lua `[ERROR]`
- Filtrage automatique des serveurs Garry's Mod uniquement
- Surveillance continue avec intervalle configurable (1s à 60s)

### 📊 **Capture complète des erreurs**
- Message d'erreur complet
- Stack trace détaillée
- Contexte de l'erreur
- Timestamp de détection
- Identification de l'addon responsable

### 🚀 **Modes d'exécution**
- **Mode console** : Surveillance en temps réel avec affichage live
- **Mode daemon** : Service en arrière-plan avec gestion des processus
- **Mode debug** : Test sans base de données avec serveurs simulés

### 🎯 **Filtrage intelligent**
- Serveurs Garry's Mod uniquement
- Filtrage par ID de serveur spécifique
- Exclusion des serveurs suspendus ou non installés

## 🛠️ Installation et configuration

### 1. **Vérification des prérequis**
```bash
# Vérifier que PHP a les extensions nécessaires
php -m | grep -E "(pcntl|posix)"

# Vérifier que Laravel est installé
php artisan --version
```

### 2. **Structure des fichiers**
```
app/
├── Console/Commands/
│   ├── MonitorLuaConsole.php          # Surveillance en temps réel
│   ├── AnalyzeLuaLogs.php             # Analyse des logs
│   ├── LuaConsoleDaemon.php           # Gestion du daemon
│   └── InsertTestLuaErrors.php        # Insertion d'erreurs de test
├── Services/Servers/
│   ├── LuaConsoleHookService.php      # Service principal
│   └── LuaLogService.php              # Gestion des logs
├── Filament/Pages/
│   ├── LuaConsoleDaemonControl.php    # Contrôle du daemon
│   └── LuaErrorDashboard.php          # Dashboard des erreurs
└── Models/
    └── LuaError.php                   # Modèle des erreurs
```

## 🚀 Utilisation

### **Commandes Artisan principales**

#### 1. **Surveillance en temps réel**
```bash
# Surveillance de tous les serveurs GMod
php artisan lua:monitor --stream

# Surveillance d'un serveur spécifique
php artisan lua:monitor --server=123 --stream

# Mode debug avec serveurs simulés
php artisan lua:monitor --debug --stream

# Combinaison des options
php artisan lua:monitor --server=123 --debug --stream
```

#### 2. **Gestion du daemon**
```bash
# Démarrer le daemon
php artisan lua:daemon start --server=123 --interval=5

# Vérifier le statut
php artisan lua:daemon status

# Arrêter le daemon
php artisan lua:daemon stop

# Redémarrer le daemon
php artisan lua:daemon restart
```

#### 3. **Analyse des logs**
```bash
# Analyser un fichier spécifique
php artisan lua:analyze-logs --file=/path/to/log.txt

# Analyser les logs d'un serveur
php artisan lua:analyze-logs --server=123

# Exporter en différents formats
php artisan lua:analyze-logs --output=json --errors-only
```

#### 4. **Tests et développement**
```bash
# Insérer des erreurs de test
php artisan lua:insert-test-errors 123 --count=10

# Test complet du système
php test_lua_system.php
```

### **Interface web Filament**

#### 1. **Contrôle du Daemon** (`/admin/lua-console-daemon`)
- Statut en temps réel du daemon
- Contrôles de démarrage/arrêt/redémarrage
- Configuration de l'intervalle de surveillance
- Actualisation automatique de l'interface

#### 2. **Dashboard des Erreurs** (`/admin/lua-error-dashboard`)
- Statistiques globales des erreurs
- Erreurs récentes avec détails
- Répartition des erreurs par serveur
- Actions rapides vers les autres pages

#### 3. **Logger d'Erreurs** (`/admin/lua-error-logger`)
- Affichage des erreurs par serveur
- Gestion du statut (ouvert/résolu/fermé)
- Stack traces détaillées
- Filtres et recherche

## 🔧 Configuration avancée

### **Variables d'environnement**
```env
# Intervalle de surveillance par défaut (secondes)
LUA_CONSOLE_CHECK_INTERVAL=5

# Mode debug (true/false)
LUA_CONSOLE_DEBUG_MODE=false

# Fichier PID du daemon
LUA_CONSOLE_PID_FILE=storage/lua-console-hook.pid
```

### **Personnalisation des patterns d'erreur**
Dans `LuaConsoleHookService.php`, modifiez la méthode `isLuaError()` :
```php
private function isLuaError(string $line): bool
{
    $patterns = [
        '/\[ERROR\]/i',
        '/Lua Error:/i',
        '/Script Error:/i',
        // Ajoutez vos patterns personnalisés ici
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $line)) {
            return true;
        }
    }
    
    return false;
}
```

## 📊 Structure de la base de données

### **Table `lua_errors`**
```sql
CREATE TABLE lua_errors (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    server_id BIGINT UNSIGNED NOT NULL,
    error_key VARCHAR(255) UNIQUE NOT NULL,
    level ENUM('ERROR', 'WARNING', 'INFO') DEFAULT 'ERROR',
    message TEXT NOT NULL,
    addon VARCHAR(255) NULL,
    stack_trace TEXT NULL,
    context TEXT NULL,
    count INT UNSIGNED DEFAULT 1,
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('open', 'resolved', 'closed') DEFAULT 'open',
    resolved BOOLEAN DEFAULT FALSE,
    resolved_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_server_status (server_id, status),
    INDEX idx_error_key (error_key),
    INDEX idx_last_seen (last_seen)
);
```

## 🧪 Tests et débogage

### **Mode debug**
Le mode debug permet de tester le système sans base de données :
```php
$hookService = app(LuaConsoleHookService::class);
$hookService->setDebugMode(true);
$hookService->enableStreamingMode();
$hookService->startHooking();
```

### **Serveurs simulés**
En mode debug, le système crée des serveurs de test avec des erreurs simulées :
- **GMod Test Server 1** : Erreurs d'addons
- **GMod Test Server 2** : Erreurs de scripts
- **GMod Test Server 3** : Erreurs de ressources

### **Script de test complet**
```bash
# Exécuter le test complet
php test_lua_system.php

# Ce script teste :
# 1. Initialisation du service
# 2. Création des serveurs de test
# 3. Surveillance en streaming
# 4. Détection d'erreurs simulées
```

## 🚨 Dépannage

### **Problèmes courants**

#### 1. **"Target class does not exist"**
```bash
# Vérifier que le service est bien créé
php artisan make:service LuaConsoleHookService

# Vérifier l'autoload
composer dump-autoload
```

#### 2. **Erreurs de connexion à la base**
```bash
# Utiliser le mode debug
php artisan lua:monitor --debug

# Vérifier la configuration DB
php artisan config:show database
```

#### 3. **Daemon ne démarre pas**
```bash
# Vérifier les permissions
chmod +x storage/
chmod 666 storage/lua-console-hook.pid

# Vérifier les extensions PHP
php -m | grep pcntl
```

#### 4. **Aucune erreur détectée**
```bash
# Vérifier que les serveurs sont GMod
php artisan tinker --execute="App\Models\Server::where('egg_id', 1)->get()"

# Vérifier les patterns de détection
php artisan lua:monitor --debug --stream
```

### **Logs et monitoring**
```bash
# Logs Laravel
tail -f storage/logs/laravel.log

# Logs du daemon
tail -f storage/logs/lua-console-hook.log

# Statut du processus
ps aux | grep lua-console-hook
```

## 🔄 Mise à jour et maintenance

### **Mise à jour du système**
```bash
# Pull des dernières modifications
git pull origin main

# Mise à jour des dépendances
composer update

# Nettoyage du cache
php artisan cache:clear
php artisan config:clear
```

### **Maintenance du daemon**
```bash
# Redémarrer le service
php artisan lua:daemon restart

# Vérifier l'intégrité
php artisan lua:daemon status

# Nettoyer les anciens processus
pkill -f lua-console-hook
```

## 📈 Performance et optimisation

### **Recommandations**
- **Intervalle optimal** : 5-10 secondes pour la plupart des cas
- **Mode streaming** : Pour le développement et le debugging
- **Mode daemon** : Pour la production avec surveillance continue
- **Cache des erreurs** : Évite la duplication des erreurs identiques

### **Monitoring des ressources**
```bash
# Utilisation CPU
top -p $(cat storage/lua-console-hook.pid)

# Utilisation mémoire
ps -o pid,ppid,cmd,%mem,%cpu --pid=$(cat storage/lua-console-hook.pid)

# Logs de performance
tail -f storage/logs/lua-console-hook.log | grep "PERF"
```

## 🤝 Support et contribution

### **Rapport de bugs**
1. Vérifiez les logs : `storage/logs/laravel.log`
2. Testez en mode debug : `php artisan lua:monitor --debug`
3. Vérifiez la configuration de la base de données
4. Créez une issue avec les détails complets

### **Améliorations suggérées**
- Interface de configuration avancée
- Notifications par email/Slack
- Intégration avec d'autres systèmes de monitoring
- API REST pour l'intégration externe
- Métriques et graphiques de performance

---

## 🎯 **Résumé des commandes principales**

| Commande | Description | Options |
|----------|-------------|---------|
| `lua:monitor` | Surveillance en temps réel | `--server`, `--stream`, `--debug` |
| `lua:daemon` | Gestion du service | `start`, `stop`, `status`, `restart` |
| `lua:analyze-logs` | Analyse des logs | `--file`, `--server`, `--output` |
| `lua:insert-test-errors` | Erreurs de test | `--count` |

## 🌟 **Fonctionnalités clés**

✅ **Surveillance automatique** des serveurs GMod  
✅ **Détection en temps réel** des erreurs Lua  
✅ **Stack traces complètes** avec contexte  
✅ **Service daemon** en arrière-plan  
✅ **Interface web** complète dans Filament  
✅ **Mode debug** pour les tests  
✅ **Filtrage intelligent** des serveurs  
✅ **Enregistrement automatique** en base  
✅ **Gestion des erreurs** (ouvert/résolu/fermé)  
✅ **Export** dans plusieurs formats  

---

**🎮 Votre système de surveillance Lua est maintenant opérationnel !** 🚀
