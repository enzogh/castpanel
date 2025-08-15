# Surveillance en temps réel des erreurs Lua

## 🎯 **Fonctionnalité de surveillance automatique**

Le logger d'erreur Lua surveille maintenant automatiquement la console de votre serveur Garry's Mod et capture en temps réel toutes les erreurs Lua qui se produisent.

### **Détection automatique des erreurs**

Le système détecte automatiquement les erreurs Lua courantes :

- **`[ERROR] lua_run:1: attempt to call global 'caca' (a nil value)`**
- **`attempt to index a nil value`**
- **`bad argument #1 to 'function'`**
- **`syntax error`**
- **`missing dependency`**
- **`failed to load addon`**

### **Capture de la stack trace**

Pour chaque erreur détectée, le système capture automatiquement :

- **Ligne d'erreur** : Le message d'erreur complet
- **Contexte** : Quelques lignes avant et après l'erreur
- **Stack trace** : La trace d'exécution complète
- **Nom de l'addon** : Identification automatique de l'addon problématique
- **Timestamp** : Heure exacte de l'erreur

## 🚀 **Comment ça fonctionne**

### **1. Surveillance continue**
- **Polling automatique** : Toutes les 5 secondes
- **API Daemon** : Connexion directe à la console du serveur
- **Détection en temps réel** : Capture immédiate des erreurs

### **2. Traitement intelligent**
- **Déduplication** : Évite les doublons d'erreurs
- **Catégorisation** : Classification automatique des types d'erreurs
- **Stockage persistant** : Sauvegarde automatique dans les fichiers de log

### **3. Interface réactive**
- **Indicateur visuel** : Point vert pulsant pour la surveillance active
- **Nouvelles erreurs** : Marquage spécial pour les erreurs récemment détectées
- **Mise à jour automatique** : Interface qui se rafraîchit en temps réel

## 📱 **Utilisation de l'interface**

### **Contrôles de surveillance**
- **Bouton Pause/Reprendre** : Contrôler la surveillance
- **Auto-scroll** : Suivre automatiquement les nouvelles erreurs
- **Indicateur de statut** : Voir si la surveillance est active

### **Affichage des erreurs**
- **Erreurs en temps réel** : Apparaissent immédiatement
- **Marquage spécial** : Anneau bleu pour les nouvelles erreurs
- **Badge "Nouvelle erreur"** : Identification claire des erreurs récentes

## 🔧 **Configuration technique**

### **Endpoint de surveillance**
```
GET /api/client/servers/{server}/lua-logs/monitor
```

### **Réponse de l'API**
```json
{
    "success": true,
    "data": {
        "new_errors": [
            {
                "timestamp": "2024-01-15T10:30:45.123Z",
                "level": "error",
                "message": "[ERROR] lua_run:1: attempt to call global 'caca' (a nil value)",
                "addon": "Console Command",
                "stack_trace": ">>> [ERROR] lua_run:1: attempt to call global 'caca' (a nil value)\n    1. unknown - lua_run:1",
                "line_number": 1,
                "error_type": "Call nil value"
            }
        ],
        "total_new": 1,
        "timestamp": "2024-01-15T10:30:45.123Z"
    }
}
```

### **Patterns de détection**
Le système utilise des expressions régulières pour détecter les erreurs :

```php
$luaErrorPatterns = [
    '/^\[ERROR\]/i',           // [ERROR] au début de ligne
    '/lua_run:\d+:/',          // lua_run:1:, lua_run:2:, etc.
    '/attempt to call global/', // Tentative d'appel d'une fonction nil
    '/attempt to index/',       // Tentative d'indexation d'une valeur nil
    '/bad argument/',           // Mauvais argument passé à une fonction
    '/syntax error/',           // Erreur de syntaxe
    '/nil value/',              // Valeur nil
    '/missing dependency/',     // Dépendance manquante
    '/failed to load/',         // Échec de chargement
    '/error in addon/',         // Erreur dans un addon
];
```

## 📊 **Exemples d'erreurs capturées**

### **Erreur de fonction nil**
```
[ERROR] lua_run:1: attempt to call global 'caca' (a nil value)
1. unknown - lua_run:1
```

**Capture automatique :**
- **Type** : Call nil value
- **Addon** : Console Command
- **Stack trace** : Ligne d'erreur + contexte

### **Erreur d'indexation**
```
[ERROR] addon: attempt to index a nil value
```

**Capture automatique :**
- **Type** : Index nil value
- **Addon** : addon
- **Stack trace** : Contexte complet

### **Erreur de syntaxe**
```
[ERROR] syntax error near 'end'
```

**Capture automatique :**
- **Type** : Syntax error
- **Addon** : Unknown Addon
- **Stack trace** : Ligne problématique

## 🎮 **Intégration avec Garry's Mod**

### **Console de commandes**
Le système surveille automatiquement la sortie de la console de commandes de Garry's Mod, y compris :

- **Commandes lua_run** : Erreurs dans les commandes Lua
- **Addons** : Erreurs de chargement et d'exécution
- **Scripts** : Erreurs dans les scripts serveur

### **Fichiers de logs**
Les erreurs sont également capturées depuis :

- **Console serveur** : Sortie directe du serveur
- **Logs d'addons** : Messages d'erreur des addons
- **Scripts d'installation** : Erreurs lors du démarrage

## 🔍 **Dépannage**

### **La surveillance ne fonctionne pas**
1. **Vérifiez les permissions** : `view server` requis
2. **Vérifiez la connexion** : Serveur doit être accessible
3. **Vérifiez le type** : Serveur doit être Garry's Mod

### **Erreurs non détectées**
1. **Vérifiez les patterns** : L'erreur doit correspondre aux patterns définis
2. **Vérifiez la console** : L'erreur doit apparaître dans la console
3. **Vérifiez les logs** : Consultez les logs Laravel pour les erreurs

### **Performance**
1. **Polling** : Réduisez l'intervalle si nécessaire
2. **Mémoire** : Limite automatique à 100 erreurs en mémoire
3. **Stockage** : Logs automatiquement sauvegardés

## 🚀 **Fonctionnalités avancées**

### **Webhook automatique**
Possibilité d'ajouter des webhooks pour notifier en temps réel :

```php
// Dans le service
if (!empty($newErrors)) {
    $this->dispatchWebhook($server, $newErrors);
}
```

### **Notifications push**
Intégration avec les notifications du panel pour alerter immédiatement :

```php
// Notification en temps réel
$server->notify(new LuaErrorDetected($newErrors));
```

### **Export automatique**
Export automatique des erreurs critiques :

```php
// Export automatique des erreurs
if (count($criticalErrors) > 10) {
    $this->exportLogs($server, 'json');
}
```

---

**Note** : Cette fonctionnalité de surveillance en temps réel est spécifiquement conçue pour les serveurs Garry's Mod et nécessite une connexion active au daemon du serveur.
