# 🔧 Solution simple pour l'erreur 404 des tickets

## Problème
L'erreur `404 | Not found - No query results for model [App\Models\Ticket]` se produit car aucun ticket n'existe dans la base de données.

## Solution
Une migration a été créée pour insérer automatiquement un ticket par défaut.

## Comment l'appliquer

### Option 1 : Exécuter la migration (recommandé)
```bash
php artisan migrate
```

### Option 2 : Si Composer ne fonctionne pas
Exécutez directement la migration SQL dans votre base de données :

```sql
-- Vérifier si des tickets existent déjà
SELECT COUNT(*) FROM tickets;

-- Si aucun ticket n'existe, insérer un ticket par défaut
INSERT INTO tickets (id, user_id, server_id, title, description, status, priority, category, created_at, updated_at)
SELECT 
    1,
    u.id,
    s.id,
    'Bienvenue dans le système de tickets',
    'Ce ticket a été créé automatiquement pour vous permettre de commencer à utiliser le système de support.',
    'open',
    'medium',
    'general',
    NOW(),
    NOW()
FROM users u, servers s
LIMIT 1;

-- Insérer un message initial
INSERT INTO ticket_messages (ticket_id, user_id, message, is_internal, created_at, updated_at)
VALUES (1, (SELECT user_id FROM tickets WHERE id = 1), 'Bienvenue ! Ce ticket a été créé automatiquement.', false, NOW(), NOW());
```

## Vérification
Après avoir appliqué la solution :
1. Accédez à `/server/1/tickets` - vous devriez voir la liste des tickets
2. Accédez à `/server/1/tickets/1` - vous devriez voir le ticket de bienvenue

## Création de nouveaux tickets
Une fois le ticket par défaut créé, vous pouvez :
1. Utiliser le bouton "Nouveau ticket" dans l'interface
2. Créer des tickets via l'API si disponible
3. Insérer directement dans la base de données

## Structure attendue
- **Table `tickets`** : Contient les informations du ticket
- **Table `ticket_messages`** : Contient les messages du ticket
- **Relations** : `user_id` → `users.id`, `server_id` → `servers.id`

---

**Note** : Cette solution est temporaire. Une fois que vous pouvez utiliser `php artisan migrate`, la migration s'exécutera automatiquement.
