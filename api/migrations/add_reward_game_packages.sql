-- Migration: Système de packages de jeux payables en points via récompenses
-- Date: 2025-10-20
-- Description: Permet aux admins de créer des récompenses qui sont des packages de jeux
--              payables uniquement avec des points (pas d'argent)

USE `gamezone`;

-- ============================================================================
-- Ajouter des colonnes à game_packages pour supporter le paiement en points
-- ============================================================================
ALTER TABLE game_packages 
  ADD COLUMN is_points_only TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Package payable uniquement en points (pas d\'argent)' AFTER is_active,
  ADD COLUMN points_cost INT NULL COMMENT 'Coût en points si is_points_only = 1' AFTER points_earned,
  ADD COLUMN reward_id INT NULL COMMENT 'ID de la récompense liée (si créée via système de récompenses)' AFTER points_cost,
  ADD CONSTRAINT fk_package_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE SET NULL;

-- Index pour les packages payables en points
CREATE INDEX idx_points_only ON game_packages (is_points_only, is_active);

-- ============================================================================
-- Ajouter des colonnes à rewards pour supporter les packages de jeux
-- ============================================================================
ALTER TABLE rewards
  ADD COLUMN reward_type ENUM('physical', 'digital', 'game_package', 'discount', 'other') NOT NULL DEFAULT 'physical' COMMENT 'Type de récompense' AFTER category,
  ADD COLUMN game_package_id INT NULL COMMENT 'ID du package de jeu associé' AFTER reward_type,
  ADD CONSTRAINT fk_reward_package FOREIGN KEY (game_package_id) REFERENCES game_packages(id) ON DELETE CASCADE;

-- Index pour les récompenses de type game_package
CREATE INDEX idx_reward_type ON rewards (reward_type, available);

-- ============================================================================
-- Mettre à jour purchases pour supporter le paiement en points
-- ============================================================================
ALTER TABLE purchases
  ADD COLUMN paid_with_points TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Acheté avec des points au lieu d\'argent' AFTER points_credited,
  ADD COLUMN points_spent INT NOT NULL DEFAULT 0 COMMENT 'Points dépensés pour cet achat' AFTER paid_with_points;

-- Index pour les achats payés en points
CREATE INDEX idx_paid_with_points ON purchases (paid_with_points, user_id);

-- ============================================================================
-- Créer une vue pour les packages payables en points
-- ============================================================================
CREATE OR REPLACE VIEW point_packages AS
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
GROUP BY pkg.id, pkg.game_id, g.name, g.slug, g.image_url, pkg.name, 
         pkg.duration_minutes, pkg.points_cost, pkg.points_earned, pkg.bonus_multiplier,
         pkg.is_promotional, pkg.promotional_label, pkg.max_purchases_per_user,
         pkg.available_from, pkg.available_until, pkg.is_active, pkg.display_order,
         r.id, r.name, r.description, r.image_url, r.category, r.is_featured;

-- ============================================================================
-- Créer une vue pour l'historique des échanges de points
-- ============================================================================
CREATE OR REPLACE VIEW points_redemption_history AS
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
-- Exemples de packages payables en points
-- ============================================================================

-- Package FIFA 30 min - 50 points
INSERT INTO game_packages (
  game_id, name, duration_minutes, price, points_earned, 
  is_points_only, points_cost, is_active, display_order, 
  created_at, updated_at
) VALUES (
  (SELECT id FROM games WHERE slug = 'fifa-2024'), 
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
) ON DUPLICATE KEY UPDATE updated_at = NOW();

SET @fifa_package_id = LAST_INSERT_ID();

-- Créer la récompense associée
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
) ON DUPLICATE KEY UPDATE updated_at = NOW();

-- Lier le package à la récompense
UPDATE game_packages SET reward_id = LAST_INSERT_ID() WHERE id = @fifa_package_id;

-- Package COD 1 heure - 100 points
INSERT INTO game_packages (
  game_id, name, duration_minutes, price, points_earned, 
  is_points_only, points_cost, is_active, display_order, 
  created_at, updated_at
) VALUES (
  (SELECT id FROM games WHERE slug = 'cod-mw3'), 
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
) ON DUPLICATE KEY UPDATE updated_at = NOW();

SET @cod_package_id = LAST_INSERT_ID();

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
) ON DUPLICATE KEY UPDATE updated_at = NOW();

UPDATE game_packages SET reward_id = LAST_INSERT_ID() WHERE id = @cod_package_id;

-- Package Beat Saber VR 30 min - 150 points (VR est plus cher)
INSERT INTO game_packages (
  game_id, name, duration_minutes, price, points_earned, 
  is_points_only, points_cost, is_active, display_order, 
  created_at, updated_at
) VALUES (
  (SELECT id FROM games WHERE slug = 'beat-saber-vr'), 
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
) ON DUPLICATE KEY UPDATE updated_at = NOW();

SET @vr_package_id = LAST_INSERT_ID();

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
) ON DUPLICATE KEY UPDATE updated_at = NOW();

UPDATE game_packages SET reward_id = LAST_INSERT_ID() WHERE id = @vr_package_id;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
