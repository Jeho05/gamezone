-- Migration: Système de gamification complet (badges, niveaux, règles de points)
-- Date: 2025-01-14

USE `gamezone`;

-- Table des badges disponibles
CREATE TABLE IF NOT EXISTS badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(255) NULL,
  category ENUM('points', 'activity', 'social', 'achievement', 'special') NOT NULL DEFAULT 'achievement',
  requirement_type ENUM('points_total', 'points_earned', 'days_active', 'games_played', 'events_attended', 'friends_referred', 'login_streak', 'special') NOT NULL,
  requirement_value INT NOT NULL,
  rarity ENUM('common', 'rare', 'epic', 'legendary') NOT NULL DEFAULT 'common',
  points_reward INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des badges obtenus par les utilisateurs
CREATE TABLE IF NOT EXISTS user_badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  earned_at DATETIME NOT NULL,
  CONSTRAINT fk_ub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
  UNIQUE KEY unique_user_badge (user_id, badge_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des niveaux de progression
CREATE TABLE IF NOT EXISTS levels (
  id INT AUTO_INCREMENT PRIMARY KEY,
  level_number INT NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  points_required INT NOT NULL,
  points_bonus INT NOT NULL DEFAULT 0,
  color VARCHAR(20) NULL,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table des règles de points (pour attribution automatique)
CREATE TABLE IF NOT EXISTS points_rules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  action_type ENUM('game_played', 'event_attended', 'tournament_win', 'tournament_participate', 'friend_referred', 'daily_login', 'profile_complete', 'first_purchase', 'review_written', 'share_social') NOT NULL UNIQUE,
  points_amount INT NOT NULL,
  description VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour tracker les streaks de connexion
CREATE TABLE IF NOT EXISTS login_streaks (
  user_id INT PRIMARY KEY,
  current_streak INT NOT NULL DEFAULT 0,
  longest_streak INT NOT NULL DEFAULT 0,
  last_login_date DATE NOT NULL,
  CONSTRAINT fk_ls_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour les multiplicateurs de bonus actifs
CREATE TABLE IF NOT EXISTS bonus_multipliers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  multiplier DECIMAL(3,2) NOT NULL DEFAULT 1.00,
  reason VARCHAR(255) NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_bm_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table pour les statistiques utilisateur détaillées
CREATE TABLE IF NOT EXISTS user_stats (
  user_id INT PRIMARY KEY,
  games_played INT NOT NULL DEFAULT 0,
  events_attended INT NOT NULL DEFAULT 0,
  tournaments_won INT NOT NULL DEFAULT 0,
  tournaments_participated INT NOT NULL DEFAULT 0,
  friends_referred INT NOT NULL DEFAULT 0,
  total_points_earned INT NOT NULL DEFAULT 0,
  total_points_spent INT NOT NULL DEFAULT 0,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_us_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insertion des niveaux de progression
INSERT INTO levels (level_number, name, points_required, points_bonus, color, created_at) VALUES
  (1, 'Novice', 0, 0, '#808080', NOW()),
  (2, 'Joueur', 100, 50, '#CD7F32', NOW()),
  (3, 'Passionné', 300, 100, '#C0C0C0', NOW()),
  (4, 'Expert', 600, 150, '#FFD700', NOW()),
  (5, 'Maître', 1000, 250, '#E5E4E2', NOW()),
  (6, 'Champion', 1500, 400, '#50C878', NOW()),
  (7, 'Légende', 2500, 600, '#9966CC', NOW()),
  (8, 'Élite', 4000, 1000, '#FF6347', NOW()),
  (9, 'Titan', 6000, 1500, '#00CED1', NOW()),
  (10, 'Dieu du Gaming', 10000, 2500, '#FF00FF', NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), points_required = VALUES(points_required), points_bonus = VALUES(points_bonus), color = VALUES(color);

-- Insertion des règles de points par défaut
INSERT INTO points_rules (action_type, points_amount, description, is_active, created_at, updated_at) VALUES
  ('game_played', 10, 'Points pour chaque partie jouée', 1, NOW(), NOW()),
  ('event_attended', 50, 'Points pour participation à un événement', 1, NOW(), NOW()),
  ('tournament_participate', 100, 'Points pour participation à un tournoi', 1, NOW(), NOW()),
  ('tournament_win', 500, 'Points pour victoire dans un tournoi', 1, NOW(), NOW()),
  ('friend_referred', 200, 'Points pour parrainage d\'un ami', 1, NOW(), NOW()),
  ('daily_login', 5, 'Points pour connexion quotidienne', 1, NOW(), NOW()),
  ('profile_complete', 100, 'Bonus pour profil complété à 100%', 1, NOW(), NOW()),
  ('first_purchase', 150, 'Bonus pour premier achat/échange', 1, NOW(), NOW()),
  ('review_written', 30, 'Points pour avoir écrit un commentaire', 1, NOW(), NOW()),
  ('share_social', 20, 'Points pour partage sur réseaux sociaux', 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE points_amount = VALUES(points_amount), description = VALUES(description), is_active = VALUES(is_active), updated_at = NOW();

-- Insertion des badges par défaut
INSERT INTO badges (name, description, icon, category, requirement_type, requirement_value, rarity, points_reward, created_at, updated_at) VALUES
  ('Première Connexion', 'Se connecter pour la première fois', '🎮', 'activity', 'special', 1, 'common', 10, NOW(), NOW()),
  ('Débutant', 'Atteindre 100 points', '🌟', 'points', 'points_total', 100, 'common', 25, NOW(), NOW()),
  ('Collectionneur', 'Atteindre 500 points', '💎', 'points', 'points_total', 500, 'rare', 50, NOW(), NOW()),
  ('Maître des Points', 'Atteindre 1000 points', '👑', 'points', 'points_total', 1000, 'epic', 100, NOW(), NOW()),
  ('Légende', 'Atteindre 5000 points', '🏆', 'points', 'points_total', 5000, 'legendary', 500, NOW(), NOW()),
  ('Joueur Actif', 'Jouer 10 parties', '🎯', 'activity', 'games_played', 10, 'common', 50, NOW(), NOW()),
  ('Accro du Gaming', 'Jouer 50 parties', '🔥', 'activity', 'games_played', 50, 'rare', 150, NOW(), NOW()),
  ('Participant Assidu', 'Assister à 5 événements', '🎪', 'activity', 'events_attended', 5, 'rare', 100, NOW(), NOW()),
  ('Série de 7', 'Se connecter 7 jours d\'affilée', '📅', 'activity', 'login_streak', 7, 'epic', 200, NOW(), NOW()),
  ('Série de 30', 'Se connecter 30 jours d\'affilée', '🔥', 'activity', 'login_streak', 30, 'legendary', 1000, NOW(), NOW()),
  ('Social', 'Parrainer 3 amis', '👥', 'social', 'friends_referred', 3, 'rare', 300, NOW(), NOW()),
  ('Recruteur', 'Parrainer 10 amis', '🌐', 'social', 'friends_referred', 10, 'legendary', 1500, NOW(), NOW())
ON DUPLICATE KEY UPDATE description = VALUES(description), icon = VALUES(icon), category = VALUES(category), 
  requirement_type = VALUES(requirement_type), requirement_value = VALUES(requirement_value), 
  rarity = VALUES(rarity), points_reward = VALUES(points_reward), updated_at = NOW();
