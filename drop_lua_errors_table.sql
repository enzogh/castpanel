-- Supprimer la table lua_errors problématique
DROP TABLE IF EXISTS `lua_errors`;

-- Vérifier que la table a été supprimée
SHOW TABLES LIKE 'lua_errors';
