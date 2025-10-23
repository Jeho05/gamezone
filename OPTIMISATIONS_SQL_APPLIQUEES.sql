-- OPTIMISATIONS SQL POUR AMÉLIORER LES PERFORMANCES
-- Date: 2025-01-18
-- Ces index améliorent significativement les performances des requêtes fréquentes

-- ==========================================
-- INDEX POUR LA TABLE USERS
-- ==========================================

-- Index pour les recherches par email (login)
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- Index pour les utilisateurs actifs
CREATE INDEX IF NOT EXISTS idx_users_last_active ON users(last_active);

-- Index pour le statut et rôle
CREATE INDEX IF NOT EXISTS idx_users_status_role ON users(status, role);

-- Index pour le classement par points
CREATE INDEX IF NOT EXISTS idx_users_points ON users(points DESC);

-- ==========================================
-- INDEX POUR LA TABLE PURCHASES
-- ==========================================

-- Index pour les achats d'un utilisateur
CREATE INDEX IF NOT EXISTS idx_purchases_user_id ON purchases(user_id);

-- Index pour les achats complétés
CREATE INDEX IF NOT EXISTS idx_purchases_payment_status ON purchases(payment_status);

-- Index composite pour les revenus par date
CREATE INDEX IF NOT EXISTS idx_purchases_status_date ON purchases(payment_status, created_at);

-- Index pour la référence de paiement
CREATE INDEX IF NOT EXISTS idx_purchases_payment_ref ON purchases(payment_reference);

-- ==========================================
-- INDEX POUR LA TABLE GAME_SESSIONS
-- ==========================================

-- Index pour les sessions actives
CREATE INDEX IF NOT EXISTS idx_game_sessions_status ON game_sessions(status);

-- Index pour les sessions par utilisateur
CREATE INDEX IF NOT EXISTS idx_game_sessions_user_id ON game_sessions(user_id);

-- Index pour les sessions par jeu
CREATE INDEX IF NOT EXISTS idx_game_sessions_game_id ON game_sessions(game_id);

-- Index composite pour les sessions actives par utilisateur
CREATE INDEX IF NOT EXISTS idx_game_sessions_user_status ON game_sessions(user_id, status);

-- ==========================================
-- INDEX POUR LA TABLE POINTS_TRANSACTIONS
-- ==========================================

-- Index pour l'historique des points d'un utilisateur
CREATE INDEX IF NOT EXISTS idx_points_transactions_user_id ON points_transactions(user_id);

-- Index pour les transactions par date
CREATE INDEX IF NOT EXISTS idx_points_transactions_date ON points_transactions(created_at);

-- Index composite pour les transactions par type et utilisateur
CREATE INDEX IF NOT EXISTS idx_points_transactions_user_type ON points_transactions(user_id, type);

-- ==========================================
-- INDEX POUR LA TABLE GAME_RESERVATIONS
-- ==========================================

-- Index pour les réservations par utilisateur
CREATE INDEX IF NOT EXISTS idx_game_reservations_user_id ON game_reservations(user_id);

-- Index pour les réservations par jeu
CREATE INDEX IF NOT EXISTS idx_game_reservations_game_id ON game_reservations(game_id);

-- Index pour les réservations par statut
CREATE INDEX IF NOT EXISTS idx_game_reservations_status ON game_reservations(status);

-- Index composite pour la vérification de disponibilité (critique pour les performances)
CREATE INDEX IF NOT EXISTS idx_game_reservations_availability ON game_reservations(game_id, scheduled_start, scheduled_end, status);

-- ==========================================
-- INDEX POUR LA TABLE INVOICES
-- ==========================================

-- Index pour les factures d'un achat
CREATE INDEX IF NOT EXISTS idx_invoices_purchase_id ON invoices(purchase_id);

-- Index pour les factures par statut
CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);

-- Index pour le QR code
CREATE INDEX IF NOT EXISTS idx_invoices_qr_code ON invoices(qr_code);

-- ==========================================
-- INDEX POUR LA TABLE REWARD_REDEMPTIONS
-- ==========================================

-- Index pour les rachats par utilisateur
CREATE INDEX IF NOT EXISTS idx_reward_redemptions_user_id ON reward_redemptions(user_id);

-- Index pour les rachats par récompense
CREATE INDEX IF NOT EXISTS idx_reward_redemptions_reward_id ON reward_redemptions(reward_id);

-- Index pour les rachats en attente
CREATE INDEX IF NOT EXISTS idx_reward_redemptions_status ON reward_redemptions(status);

-- ==========================================
-- INDEX POUR LES TABLES DE CONTENU
-- ==========================================

-- Index pour le contenu publié
CREATE INDEX IF NOT EXISTS idx_content_published ON content(is_published);

-- Index pour les événements par date
CREATE INDEX IF NOT EXISTS idx_events_date ON events(date);

-- Index pour les événements par type
CREATE INDEX IF NOT EXISTS idx_events_type ON events(type);

-- ==========================================
-- OPTIMISATIONS ADDITIONNELLES
-- ==========================================

-- Analyser les tables pour mettre à jour les statistiques
ANALYZE TABLE users;
ANALYZE TABLE purchases;
ANALYZE TABLE game_sessions;
ANALYZE TABLE points_transactions;
ANALYZE TABLE game_reservations;
ANALYZE TABLE invoices;

-- Optimiser les tables pour défragmentation
OPTIMIZE TABLE users;
OPTIMIZE TABLE purchases;
OPTIMIZE TABLE game_sessions;
OPTIMIZE TABLE points_transactions;

-- ==========================================
-- NOTES D'OPTIMISATION
-- ==========================================
-- 1. Ces index améliorent les performances des requêtes SELECT mais légèrement ralentissent les INSERT/UPDATE
-- 2. Surveiller la taille des index avec: SELECT table_name, index_name, stat_value FROM mysql.innodb_index_stats
-- 3. Revoir périodiquement l'utilisation des index avec: SHOW INDEX FROM table_name
-- 4. Optimiser les tables mensuellement en production
