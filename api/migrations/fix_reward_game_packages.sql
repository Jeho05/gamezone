-- Migration: Compléter le système de récompenses (correctif)
-- Date: 2025-10-21
-- Description: Ajouter les colonnes manquantes sans créer de doublons

USE `gamezone`;

-- ============================================================================
-- Modifier reward_type pour ajouter 'game_package'
-- ============================================================================
ALTER TABLE rewards 
  MODIFY COLUMN reward_type ENUM('game_time', 'discount', 'item', 'badge', 'other', 'physical', 'digital', 'game_package') NULL DEFAULT 'other';

-- ============================================================================
-- Ajouter game_package_id à rewards (si n'existe pas)
-- ============================================================================
SET @columnExists = (
  SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'gamezone' 
    AND TABLE_NAME = 'rewards' 
    AND COLUMN_NAME = 'game_package_id'
);

SET @sqlStatement = IF(
  @columnExists = 0,
  'ALTER TABLE rewards ADD COLUMN game_package_id INT NULL COMMENT "ID du package de jeu associé" AFTER reward_type',
  'SELECT "Column game_package_id already exists" as message'
);

PREPARE stmt FROM @sqlStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- Ajouter paid_with_points et points_spent à purchases (si n'existe pas)
-- ============================================================================
SET @columnExists = (
  SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'gamezone' 
    AND TABLE_NAME = 'purchases' 
    AND COLUMN_NAME = 'paid_with_points'
);

SET @sqlStatement = IF(
  @columnExists = 0,
  'ALTER TABLE purchases ADD COLUMN paid_with_points TINYINT(1) NOT NULL DEFAULT 0 COMMENT "Acheté avec des points au lieu d\\"argent" AFTER points_credited',
  'SELECT "Column paid_with_points already exists" as message'
);

PREPARE stmt FROM @sqlStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @columnExists = (
  SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'gamezone' 
    AND TABLE_NAME = 'purchases' 
    AND COLUMN_NAME = 'points_spent'
);

SET @sqlStatement = IF(
  @columnExists = 0,
  'ALTER TABLE purchases ADD COLUMN points_spent INT NOT NULL DEFAULT 0 COMMENT "Points dépensés pour cet achat" AFTER paid_with_points',
  'SELECT "Column points_spent already exists" as message'
);

PREPARE stmt FROM @sqlStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================================
-- Créer les vues (DROP et CREATE pour éviter les erreurs)
-- ============================================================================

DROP VIEW IF EXISTS point_packages;
CREATE VIEW point_packages AS
SELECT 
  pkg.id,
  pkg.game_id,
  g.name as game_name,
  g.slug as game_slug,
  g.image_url as game_image,
  pkg.name as package_name,
  pkg.duration_minutes,
  pkg.points_cost,
  pkg.points_earned,
  pkg.bonus_multiplier,
  pkg.is_promotional,
  pkg.promotional_label,
  pkg.max_purchases_per_user,
  pkg.available_from,
  pkg.available_until,
  pkg.is_active,
  pkg.display_order,
  r.id as reward_id,
  r.name as reward_name,
  r.description as reward_description,
  r.image_url as reward_image,
  r.category as reward_category,
  r.is_featured as reward_featured,
  COUNT(DISTINCT p.id) as total_redemptions,
  COUNT(DISTINCT p.user_id) as unique_users
FROM game_packages pkg
INNER JOIN games g ON pkg.game_id = g.id
LEFT JOIN rewards r ON pkg.reward_id = r.id
LEFT JOIN purchases p ON pkg.id = p.package_id AND p.paid_with_points = 1
WHERE pkg.is_points_only = 1
GROUP BY pkg.id;

DROP VIEW IF EXISTS points_redemption_history;
CREATE VIEW points_redemption_history AS
SELECT 
  p.id as purchase_id,
  p.user_id,
  u.username,
  p.game_id,
  p.game_name,
  p.package_id,
  p.package_name,
  p.duration_minutes,
  p.points_spent,
  p.points_earned,
  p.payment_status,
  p.session_status,
  p.created_at,
  pkg.points_cost,
  r.id as reward_id,
  r.name as reward_name
FROM purchases p
INNER JOIN users u ON p.user_id = u.id
LEFT JOIN game_packages pkg ON p.package_id = pkg.id
LEFT JOIN rewards r ON pkg.reward_id = r.id
WHERE p.paid_with_points = 1
ORDER BY p.created_at DESC;

-- ============================================================================
-- Créer des packages et récompenses d'exemple
-- ============================================================================

-- Package FIFA 30 min - 50 points
INSERT IGNORE INTO game_packages (
  game_id, name, duration_minutes, price, points_earned, 
  is_points_only, points_cost, is_active, display_order, 
  created_at, updated_at
)
SELECT 
  id, 
  'Session Express - 30 min (Points)', 
  30, 
  0.00, 
  5, 
  1, 
  50, 
  1, 
  10, 
  NOW(), 
  NOW()
FROM games 
WHERE slug = 'fifa-2024'
LIMIT 1;

SET @fifa_package_id = (SELECT id FROM game_packages WHERE name = 'Session Express - 30 min (Points)' AND game_id = (SELECT id FROM games WHERE slug = 'fifa-2024' LIMIT 1) LIMIT 1);

INSERT INTO rewards (
  name, description, cost, category, reward_type, 
  game_package_id, available, is_featured, 
  created_at, updated_at
) VALUES (
  'FIFA 2024 - 30 minutes', 
  'Profitez de 30 minutes de jeu sur FIFA 2024 en échangeant vos points de fidélité. Gagnez 5 points bonus en jouant !', 
  50, 
  'gaming', 
  'game_package', 
  @fifa_package_id, 
  1, 
  1, 
  NOW(), 
  NOW()
) ON DUPLICATE KEY UPDATE 
  description = VALUES(description),
  game_package_id = VALUES(game_package_id),
  updated_at = NOW();

UPDATE game_packages SET reward_id = LAST_INSERT_ID() WHERE id = @fifa_package_id;

-- Package COD 1 heure - 100 points
INSERT IGNORE INTO game_packages (
  game_id, name, duration_minutes, price, points_earned, 
  is_points_only, points_cost, is_active, display_order, 
  created_at, updated_at
)
SELECT 
  id, 
  'Session Gaming - 1h (Points)', 
  60, 
  0.00, 
  10, 
  1, 
  100, 
  1, 
  10, 
  NOW(), 
  NOW()
FROM games 
WHERE slug = 'cod-mw3'
LIMIT 1;

SET @cod_package_id = (SELECT id FROM game_packages WHERE name = 'Session Gaming - 1h (Points)' AND game_id = (SELECT id FROM games WHERE slug = 'cod-mw3' LIMIT 1) LIMIT 1);

INSERT INTO rewards (
  name, description, cost, category, reward_type, 
  game_package_id, available, is_featured, 
  created_at, updated_at
) VALUES (
  'Call of Duty MW3 - 1 heure', 
  'Une heure d\'action intense sur COD Modern Warfare 3. Échangez vos points et gagnez-en 10 de plus en jouant !', 
  100, 
  'gaming', 
  'game_package', 
  @cod_package_id, 
  1, 
  1, 
  NOW(), 
  NOW()
) ON DUPLICATE KEY UPDATE 
  description = VALUES(description),
  game_package_id = VALUES(game_package_id),
  updated_at = NOW();

UPDATE game_packages SET reward_id = LAST_INSERT_ID() WHERE id = @cod_package_id;

-- Package Beat Saber VR 30 min - 150 points
INSERT IGNORE INTO game_packages (
  game_id, name, duration_minutes, price, points_earned, 
  is_points_only, points_cost, is_active, display_order, 
  created_at, updated_at
)
SELECT 
  id, 
  'Expérience VR - 30 min (Points)', 
  30, 
  0.00, 
  15, 
  1, 
  150, 
  1, 
  10, 
  NOW(), 
  NOW()
FROM games 
WHERE slug = 'beat-saber-vr'
LIMIT 1;

SET @vr_package_id = (SELECT id FROM game_packages WHERE name = 'Expérience VR - 30 min (Points)' AND game_id = (SELECT id FROM games WHERE slug = 'beat-saber-vr' LIMIT 1) LIMIT 1);

INSERT INTO rewards (
  name, description, cost, category, reward_type, 
  game_package_id, available, is_featured, 
  created_at, updated_at
) VALUES (
  'Beat Saber VR - 30 minutes', 
  'Plongez dans l\'univers VR avec Beat Saber. 30 minutes d\'immersion totale en échangeant vos points. +15 points bonus !', 
  150, 
  'gaming', 
  'game_package', 
  @vr_package_id, 
  1, 
  1, 
  NOW(), 
  NOW()
) ON DUPLICATE KEY UPDATE 
  description = VALUES(description),
  game_package_id = VALUES(game_package_id),
  updated_at = NOW();

UPDATE game_packages SET reward_id = LAST_INSERT_ID() WHERE id = @vr_package_id;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
