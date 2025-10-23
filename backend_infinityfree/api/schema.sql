-- api/schema.sql
-- MySQL schema for GameZone backend

CREATE DATABASE IF NOT EXISTS `gamezone` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `gamezone`;

-- Users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('player','admin') NOT NULL DEFAULT 'player',
  avatar_url VARCHAR(500) NULL,
  points INT NOT NULL DEFAULT 0,
  level VARCHAR(100) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  deactivation_reason TEXT NULL,
  deactivation_date DATETIME NULL,
  deactivated_by INT NULL,
  join_date DATE NULL,
  last_active DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Points transactions
CREATE TABLE IF NOT EXISTS points_transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  change_amount INT NOT NULL,
  reason VARCHAR(255) NULL,
  type ENUM('game','tournament','bonus','reservation','friend','adjustment','reward') NULL,
  admin_id INT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_pt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rewards catalog
CREATE TABLE IF NOT EXISTS rewards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  cost INT NOT NULL,
  available TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Reward redemptions
CREATE TABLE IF NOT EXISTS reward_redemptions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  reward_id INT NOT NULL,
  user_id INT NOT NULL,
  cost INT NOT NULL,
  created_at DATETIME NOT NULL,
  CONSTRAINT fk_rr_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
  CONSTRAINT fk_rr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events / Gallery items
CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  date DATE NOT NULL,
  type ENUM('tournament','event','stream','news') NOT NULL,
  image_url VARCHAR(500) NULL,
  participants INT NULL,
  winner VARCHAR(100) NULL,
  description TEXT NULL,
  content TEXT NULL,
  status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
  likes INT NOT NULL DEFAULT 0,
  comments INT NOT NULL DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_event_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gallery table for images and media
CREATE TABLE IF NOT EXISTS gallery (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT NULL,
  image_url VARCHAR(500) NOT NULL,
  thumbnail_url VARCHAR(500) NULL,
  category ENUM('tournament','event','stream','general','vr','retro') NOT NULL DEFAULT 'general',
  event_id INT NULL,
  status ENUM('active','archived') NOT NULL DEFAULT 'active',
  display_order INT NOT NULL DEFAULT 0,
  views INT NOT NULL DEFAULT 0,
  likes INT NOT NULL DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_gallery_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL,
  CONSTRAINT fk_gallery_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tournaments details
CREATE TABLE IF NOT EXISTS tournaments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  game_name VARCHAR(150) NOT NULL,
  platform VARCHAR(100) NULL,
  max_participants INT NOT NULL,
  current_participants INT NOT NULL DEFAULT 0,
  prize_pool VARCHAR(200) NULL,
  registration_start DATETIME NULL,
  registration_end DATETIME NULL,
  tournament_start DATETIME NOT NULL,
  tournament_end DATETIME NULL,
  rules TEXT NULL,
  status ENUM('upcoming','registration_open','in_progress','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_tournament_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Streams details
CREATE TABLE IF NOT EXISTS streams (
  id INT AUTO_INCREMENT PRIMARY KEY,
  event_id INT NOT NULL,
  streamer_name VARCHAR(150) NOT NULL,
  platform VARCHAR(100) NOT NULL DEFAULT 'Twitch',
  stream_url VARCHAR(500) NULL,
  game_name VARCHAR(150) NULL,
  scheduled_start DATETIME NOT NULL,
  scheduled_end DATETIME NULL,
  actual_start DATETIME NULL,
  actual_end DATETIME NULL,
  viewers_count INT NOT NULL DEFAULT 0,
  status ENUM('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  CONSTRAINT fk_stream_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Daily bonus tracking
CREATE TABLE IF NOT EXISTS daily_bonuses (
  user_id INT PRIMARY KEY,
  last_claim_date DATE NOT NULL,
  CONSTRAINT fk_db_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deleted users audit log
CREATE TABLE IF NOT EXISTS deleted_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  username VARCHAR(100) NOT NULL,
  email VARCHAR(191) NOT NULL,
  deletion_reason TEXT NOT NULL,
  deleted_by INT NOT NULL,
  deleted_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed minimal data
INSERT INTO rewards (name, cost, available, created_at, updated_at) VALUES
  ('1h de jeu gratuite', 500, 1, NOW(), NOW()),
  ('Boisson offerte', 200, 1, NOW(), NOW()),
  ('T-shirt GameZone', 1500, 1, NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), cost = VALUES(cost), available = VALUES(available), updated_at = NOW();

INSERT INTO events (title, date, type, image_url, participants, winner, description, likes, comments, created_at) VALUES
  ('Tournoi FIFA 24 - Finale', '2025-01-25', 'tournament', 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800&h=600&fit=crop', 32, 'GameMaster99', 'Une finale épique qui s\'est jouée aux tirs au but !', 47, 12, NOW()),
  ('Soirée Retro Gaming', '2025-01-20', 'event', 'https://images.unsplash.com/photo-1493711662062-fa541adb3fc8?w=800&h=600&fit=crop', 28, NULL, 'Une soirée nostalgique avec les classiques des années 80-90', 63, 18, NOW()),
  ('Championnat Apex Legends', '2025-01-18', 'tournament', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&h=600&fit=crop', 48, 'ProGamer2024', 'Battle royale intense avec des équipes de 3 joueurs', 89, 25, NOW()),
  ('Session Streaming Live', '2025-01-15', 'stream', 'https://images.unsplash.com/photo-1560472355-536de3962603?w=800&h=600&fit=crop', 156, NULL, 'Stream communautaire avec les meilleurs joueurs', 124, 67, NOW()),
  ('Inauguration Nouvelle Zone VR', '2025-01-12', 'news', 'https://images.unsplash.com/photo-1617802690992-15d93263d3a9?w=800&h=600&fit=crop', 85, NULL, 'Découverte de notre nouvel espace réalité virtuelle', 156, 43, NOW())
ON DUPLICATE KEY UPDATE title = VALUES(title), date = VALUES(date), type = VALUES(type), image_url = VALUES(image_url), participants = VALUES(participants), winner = VALUES(winner), description = VALUES(description), likes = VALUES(likes), comments = VALUES(comments);

-- Create default admin if not exists (password: demo123)
INSERT INTO users (username, email, password_hash, role, points, level, status, join_date, last_active, created_at, updated_at)
SELECT 'Admin', 'admin@gmail.com', '$2y$10$VJm5l0uhQ9uQhYy1gJqkUe8v7.2t2fYyF0u7l5wGts5Vh7n7qGAVi', 'admin', 0, 'Admin', 'active', CURDATE(), NOW(), NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'admin@gmail.com');

-- The hash corresponds to password 'demo123' generated by PHP password_hash
