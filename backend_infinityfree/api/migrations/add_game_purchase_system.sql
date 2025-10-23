-- Migration: Système complet d'achat de temps de jeu avec paiement
-- Date: 2025-01-15
-- Description: Permet aux utilisateurs d'acheter du temps de jeu, gagner des points
--              L'admin configure tout: jeux, packages, tarifs, points, méthodes de paiement

USE `gamezone`;

-- ============================================================================
-- TABLE: games
-- Jeux disponibles à l'achat (configurés par l'admin)
-- ============================================================================
CREATE TABLE IF NOT EXISTS games (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(200) NOT NULL UNIQUE,
  description TEXT NULL,
  short_description VARCHAR(500) NULL,
  image_url VARCHAR(500) NULL,
  thumbnail_url VARCHAR(500) NULL,
  category ENUM('action', 'adventure', 'sports', 'racing', 'strategy', 'rpg', 'fighting', 'simulation', 'vr', 'retro', 'other') NOT NULL DEFAULT 'other',
  platform VARCHAR(100) NULL COMMENT 'PC, PS5, Xbox, VR, etc.',
  min_players INT NOT NULL DEFAULT 1,
  max_players INT NOT NULL DEFAULT 1,
  age_rating VARCHAR(20) NULL COMMENT 'PEGI 3, 7, 12, 16, 18',
  
  -- Configuration des points
  points_per_hour INT NOT NULL DEFAULT 10 COMMENT 'Points gagnés par heure de jeu',
  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Prix de base par heure (en devise locale)',
  
  -- Disponibilité
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  display_order INT NOT NULL DEFAULT 0,
  
  -- Métadonnées
  created_by INT NULL COMMENT 'Admin qui a créé le jeu',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  
  CONSTRAINT fk_games_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_active_featured (is_active, is_featured),
  INDEX idx_category (category),
  INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Catalogue des jeux disponibles';

-- ============================================================================
-- TABLE: game_packages
-- Packages de temps de jeu avec tarifs spéciaux (configurés par l'admin)
-- ============================================================================
CREATE TABLE IF NOT EXISTS game_packages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  game_id INT NOT NULL,
  name VARCHAR(150) NOT NULL COMMENT 'Ex: "1 heure", "Pack soirée 3h", "Week-end illimité"',
  
  -- Durée et tarification
  duration_minutes INT NOT NULL COMMENT 'Durée en minutes',
  price DECIMAL(10,2) NOT NULL COMMENT 'Prix du package',
  original_price DECIMAL(10,2) NULL COMMENT 'Prix barré pour afficher la promotion',
  
  -- Points et bonus
  points_earned INT NOT NULL COMMENT 'Points totaux gagnés avec ce package (calculé automatiquement ou personnalisé)',
  bonus_multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00 COMMENT 'Multiplicateur de points bonus (1.5 = +50%)',
  
  -- Options spéciales
  is_promotional TINYINT(1) NOT NULL DEFAULT 0,
  promotional_label VARCHAR(100) NULL COMMENT 'Ex: "PROMO -20%", "POPULAIRE", "BEST VALUE"',
  
  -- Limitations
  max_purchases_per_user INT NULL COMMENT 'Limite d\'achats par utilisateur (NULL = illimité)',
  available_from DATETIME NULL COMMENT 'Date de début de disponibilité',
  available_until DATETIME NULL COMMENT 'Date de fin de disponibilité',
  
  -- État
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT NOT NULL DEFAULT 0,
  
  -- Métadonnées
  created_by INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  
  CONSTRAINT fk_packages_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  CONSTRAINT fk_packages_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_game_active (game_id, is_active),
  INDEX idx_promotional (is_promotional)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Packages de temps de jeu avec tarifs';

-- ============================================================================
-- TABLE: payment_methods
-- Méthodes de paiement configurées par l'admin
-- ============================================================================
CREATE TABLE IF NOT EXISTS payment_methods (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL COMMENT 'Ex: "Carte bancaire", "PayPal", "Espèces", "Mobile Money"',
  slug VARCHAR(100) NOT NULL UNIQUE,
  provider VARCHAR(100) NULL COMMENT 'stripe, paypal, mtn_momo, orange_money, etc.',
  
  -- Configuration API
  api_key_public VARCHAR(500) NULL COMMENT 'Clé publique API (si applicable)',
  api_key_secret VARCHAR(500) NULL COMMENT 'Clé secrète API (chiffrée)',
  api_endpoint VARCHAR(500) NULL COMMENT 'URL de l\'API du provider',
  webhook_secret VARCHAR(500) NULL COMMENT 'Secret pour vérifier les webhooks',
  
  -- Options
  requires_online_payment TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = paiement en personne (espèces), 1 = paiement en ligne',
  auto_confirm TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Confirmation automatique ou manuelle par admin',
  
  -- Frais
  fee_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais en % du montant',
  fee_fixed DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Frais fixes',
  
  -- Disponibilité
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  display_order INT NOT NULL DEFAULT 0,
  
  -- Instructions pour l'utilisateur
  instructions TEXT NULL COMMENT 'Instructions de paiement affichées à l\'utilisateur',
  
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  
  INDEX idx_active (is_active),
  INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Méthodes de paiement disponibles';

-- ============================================================================
-- TABLE: purchases
-- Achats effectués par les utilisateurs
-- ============================================================================
CREATE TABLE IF NOT EXISTS purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  game_id INT NOT NULL,
  package_id INT NULL COMMENT 'Package acheté (NULL si achat personnalisé)',
  
  -- Détails de l'achat (snapshot au moment de l'achat)
  game_name VARCHAR(200) NOT NULL,
  package_name VARCHAR(150) NULL,
  duration_minutes INT NOT NULL,
  
  -- Montant
  price DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) NOT NULL DEFAULT 'XOF' COMMENT 'Code devise ISO 4217',
  
  -- Points
  points_earned INT NOT NULL DEFAULT 0,
  points_credited TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Points déjà crédités ?',
  
  -- Paiement
  payment_method_id INT NULL,
  payment_method_name VARCHAR(100) NULL,
  payment_status ENUM('pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
  payment_reference VARCHAR(255) NULL COMMENT 'Référence transaction externe',
  payment_details JSON NULL COMMENT 'Détails du paiement (données du provider)',
  
  -- Confirmation
  confirmed_by INT NULL COMMENT 'Admin qui a confirmé (si paiement manuel)',
  confirmed_at DATETIME NULL,
  
  -- Session de jeu
  session_status ENUM('pending', 'active', 'completed', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
  
  -- Métadonnées
  notes TEXT NULL COMMENT 'Notes admin',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  
  CONSTRAINT fk_purchases_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchases_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  CONSTRAINT fk_purchases_package FOREIGN KEY (package_id) REFERENCES game_packages(id) ON DELETE SET NULL,
  CONSTRAINT fk_purchases_payment_method FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE SET NULL,
  CONSTRAINT fk_purchases_confirmer FOREIGN KEY (confirmed_by) REFERENCES users(id) ON DELETE SET NULL,
  
  INDEX idx_user_status (user_id, payment_status),
  INDEX idx_payment_status (payment_status),
  INDEX idx_payment_reference (payment_reference),
  INDEX idx_session_status (session_status),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Achats de temps de jeu';

-- ============================================================================
-- TABLE: game_sessions
-- Sessions de jeu actives (temps utilisé)
-- ============================================================================
CREATE TABLE IF NOT EXISTS game_sessions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  user_id INT NOT NULL,
  game_id INT NOT NULL,
  
  -- Temps alloué
  total_minutes INT NOT NULL,
  used_minutes INT NOT NULL DEFAULT 0,
  remaining_minutes INT GENERATED ALWAYS AS (total_minutes - used_minutes) VIRTUAL,
  
  -- État de la session
  status ENUM('pending', 'active', 'paused', 'completed', 'expired', 'cancelled') NOT NULL DEFAULT 'pending',
  
  -- Dates
  started_at DATETIME NULL,
  paused_at DATETIME NULL,
  resumed_at DATETIME NULL,
  completed_at DATETIME NULL,
  expires_at DATETIME NULL COMMENT 'Date d\'expiration du temps acheté',
  
  -- Suivi
  last_activity_at DATETIME NULL,
  
  -- Métadonnées
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  
  CONSTRAINT fk_sessions_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  CONSTRAINT fk_sessions_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_sessions_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  
  INDEX idx_user_status (user_id, status),
  INDEX idx_purchase (purchase_id),
  INDEX idx_status (status),
  INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Sessions de jeu actives';

-- ============================================================================
-- TABLE: session_activities
-- Historique d'activité des sessions (logs détaillés)
-- ============================================================================
CREATE TABLE IF NOT EXISTS session_activities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  session_id INT NOT NULL,
  activity_type ENUM('start', 'pause', 'resume', 'complete', 'expire', 'cancel', 'time_update') NOT NULL,
  minutes_used INT NOT NULL DEFAULT 0,
  description VARCHAR(500) NULL,
  created_by INT NULL COMMENT 'Admin qui a effectué l\'action (NULL = automatique)',
  created_at DATETIME NOT NULL,
  
  CONSTRAINT fk_activities_session FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
  CONSTRAINT fk_activities_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  
  INDEX idx_session (session_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Historique des activités de session';

-- ============================================================================
-- TABLE: payment_transactions
-- Transactions de paiement (logs détaillés)
-- ============================================================================
CREATE TABLE IF NOT EXISTS payment_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  purchase_id INT NOT NULL,
  transaction_type ENUM('charge', 'refund', 'chargeback', 'adjustment') NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  currency VARCHAR(3) NOT NULL DEFAULT 'XOF',
  
  -- Détails du provider
  provider_transaction_id VARCHAR(255) NULL,
  provider_status VARCHAR(100) NULL,
  provider_response JSON NULL,
  
  -- Métadonnées
  notes TEXT NULL,
  created_at DATETIME NOT NULL,
  
  CONSTRAINT fk_transactions_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
  
  INDEX idx_purchase (purchase_id),
  INDEX idx_provider_transaction (provider_transaction_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Transactions de paiement';

-- ============================================================================
-- DONNÉES PAR DÉFAUT
-- ============================================================================

-- Méthodes de paiement par défaut
INSERT INTO payment_methods (name, slug, provider, requires_online_payment, auto_confirm, is_active, display_order, instructions, created_at, updated_at) VALUES
  ('Espèces', 'cash', NULL, 0, 0, 1, 1, 'Payez directement à la réception. Un membre de l\'équipe confirmera votre paiement.', NOW(), NOW()),
  ('Carte Bancaire', 'card', 'stripe', 1, 1, 0, 2, 'Paiement sécurisé par carte bancaire via Stripe.', NOW(), NOW()),
  ('PayPal', 'paypal', 'paypal', 1, 1, 0, 3, 'Payez avec votre compte PayPal.', NOW(), NOW()),
  ('Mobile Money MTN', 'mtn_momo', 'mtn', 1, 0, 0, 4, 'Composez *XXX# et suivez les instructions. Entrez votre numéro de téléphone MTN.', NOW(), NOW()),
  ('Orange Money', 'orange_money', 'orange', 1, 0, 0, 5, 'Composez #XXX# et suivez les instructions. Entrez votre numéro Orange.', NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  name = VALUES(name), 
  instructions = VALUES(instructions), 
  updated_at = NOW();

-- Jeux de démonstration
INSERT INTO games (name, slug, description, short_description, image_url, category, platform, min_players, max_players, age_rating, points_per_hour, base_price, is_active, is_featured, display_order, created_at, updated_at) VALUES
  ('FIFA 2024', 'fifa-2024', 'Le jeu de football ultime avec tous les championnats et équipes officiels. Mode carrière, Ultimate Team, et bien plus encore.', 'Jeu de football avec tous les championnats officiels', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800', 'sports', 'PS5, Xbox Series X', 1, 4, 'PEGI 3', 15, 5.00, 1, 1, 1, NOW(), NOW()),
  ('Call of Duty: Modern Warfare III', 'cod-mw3', 'Action FPS intense avec campagne solo, multijoueur compétitif et mode Zombies. Graphismes de nouvelle génération.', 'FPS action multijoueur intense', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800', 'action', 'PS5, Xbox Series X, PC', 1, 12, 'PEGI 18', 20, 6.00, 1, 1, 2, NOW(), NOW()),
  ('Grand Theft Auto V', 'gta-5', 'Monde ouvert avec trois protagonistes. Mode histoire épique et GTA Online pour des aventures multijoueurs infinies.', 'Action monde ouvert avec GTA Online', 'https://images.unsplash.com/photo-1556438064-2d7646166914?w=800', 'action', 'PS5, Xbox Series X, PC', 1, 30, 'PEGI 18', 18, 5.50, 1, 1, 3, NOW(), NOW()),
  ('Forza Horizon 5', 'forza-horizon-5', 'Course arcade en monde ouvert au Mexique. Des centaines de voitures et des paysages époustouflants.', 'Course arcade monde ouvert', 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?w=800', 'racing', 'Xbox Series X, PC', 1, 12, 'PEGI 3', 12, 4.50, 1, 0, 4, NOW(), NOW()),
  ('Street Fighter 6', 'street-fighter-6', 'Le roi des jeux de combat est de retour avec de nouveaux personnages, modes de jeu et mécaniques innovantes.', 'Jeu de combat compétitif', 'https://images.unsplash.com/photo-1598550476439-6847785fcea6?w=800', 'fighting', 'PS5, Xbox Series X, PC', 2, 2, 'PEGI 12', 15, 4.00, 1, 0, 5, NOW(), NOW()),
  ('Beat Saber VR', 'beat-saber-vr', 'Rythmez au son de la musique en tranchant des blocs avec vos sabres laser. Expérience VR immersive et fun.', 'Rythme VR immersif', 'https://images.unsplash.com/photo-1617802690992-15d93263d3a9?w=800', 'vr', 'Meta Quest 2, PSVR2', 1, 1, 'PEGI 7', 25, 7.00, 1, 1, 6, NOW(), NOW()),
  ('Pac-Man Championship Edition', 'pacman-ce', 'Version modernisée du classique avec graphismes néon et gameplay frénétique. Nostalgie garantie !', 'Arcade rétro modernisé', 'https://images.unsplash.com/photo-1493711662062-fa541adb3fc8?w=800', 'retro', 'Arcade', 1, 2, 'PEGI 3', 10, 3.00, 1, 0, 7, NOW(), NOW()),
  ('Mortal Kombat 11', 'mortal-kombat-11', 'Combat brutal avec des fatalities spectaculaires. Mode histoire cinématographique et multijoueur compétitif.', 'Combat brutal et spectaculaire', 'https://images.unsplash.com/photo-1552820728-8b83bb6b773f?w=800', 'fighting', 'PS5, Xbox Series X, PC', 1, 2, 'PEGI 18', 15, 4.50, 1, 0, 8, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  description = VALUES(description),
  short_description = VALUES(short_description),
  points_per_hour = VALUES(points_per_hour),
  base_price = VALUES(base_price),
  updated_at = NOW();

-- Packages pour FIFA 2024
INSERT INTO game_packages (game_id, name, duration_minutes, price, original_price, points_earned, bonus_multiplier, is_promotional, promotional_label, is_active, display_order, created_at, updated_at) VALUES
  ((SELECT id FROM games WHERE slug = 'fifa-2024'), '30 minutes', 30, 2.50, NULL, 8, 1.00, 0, NULL, 1, 1, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'fifa-2024'), '1 heure', 60, 5.00, NULL, 15, 1.00, 0, 'POPULAIRE', 1, 2, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'fifa-2024'), 'Pack Soirée - 3 heures', 180, 12.00, 15.00, 50, 1.10, 1, 'MEILLEURE OFFRE -20%', 1, 3, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'fifa-2024'), 'Journée complète - 6 heures', 360, 20.00, 30.00, 120, 1.30, 1, 'PROMO -33%', 1, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  price = VALUES(price),
  original_price = VALUES(original_price),
  points_earned = VALUES(points_earned),
  updated_at = NOW();

-- Packages pour Call of Duty MW3
INSERT INTO game_packages (game_id, name, duration_minutes, price, original_price, points_earned, bonus_multiplier, is_promotional, promotional_label, is_active, display_order, created_at, updated_at) VALUES
  ((SELECT id FROM games WHERE slug = 'cod-mw3'), '30 minutes', 30, 3.00, NULL, 10, 1.00, 0, NULL, 1, 1, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'cod-mw3'), '1 heure', 60, 6.00, NULL, 20, 1.00, 0, 'POPULAIRE', 1, 2, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'cod-mw3'), 'Pack Gaming - 2 heures', 120, 10.00, 12.00, 45, 1.15, 1, '-15%', 1, 3, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'cod-mw3'), 'Marathon - 5 heures', 300, 22.00, 30.00, 130, 1.30, 1, 'BEST VALUE -27%', 1, 4, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  price = VALUES(price),
  original_price = VALUES(original_price),
  points_earned = VALUES(points_earned),
  updated_at = NOW();

-- Packages pour Beat Saber VR
INSERT INTO game_packages (game_id, name, duration_minutes, price, original_price, points_earned, bonus_multiplier, is_promotional, promotional_label, is_active, display_order, created_at, updated_at) VALUES
  ((SELECT id FROM games WHERE slug = 'beat-saber-vr'), 'Découverte - 15 minutes', 15, 4.00, NULL, 7, 1.00, 0, NULL, 1, 1, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'beat-saber-vr'), '30 minutes', 30, 7.00, NULL, 13, 1.00, 0, 'POPULAIRE', 1, 2, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'beat-saber-vr'), 'Session VR - 1 heure', 60, 12.00, 14.00, 30, 1.20, 1, 'PROMO VR', 1, 3, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  price = VALUES(price),
  original_price = VALUES(original_price),
  points_earned = VALUES(points_earned),
  updated_at = NOW();

-- Packages pour GTA V
INSERT INTO game_packages (game_id, name, duration_minutes, price, original_price, points_earned, bonus_multiplier, is_promotional, promotional_label, is_active, display_order, created_at, updated_at) VALUES
  ((SELECT id FROM games WHERE slug = 'gta-5'), '1 heure', 60, 5.50, NULL, 18, 1.00, 0, NULL, 1, 1, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'gta-5'), 'Après-midi - 3 heures', 180, 14.00, 16.50, 60, 1.10, 1, '-15%', 1, 2, NOW(), NOW()),
  ((SELECT id FROM games WHERE slug = 'gta-5'), 'Journée GTA - 8 heures', 480, 30.00, 44.00, 180, 1.25, 1, 'SUPER PROMO -32%', 1, 3, NOW(), NOW())
ON DUPLICATE KEY UPDATE 
  price = VALUES(price),
  original_price = VALUES(original_price),
  points_earned = VALUES(points_earned),
  updated_at = NOW();

-- ============================================================================
-- VUES UTILES
-- ============================================================================

-- Vue: Statistiques des jeux
CREATE OR REPLACE VIEW game_stats AS
SELECT 
  g.id,
  g.name,
  g.slug,
  COUNT(DISTINCT p.id) as total_purchases,
  COUNT(DISTINCT p.user_id) as unique_players,
  SUM(p.price) as total_revenue,
  SUM(p.duration_minutes) as total_minutes_sold,
  AVG(p.price) as avg_purchase_price,
  COUNT(DISTINCT CASE WHEN p.payment_status = 'completed' THEN p.id END) as completed_purchases,
  COUNT(DISTINCT CASE WHEN p.payment_status = 'pending' THEN p.id END) as pending_purchases
FROM games g
LEFT JOIN purchases p ON g.id = p.game_id
GROUP BY g.id, g.name, g.slug;

-- Vue: Statistiques des packages
CREATE OR REPLACE VIEW package_stats AS
SELECT 
  pkg.id,
  pkg.name,
  g.name as game_name,
  pkg.duration_minutes,
  pkg.price,
  pkg.points_earned,
  COUNT(DISTINCT p.id) as total_purchases,
  SUM(p.price) as total_revenue
FROM game_packages pkg
INNER JOIN games g ON pkg.game_id = g.id
LEFT JOIN purchases p ON pkg.id = p.package_id
GROUP BY pkg.id, pkg.name, g.name, pkg.duration_minutes, pkg.price, pkg.points_earned;

-- Vue: Sessions actives
CREATE OR REPLACE VIEW active_sessions AS
SELECT 
  s.id,
  s.user_id,
  u.username,
  g.name as game_name,
  s.total_minutes,
  s.used_minutes,
  s.remaining_minutes,
  s.status,
  s.started_at,
  s.expires_at,
  p.price,
  p.payment_status
FROM game_sessions s
INNER JOIN users u ON s.user_id = u.id
INNER JOIN games g ON s.game_id = g.id
INNER JOIN purchases p ON s.purchase_id = p.id
WHERE s.status IN ('pending', 'active', 'paused');

-- Vue: Revenus par méthode de paiement
CREATE OR REPLACE VIEW revenue_by_payment_method AS
SELECT 
  pm.name as payment_method,
  COUNT(p.id) as total_transactions,
  SUM(p.price) as total_revenue,
  AVG(p.price) as avg_transaction,
  COUNT(CASE WHEN p.payment_status = 'completed' THEN 1 END) as completed_count,
  COUNT(CASE WHEN p.payment_status = 'pending' THEN 1 END) as pending_count,
  COUNT(CASE WHEN p.payment_status = 'failed' THEN 1 END) as failed_count
FROM purchases p
INNER JOIN payment_methods pm ON p.payment_method_id = pm.id
GROUP BY pm.name;

-- ============================================================================
-- FIN DE LA MIGRATION
-- ============================================================================
