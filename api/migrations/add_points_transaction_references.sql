-- ============================================================================
-- MIGRATION: Ajouter les colonnes de référence aux transactions de points
-- Date: 2025-10-21
-- Description: Permet de lier les transactions de points à leurs sources
--              (sessions de jeu, récompenses, etc.)
-- ============================================================================

USE `gamezone`;

-- Ajouter les colonnes reference_type et reference_id
-- Ces colonnes permettent de lier chaque transaction à son origine
ALTER TABLE points_transactions 
ADD COLUMN reference_type VARCHAR(50) NULL COMMENT 'Type de référence: game_session, reward, bonus, etc.' AFTER type,
ADD COLUMN reference_id INT NULL COMMENT 'ID de l\'entité référencée' AFTER reference_type,
ADD INDEX idx_reference (reference_type, reference_id);

-- Vérifier la structure finale
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'gamezone'
AND TABLE_NAME = 'points_transactions'
ORDER BY ORDINAL_POSITION;
