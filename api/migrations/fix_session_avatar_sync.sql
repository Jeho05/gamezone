-- ============================================================================
-- Migration: Ajout de l'avatar_url dans la vue session_summary
-- Date: 2025-01-18
-- Description: Correction de la synchronisation des avatars pour les sessions
-- ============================================================================

USE `gamezone`;

-- Supprimer l'ancienne vue
DROP VIEW IF EXISTS session_summary;

-- Recréer la vue avec l'avatar_url
CREATE VIEW session_summary AS
SELECT 
  s.*,
  i.invoice_number,
  i.validation_code,
  u.username,
  u.avatar_url,
  u.level,
  u.points,
  g.name as game_name,
  g.slug as game_slug,
  g.image_url as game_image,
  ROUND((s.used_minutes / s.total_minutes) * 100, 1) as progress_percent,
  (s.total_minutes - s.used_minutes) as remaining_minutes
FROM active_game_sessions_v2 s
INNER JOIN invoices i ON s.invoice_id = i.id
INNER JOIN users u ON s.user_id = u.id
INNER JOIN games g ON s.game_id = g.id
ORDER BY s.created_at DESC;

-- Afficher un résumé
SELECT 
  'Migration terminée avec succès' as message,
  COUNT(*) as total_sessions_in_view
FROM session_summary;
