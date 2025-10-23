-- Migration pour créer les tables de gestion de contenu
-- Exécuter ce fichier pour créer toutes les tables nécessaires

-- Table pour le contenu (news, events, streams, gallery)
CREATE TABLE IF NOT EXISTS `content` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('news', 'event', 'stream', 'gallery') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `content` LONGTEXT,
  `image_url` VARCHAR(500),
  `video_url` VARCHAR(500),
  `external_link` VARCHAR(500),
  `event_date` DATETIME,
  `event_location` VARCHAR(255),
  `stream_url` VARCHAR(500),
  `is_published` TINYINT(1) DEFAULT 1,
  `is_pinned` TINYINT(1) DEFAULT 0,
  `published_at` DATETIME,
  `views_count` INT DEFAULT 0,
  `created_by` INT,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX idx_type (`type`),
  INDEX idx_published (`is_published`, `published_at`),
  INDEX idx_pinned (`is_pinned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les likes de contenu
CREATE TABLE IF NOT EXISTS `content_likes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `content_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`content_id`) REFERENCES `content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_like (`content_id`, `user_id`),
  INDEX idx_content (`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les commentaires de contenu
CREATE TABLE IF NOT EXISTS `content_comments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `content_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `comment` TEXT NOT NULL,
  `parent_id` INT,
  `is_approved` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  FOREIGN KEY (`content_id`) REFERENCES `content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`parent_id`) REFERENCES `content_comments`(`id`) ON DELETE CASCADE,
  INDEX idx_content (`content_id`),
  INDEX idx_parent (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table améliorée pour les tournois
CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `game_id` INT,
  `type` ENUM('single_elimination', 'double_elimination', 'round_robin', 'swiss', 'free_for_all') DEFAULT 'single_elimination',
  `max_participants` INT NOT NULL,
  `entry_fee` INT DEFAULT 0 COMMENT 'Coût en points pour participer',
  `prize_pool` INT DEFAULT 0 COMMENT 'Cagnotte totale',
  `first_place_prize` INT DEFAULT 0,
  `second_place_prize` INT DEFAULT 0,
  `third_place_prize` INT DEFAULT 0,
  `start_date` DATETIME NOT NULL,
  `end_date` DATETIME,
  `registration_deadline` DATETIME,
  `rules` TEXT,
  `image_url` VARCHAR(500),
  `stream_url` VARCHAR(500),
  `status` ENUM('upcoming', 'registration_open', 'registration_closed', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
  `is_featured` TINYINT(1) DEFAULT 0,
  `winner_id` INT,
  `created_by` INT,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  FOREIGN KEY (`game_id`) REFERENCES `games`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`winner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX idx_status (`status`),
  INDEX idx_start_date (`start_date`),
  INDEX idx_featured (`is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les participants aux tournois
CREATE TABLE IF NOT EXISTS `tournament_participants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `team_name` VARCHAR(255),
  `status` ENUM('registered', 'confirmed', 'checked_in', 'disqualified', 'withdrawn') DEFAULT 'registered',
  `placement` INT COMMENT 'Position finale dans le tournoi',
  `points_earned` INT DEFAULT 0,
  `prize_won` INT DEFAULT 0,
  `registered_at` DATETIME NOT NULL,
  `checked_in_at` DATETIME,
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_participant (`tournament_id`, `user_id`),
  INDEX idx_tournament (`tournament_id`),
  INDEX idx_status (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les matchs de tournoi
CREATE TABLE IF NOT EXISTS `tournament_matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tournament_id` INT NOT NULL,
  `round` INT NOT NULL,
  `match_number` INT NOT NULL,
  `player1_id` INT,
  `player2_id` INT,
  `winner_id` INT,
  `player1_score` INT DEFAULT 0,
  `player2_score` INT DEFAULT 0,
  `status` ENUM('pending', 'ongoing', 'completed', 'forfeit') DEFAULT 'pending',
  `scheduled_time` DATETIME,
  `started_at` DATETIME,
  `completed_at` DATETIME,
  `notes` TEXT,
  FOREIGN KEY (`tournament_id`) REFERENCES `tournaments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`player1_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`player2_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`winner_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX idx_tournament (`tournament_id`),
  INDEX idx_round (`round`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les packages de points (à acheter avec de l'argent réel)
CREATE TABLE IF NOT EXISTS `points_packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `points_amount` INT NOT NULL COMMENT 'Nombre de points dans ce package',
  `bonus_points` INT DEFAULT 0 COMMENT 'Points bonus offerts',
  `price` DECIMAL(10,2) NOT NULL COMMENT 'Prix en devise réelle',
  `currency` VARCHAR(3) DEFAULT 'XOF',
  `discount_percentage` INT DEFAULT 0,
  `is_featured` TINYINT(1) DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `display_order` INT DEFAULT 0,
  `image_url` VARCHAR(500),
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  INDEX idx_active (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les achats de packages de points
CREATE TABLE IF NOT EXISTS `points_package_purchases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `package_id` INT NOT NULL,
  `points_amount` INT NOT NULL,
  `bonus_points` INT DEFAULT 0,
  `total_points` INT NOT NULL COMMENT 'points_amount + bonus_points',
  `price` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'XOF',
  `payment_method` VARCHAR(50),
  `payment_status` ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `payment_reference` VARCHAR(255),
  `points_credited` TINYINT(1) DEFAULT 0,
  `credited_at` DATETIME,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NOT NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`package_id`) REFERENCES `points_packages`(`id`) ON DELETE SET NULL,
  INDEX idx_user (`user_id`),
  INDEX idx_status (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
