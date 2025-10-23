-- ============================================================================
-- Migration: Système de réservations de jeux avec frais par jeu
-- Date: 2025-10-17
-- Description: Ajoute la capacité de réserver un jeu à une date précise,
--              avec un frais de réservation configurable par jeu.
-- ============================================================================

USE `gamezone`;

-- ----------------------------------------------------------------------------
-- 1) Colonnes sur games: is_reservable, reservation_fee
-- ----------------------------------------------------------------------------
ALTER TABLE games
  ADD COLUMN IF NOT EXISTS is_reservable TINYINT(1) NOT NULL DEFAULT 0 AFTER base_price,
  ADD COLUMN IF NOT EXISTS reservation_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER is_reservable;

-- Index utile pour filtrer les jeux réservable
CREATE INDEX IF NOT EXISTS idx_games_reservable ON games (is_reservable);

-- ----------------------------------------------------------------------------
-- 2) Table: game_reservations
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS game_reservations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  game_id INT NOT NULL,
  purchase_id INT NULL,

  scheduled_start DATETIME NOT NULL,
  scheduled_end DATETIME NOT NULL,
  duration_minutes INT NOT NULL,

  base_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Prix du package (hors frais de paiement) + frais de réservation',
  reservation_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Montant total payé (incluant frais de paiement) si connu',
  currency VARCHAR(3) NOT NULL DEFAULT 'XOF',

  status ENUM('pending_payment','paid','cancelled','completed','no_show') NOT NULL DEFAULT 'pending_payment',
  notes TEXT NULL,

  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,

  CONSTRAINT fk_res_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_res_game FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
  CONSTRAINT fk_res_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE SET NULL,

  UNIQUE KEY uq_res_purchase (purchase_id),
  INDEX idx_res_user (user_id),
  INDEX idx_res_game_time (game_id, scheduled_start, scheduled_end),
  INDEX idx_res_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Réservations de jeux à des créneaux précis';

-- ----------------------------------------------------------------------------
-- 3) Données par défaut/compatibilité
-- ----------------------------------------------------------------------------
UPDATE games SET is_reservable = 0 WHERE is_reservable IS NULL;
UPDATE games SET reservation_fee = 0.00 WHERE reservation_fee IS NULL;

-- FIN DE LA MIGRATION
