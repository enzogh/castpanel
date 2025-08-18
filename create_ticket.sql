-- Script pour créer un ticket par défaut et résoudre l'erreur 404
-- Exécutez ce script dans votre base de données

-- Vérifier si des tickets existent déjà
SELECT COUNT(*) as ticket_count FROM tickets;

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

-- Vérifier que le ticket a été créé
SELECT 
    t.id,
    t.title,
    t.status,
    t.user_id,
    t.server_id,
    u.email as user_email,
    s.name as server_name
FROM tickets t
JOIN users u ON t.user_id = u.id
JOIN servers s ON t.server_id = s.id
WHERE t.id = 1;
