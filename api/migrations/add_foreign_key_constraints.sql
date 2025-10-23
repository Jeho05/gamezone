-- Migration: Ajout de contraintes de clé étrangère pour l'intégrité référentielle
-- Date: 2025-10-21
-- Description: Empêche la création de packages sans jeu valide et la suppression de jeux utilisés

USE `gamezone`;

-- ============================================================================
-- Vérifier d'abord que toutes les données existantes sont valides
-- ============================================================================

-- Vérifier qu'aucun package n'a un game_id invalide
SELECT COUNT(*) as invalid_packages
FROM game_packages gp
LEFT JOIN games g ON gp.game_id = g.id
WHERE gp.game_id IS NOT NULL AND g.id IS NULL;

-- Si des packages invalides existent, les afficher (pour correction manuelle si nécessaire)
SELECT gp.id, gp.name, gp.game_id, 'Package avec game_id invalide' as issue
FROM game_packages gp
LEFT JOIN games g ON gp.game_id = g.id
WHERE gp.game_id IS NOT NULL AND g.id IS NULL;

-- ============================================================================
-- Supprimer les anciennes contraintes si elles existent (pour réappliquer proprement)
-- ============================================================================

-- Vérifier si la contrainte existe déjà
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'gamezone'
      AND TABLE_NAME = 'game_packages'
      AND CONSTRAINT_NAME = 'fk_game_packages_game'
);

-- Supprimer si existe
SET @sql = IF(
    @constraint_exists > 0,
    'ALTER TABLE game_packages DROP FOREIGN KEY fk_game_packages_game',
    'SELECT "Constraint fk_game_packages_game does not exist" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier contrainte reward
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'gamezone'
      AND TABLE_NAME = 'game_packages'
      AND CONSTRAINT_NAME = 'fk_package_reward'
);

SET @sql = IF(
    @constraint_exists > 0,
    'ALTER TABLE game_packages DROP FOREIGN KEY fk_package_reward',
    'SELECT "Constraint fk_package_reward does not exist" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Vérifier contrainte game_package_id dans rewards
SET @constraint_exists = (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = 'gamezone'
      AND TABLE_NAME = 'rewards'
      AND CONSTRAINT_NAME = 'fk_reward_package'
);

SET @sql = IF(
    @constraint_exists > 0,
    'ALTER TABLE rewards DROP FOREIGN KEY fk_reward_package',
    'SELECT "Constraint fk_reward_package does not exist" as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- Ajouter les contraintes de clé étrangère
-- ============================================================================

-- 1. game_packages.game_id → games.id
-- RESTRICT empêche la suppression d'un jeu si des packages l'utilisent
ALTER TABLE game_packages
ADD CONSTRAINT fk_game_packages_game
FOREIGN KEY (game_id) 
REFERENCES games(id)
ON DELETE RESTRICT
ON UPDATE CASCADE;

-- 2. game_packages.reward_id → rewards.id
-- SET NULL si la récompense est supprimée (package reste utilisable)
ALTER TABLE game_packages
ADD CONSTRAINT fk_package_reward
FOREIGN KEY (reward_id)
REFERENCES rewards(id)
ON DELETE SET NULL
ON UPDATE CASCADE;

-- 3. rewards.game_package_id → game_packages.id
-- CASCADE pour supprimer la récompense si le package est supprimé
ALTER TABLE rewards
ADD CONSTRAINT fk_reward_package
FOREIGN KEY (game_package_id)
REFERENCES game_packages(id)
ON DELETE CASCADE
ON UPDATE CASCADE;

-- ============================================================================
-- Vérification finale
-- ============================================================================

-- Afficher toutes les contraintes créées
SELECT 
    tc.TABLE_NAME,
    tc.CONSTRAINT_NAME,
    kcu.REFERENCED_TABLE_NAME,
    kcu.REFERENCED_COLUMN_NAME
FROM information_schema.TABLE_CONSTRAINTS tc
JOIN information_schema.KEY_COLUMN_USAGE kcu 
    ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME 
    AND tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
WHERE tc.CONSTRAINT_SCHEMA = 'gamezone'
  AND tc.TABLE_NAME IN ('game_packages', 'rewards')
  AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
ORDER BY tc.TABLE_NAME, tc.CONSTRAINT_NAME;

-- ============================================================================
-- Messages de confirmation
-- ============================================================================

SELECT 
    '✅ Contraintes de clé étrangère ajoutées avec succès!' as status,
    'Les packages ne peuvent plus être créés sans jeu valide' as protection_1,
    'Les jeux utilisés par des packages ne peuvent plus être supprimés' as protection_2,
    'L''intégrité référentielle est garantie au niveau base de données' as protection_3;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
