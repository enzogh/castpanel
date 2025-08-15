# Logger d'erreur Lua pour Garry's Mod

Ce module ajoute un système de logging avancé pour les serveurs Garry's Mod, permettant de surveiller et analyser les erreurs Lua en temps réel.

## Fonctionnalités

### 🎯 **Surveillance en temps réel**
- Logs en direct des erreurs Lua
- Actualisation automatique toutes les 5 secondes
- Auto-scroll configurable
- Possibilité de mettre en pause la surveillance

### 📊 **Statistiques avancées**
- Comptage des erreurs critiques, avertissements et informations
- Top des addons avec le plus d'erreurs
- Catégorisation automatique des types d'erreurs
- Graphiques de tendances

### 🔍 **Filtrage et recherche**
- Recherche textuelle dans les messages d'erreur
- Filtrage par niveau (erreur, avertissement, info)
- Filtrage par période (1h, 24h, 7j, 30j, tout)
- Recherche par nom d'addon

### 📤 **Export et gestion**
- Export en JSON, CSV et TXT
- Effacement des logs
- Sauvegarde automatique des données

## Installation

### 1. **Vérification des prérequis**
- Serveur Laravel avec Filament
- Serveur Garry's Mod configuré
- Permissions de lecture des fichiers activées

### 2. **Configuration automatique**
Le module se configure automatiquement et n'apparaît que pour les serveurs Garry's Mod.

### 3. **Vérification de l'installation**
- Connectez-vous au panel serveur
- Vérifiez que l'onglet "Logger d'erreur Lua" apparaît dans la navigation
- Le widget des statistiques doit être visible sur la page console

## Utilisation

### **Accès au logger**
1. Connectez-vous au panel de votre serveur Garry's Mod
2. Cliquez sur "Logger d'erreur Lua" dans la navigation
3. Ou cliquez sur le widget des statistiques dans la console

### **Surveillance des logs**
- Les logs apparaissent en temps réel
- Utilisez les filtres pour affiner la recherche
- Activez/désactivez l'auto-scroll selon vos besoins
- Mettez en pause pour analyser des logs spécifiques

### **Analyse des erreurs**
- Consultez les statistiques en haut de page
- Analysez le top des addons problématiques
- Identifiez les types d'erreurs les plus fréquents

### **Export des données**
1. Cliquez sur le bouton "Actions" (trois points)
2. Sélectionnez "Exporter les logs"
3. Choisissez le format souhaité (JSON, CSV, TXT)
4. Téléchargez le fichier

## API

### **Endpoints disponibles**
```
GET    /api/client/servers/{server}/lua-logs
POST   /api/client/servers/{server}/lua-logs
DELETE /api/client/servers/{server}/lua-logs
GET    /api/client/servers/{server}/lua-logs/export
GET    /api/client/servers/{server}/lua-logs/stats
GET    /api/client/servers/{server}/lua-logs/top-addons
GET    /api/client/servers/{server}/lua-logs/top-errors
```

### **Exemple d'ajout de log**
```bash
curl -X POST "https://votre-panel.com/api/client/servers/{uuid}/lua-logs" \
  -H "Authorization: Bearer {votre-token}" \
  -H "Content-Type: application/json" \
  -d '{
    "level": "error",
    "message": "attempt to index a nil value",
    "addon": "DarkRP",
    "stack_trace": "stack trace here..."
  }'
```

## Configuration

### **Canal de log Laravel**
Le module crée automatiquement un canal de log `lua` dans `config/logging.php` :

```php
'lua' => [
    'driver' => 'daily',
    'path' => storage_path('logs/lua.log'),
    'level' => env('LOG_LEVEL', 'debug'),
    'days' => 14,
],
```

### **Stockage des logs**
Les logs sont stockés dans :
- `storage/app/lua_logs/server_{id}.log` (logs spécifiques au serveur)
- `storage/logs/lua.log` (logs Laravel)

## Permissions

### **Permissions requises**
- `view server` : Consulter les logs
- `update server` : Ajouter/effacer des logs

### **Vérification automatique**
Le module vérifie automatiquement :
- Le type de serveur (Garry's Mod uniquement)
- Les permissions de l'utilisateur
- L'accès au serveur

## Dépannage

### **Le logger n'apparaît pas**
1. Vérifiez que votre serveur est bien configuré comme serveur Garry's Mod
2. Vérifiez vos permissions sur le serveur
3. Vérifiez que le module est bien installé

### **Erreur de permissions**
1. Vérifiez que vous avez les permissions `view server` et `update server`
2. Contactez l'administrateur du panel

### **Logs non mis à jour**
1. Vérifiez que l'auto-scroll est activé
2. Vérifiez que la surveillance n'est pas en pause
3. Actualisez manuellement la page

## Support

Pour toute question ou problème :
1. Consultez la documentation de votre panel
2. Vérifiez les logs Laravel dans `storage/logs/lua.log`
3. Contactez l'équipe de support

## Développement

### **Structure des fichiers**
```
app/
├── Filament/Server/
│   ├── Pages/LuaErrorLogger.php
│   └── Widgets/LuaLogStatsWidget.php
├── Http/Controllers/Api/LuaLogController.php
└── Services/Servers/LuaLogService.php

resources/
└── views/filament/server/pages/
    └── lua-error-logger.blade.php

lang/
└── fr/lua-logger.php
```

### **Extension du service**
Vous pouvez étendre le `LuaLogService` pour ajouter des fonctionnalités personnalisées :

```php
class CustomLuaLogService extends LuaLogService
{
    public function customMethod(): void
    {
        // Votre logique personnalisée
    }
}
```

---

**Note** : Ce module est spécifiquement conçu pour les serveurs Garry's Mod et ne fonctionnera pas avec d'autres types de serveurs.
