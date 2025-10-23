-- Migration pour ajouter les réactions et partages
-- Exécuter ce fichier pour ajouter les nouvelles fonctionnalités

-- Table pour les réactions sur le contenu (en plus des likes simples)
CREATE TABLE IF NOT EXISTS `content_reactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `content_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `reaction_type` ENUM('like', 'love', 'wow', 'haha', 'sad', 'angry') NOT NULL DEFAULT 'like',
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`content_id`) REFERENCES `content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY unique_reaction (`content_id`, `user_id`),
  INDEX idx_content (`content_id`),
  INDEX idx_type (`reaction_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour les partages de contenu
CREATE TABLE IF NOT EXISTS `content_shares` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `content_id` INT NOT NULL,
  `user_id` INT,
  `platform` ENUM('facebook', 'twitter', 'whatsapp', 'telegram', 'copy_link') NOT NULL,
  `created_at` DATETIME NOT NULL,
  FOREIGN KEY (`content_id`) REFERENCES `content`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX idx_content (`content_id`),
  INDEX idx_platform (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajouter une colonne pour les notifications
ALTER TABLE `content` 
ADD COLUMN IF NOT EXISTS `shares_count` INT DEFAULT 0 AFTER `views_count`;
