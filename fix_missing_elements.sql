-- ============================================================================
-- CORRECTIONS DES ÉLÉMENTS MANQUANTS
-- ============================================================================

USE `gamezone`;

-- 1. Ajouter la colonne virtuelle remaining_minutes si elle n'existe pas
ALTER TABLE game_sessions 
  ADD COLUMN IF NOT EXISTS remaining_minutes INT GENERATED ALWAYS AS (total_minutes - used_minutes) VIRTUAL;

-- 2. Recréer les vues manquantes

-- Vue: Statistiques des jeux
CREATE OR REPLACE VIEW game_stats AS
SELECT 
  g.id,
  g.name,
  g.slug,
  g.category,
  g.is_active,
  COUNT(DISTINCT p.id) as total_purchases,
  COUNT(DISTINCT p.user_id) as unique_players,
  COALESCE(SUM(p.price), 0) as total_revenue,
  COALESCE(SUM(p.duration_minutes), 0) as total_minutes_sold,
  COALESCE(AVG(p.price), 0) as avg_purchase_price,
  COUNT(DISTINCT CASE WHEN p.payment_status = 'completed' THEN p.id END) as completed_purchases,
  COUNT(DISTINCT CASE WHEN p.payment_status = 'pending' THEN p.id END) as pending_purchases
FROM games g
LEFT JOIN purchases p ON g.id = p.game_id
GROUP BY g.id, g.name, g.slug, g.category, g.is_active;

-- Vue: Statistiques des packages
CREATE OR REPLACE VIEW package_stats AS
SELECT 
  pkg.id,
  pkg.name,
  g.name as game_name,
  g.slug as game_slug,
  pkg.duration_minutes,
  pkg.price,
  pkg.points_earned,
  pkg.is_active,
  COUNT(DISTINCT p.id) as total_purchases,
  COALESCE(SUM(p.price), 0) as total_revenue
FROM game_packages pkg
INNER JOIN games g ON pkg.game_id = g.id
LEFT JOIN purchases p ON pkg.id = p.package_id
GROUP BY pkg.id, pkg.name, g.name, g.slug, pkg.duration_minutes, pkg.price, pkg.points_earned, pkg.is_active;

-- Vue: Sessions actives avec toutes les informations nécessaires
CREATE OR REPLACE VIEW active_sessions AS
SELECT 
  s.id,
  s.user_id,
  u.username,
  u.avatar_url,
  u.level,
  u.points,
  s.game_id,
  g.name as game_name,
  g.slug as game_slug,
  g.image_url as game_image,
  s.total_minutes,
  s.used_minutes,
  s.total_minutes - s.used_minutes as remaining_minutes,
  ROUND(((s.used_minutes * 100.0) / s.total_minutes), 2) as progress_percent,
  s.status,
  s.started_at,
  s.paused_at,
  s.expires_at,
  s.purchase_id,
  p.price,
  p.payment_status
FROM game_sessions s
INNER JOIN users u ON s.user_id = u.id
INNER JOIN games g ON s.game_id = g.id
INNER JOIN purchases p ON s.purchase_id = p.id
WHERE s.status IN ('pending', 'active', 'paused');

-- 3. Configurer KkiaPay dans payment_methods
INSERT INTO payment_methods (
  name, 
  slug, 
  provider, 
  requires_online_payment, 
  auto_confirm, 
  is_active, 
  display_order, 
  instructions, 
  created_at, 
  updated_at
) VALUES (
  'Mobile Money (KkiaPay)', 
  'kkiapay', 
  'kkiapay', 
  1, 
  1, 
  1, 
  3, 
  'Payez avec MTN Mobile Money, Orange Money, Moov Money ou Wave via KkiaPay. Paiement sécurisé et instantané.', 
  NOW(), 
  NOW()
) ON DUPLICATE KEY UPDATE 
  provider = 'kkiapay',
  is_active = 1,
  updated_at = NOW();

-- Configurer les autres méthodes Mobile Money pour utiliser KkiaPay
UPDATE payment_methods 
SET provider = 'kkiapay', is_active = 1 
WHERE slug IN ('mtn_momo', 'orange_money', 'moov_money', 'wave');

-- Ajouter ces méthodes si elles n'existent pas
INSERT INTO payment_methods (name, slug, provider, requires_online_payment, auto_confirm, is_active, display_order, instructions, created_at, updated_at) VALUES
  ('MTN Mobile Money', 'mtn_momo', 'kkiapay', 1, 1, 1, 4, 'Payez avec votre compte MTN Mobile Money via KkiaPay.', NOW(), NOW()),
  ('Orange Money', 'orange_money', 'kkiapay', 1, 1, 1, 5, 'Payez avec votre compte Orange Money via KkiaPay.', NOW(), NOW()),
  ('Moov Money', 'moov_money', 'kkiapay', 1, 1, 1, 6, 'Payez avec votre compte Moov Money via KkiaPay.', NOW(), NOW()),
  ('Wave', 'wave', 'kkiapay', 1, 1, 1, 7, 'Payez avec votre compte Wave via KkiaPay.', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  provider = 'kkiapay',
  is_active = 1,
  updated_at = NOW();

-- 4. Vérification finale
SELECT 'Corrections appliquées avec succès!' as Status;
