-- Migration: Add admin features for content management
-- Execute this if you already have an existing database

USE `gamezone`;

-- Update events table if it exists
ALTER TABLE `events` 
ADD COLUMN IF NOT EXISTS `content` TEXT NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `status` ENUM('draft','published','archived') NOT NULL DEFAULT 'draft' AFTER `content`,
ADD COLUMN IF NOT EXISTS `created_by` INT NULL AFTER `comments`,
ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL AFTER `created_at`,
ADD CONSTRAINT `fk_event_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Update existing events to published status
UPDATE `events` SET `status` = 'published' WHERE `status` IS NULL;

-- Create gallery table
CREATE TABLE IF NOT EXISTS `gallery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NULL,
  `image_url` VARCHAR(500) NOT NULL,
  `thumbnail_url` VARCHAR(500) NULL,
  `category` ENUM('tournament','event','stream','general','vr','retro') NOT NULL DEFAULT 'general',
  `event_id` INT NULL,
  `status` ENUM('active','archived') NOT NULL DEFAULT 'active',
  `display_order` INT NOT NULL DEFAULT 0,
  `views` INT NOT NULL DEFAULT 0,
  `likes` INT NOT NULL DEFAULT 0,
  `created_by` INT NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  CONSTRAINT `fk_gallery_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_gallery_creator` FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_gallery_category` (`category`),
  INDEX `idx_gallery_status` (`status`),
  INDEX `idx_gallery_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create tournaments table
CREATE TABLE IF NOT EXISTS `tournaments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `game_name` VARCHAR(150) NOT NULL,
  `platform` VARCHAR(100) NULL,
  `max_participants` INT NOT NULL,
  `current_participants` INT NOT NULL DEFAULT 0,
  `prize_pool` VARCHAR(200) NULL,
  `registration_start` DATETIME NULL,
  `registration_end` DATETIME NULL,
  `tournament_start` DATETIME NOT NULL,
  `tournament_end` DATETIME NULL,
  `rules` TEXT NULL,
  `status` ENUM('upcoming','registration_open','in_progress','completed','cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  CONSTRAINT `fk_tournament_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  INDEX `idx_tournament_status` (`status`),
  INDEX `idx_tournament_dates` (`tournament_start`, `tournament_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create streams table
CREATE TABLE IF NOT EXISTS `streams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_id` INT NOT NULL,
  `streamer_name` VARCHAR(150) NOT NULL,
  `platform` VARCHAR(100) NOT NULL DEFAULT 'Twitch',
  `stream_url` VARCHAR(500) NULL,
  `game_name` VARCHAR(150) NULL,
  `scheduled_start` DATETIME NOT NULL,
  `scheduled_end` DATETIME NULL,
  `actual_start` DATETIME NULL,
  `actual_end` DATETIME NULL,
  `viewers_count` INT NOT NULL DEFAULT 0,
  `status` ENUM('scheduled','live','ended','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  CONSTRAINT `fk_stream_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  INDEX `idx_stream_status` (`status`),
  INDEX `idx_stream_dates` (`scheduled_start`, `scheduled_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Populate gallery from existing events images
INSERT IGNORE INTO `gallery` (`title`, `description`, `image_url`, `thumbnail_url`, `category`, `event_id`, `status`, `display_order`, `created_at`, `updated_at`)
SELECT 
    e.title,
    e.description,
    e.image_url,
    e.image_url as thumbnail_url,
    CASE 
        WHEN e.type = 'tournament' THEN 'tournament'
        WHEN e.type = 'stream' THEN 'stream'
        WHEN e.type = 'event' THEN 'event'
        ELSE 'general'
    END as category,
    e.id as event_id,
    'active' as status,
    0 as display_order,
    e.created_at,
    NOW() as updated_at
FROM `events` e
WHERE e.image_url IS NOT NULL AND e.image_url != ''
AND NOT EXISTS (
    SELECT 1 FROM `gallery` g WHERE g.event_id = e.id
);

-- Add sample gallery items with diverse categories
INSERT IGNORE INTO `gallery` (`title`, `description`, `image_url`, `category`, `status`, `display_order`, `created_at`) VALUES
('Zone VR - Espace Moderne', 'Notre tout nouvel espace de réalité virtuelle équipé des derniers casques', 'https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?w=800&h=600&fit=crop', 'vr', 'active', 1, NOW()),
('Consoles Rétro Collection', 'Collection de consoles vintage pour les nostalgiques', 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&h=600&fit=crop', 'retro', 'active', 2, NOW()),
('Setup Gaming Pro', 'Nos stations de jeu haut de gamme avec écrans 240Hz', 'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?w=800&h=600&fit=crop', 'general', 'active', 3, NOW()),
('Tournoi Esport Ambiance', 'Atmosphère électrique lors de nos tournois', 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&h=600&fit=crop', 'tournament', 'active', 4, NOW());

-- Create indexes for better performance
ALTER TABLE `events` ADD INDEX IF NOT EXISTS `idx_event_type` (`type`);
ALTER TABLE `events` ADD INDEX IF NOT EXISTS `idx_event_status` (`status`);
ALTER TABLE `events` ADD INDEX IF NOT EXISTS `idx_event_date` (`date`);

-- Update schema version (optional tracking table)
CREATE TABLE IF NOT EXISTS `schema_version` (
  `version` VARCHAR(50) PRIMARY KEY,
  `applied_at` DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `schema_version` (`version`, `applied_at`) VALUES ('1.0.0-admin-features', NOW())
ON DUPLICATE KEY UPDATE `applied_at` = NOW();

-- Success message
SELECT 'Migration completed successfully! Admin features added.' as message;
