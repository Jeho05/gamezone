-- Migration: Créer la table game_images pour stocker les images en base64
-- Date: 2025-11-09
-- Cette table permet de stocker les images directement en BDD pour éviter les problèmes de filesystem éphémère sur Railway

CREATE TABLE IF NOT EXISTS `game_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `data` longtext NOT NULL COMMENT 'Image encodée en base64',
  `mime_type` varchar(50) NOT NULL DEFAULT 'image/jpeg',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_filename` (`filename`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
