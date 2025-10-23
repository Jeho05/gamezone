-- Script pour vérifier les tables nécessaires
-- Exécutez ce script dans phpMyAdmin ou MySQL Workbench

USE gamezone;

-- Vérifier la table purchases
SELECT 'Table purchases:' as info;
SHOW TABLES LIKE 'purchases';

SELECT 'Colonnes de purchases:' as info;
DESCRIBE purchases;

-- Vérifier la table rewards
SELECT 'Table rewards:' as info;
SHOW TABLES LIKE 'rewards';

SELECT 'Colonnes de rewards:' as info;
DESCRIBE rewards;

-- Vérifier la table game_sessions
SELECT 'Table game_sessions:' as info;
SHOW TABLES LIKE 'game_sessions';

-- Compter les données
SELECT 'Nombre d\'achats:' as info, COUNT(*) as count FROM purchases;
SELECT 'Nombre de récompenses:' as info, COUNT(*) as count FROM rewards;

-- Afficher les achats en attente
SELECT 'Achats en attente de confirmation:' as info;
SELECT id, user_id, game_name, price, payment_status, created_at 
FROM purchases 
WHERE payment_status = 'pending' 
LIMIT 5;
