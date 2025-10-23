<?php
// Corriger toutes les colonnes manquantes dans active_game_sessions_v2
require_once __DIR__ . '/api/config.php';

echo "=== CORRECTION COMPLÈTE DU SYSTÈME ===\n\n";

try {
    $pdo = get_db();
    
    // 1. Vérifier et ajouter remaining_minutes si manquant
    echo "1️⃣ Vérification de la colonne remaining_minutes...\n";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM active_game_sessions_v2 LIKE 'remaining_minutes'");
    if ($stmt->rowCount() == 0) {
        echo "  ⚠️ Colonne remaining_minutes manquante, ajout...\n";
        $pdo->exec("
            ALTER TABLE active_game_sessions_v2 
            ADD COLUMN remaining_minutes INT AS (total_minutes - used_minutes) STORED
        ");
        echo "  ✓ Colonne remaining_minutes ajoutée (calculée automatiquement)\n";
    } else {
        echo "  ✓ Colonne remaining_minutes existe\n";
    }
    
    // 2. Créer/recréer une vue pour les sessions avec toutes les infos
    echo "\n2️⃣ Création de la vue session_summary...\n";
    $pdo->exec("DROP VIEW IF EXISTS session_summary");
    
    $pdo->exec("
        CREATE VIEW session_summary AS
        SELECT 
            s.id,
            s.invoice_id,
            s.purchase_id,
            s.user_id,
            s.game_id,
            s.total_minutes,
            s.used_minutes,
            (s.total_minutes - s.used_minutes) as remaining_minutes,
            ROUND((s.used_minutes / s.total_minutes) * 100, 1) as progress_percent,
            s.status,
            s.ready_at,
            s.started_at,
            s.paused_at,
            s.resumed_at,
            s.completed_at,
            s.last_heartbeat,
            s.last_countdown_update,
            s.pause_count,
            s.total_pause_time,
            s.auto_countdown,
            s.countdown_interval,
            s.monitored_by,
            s.created_at,
            s.updated_at,
            u.username,
            u.email,
            g.name as game_name,
            i.invoice_number,
            i.validation_code,
            i.status as invoice_status
        FROM active_game_sessions_v2 s
        INNER JOIN users u ON s.user_id = u.id
        INNER JOIN games g ON s.game_id = g.id
        LEFT JOIN invoices i ON s.invoice_id = i.id
    ");
    echo "  ✓ Vue session_summary créée\n";
    
    // 3. Vérifier les colonnes expires_at
    echo "\n3️⃣ Vérification colonnes expires_at...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM active_game_sessions_v2 LIKE 'expires_at'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE active_game_sessions_v2 ADD COLUMN expires_at DATETIME DEFAULT NULL");
        echo "  ✓ Colonne expires_at ajoutée\n";
    } else {
        echo "  ✓ Colonne expires_at existe\n";
    }
    
    // 4. Mettre à jour les sessions existantes
    echo "\n4️⃣ Mise à jour des sessions existantes...\n";
    $pdo->exec("
        UPDATE active_game_sessions_v2 
        SET expires_at = DATE_ADD(COALESCE(started_at, ready_at), INTERVAL total_minutes MINUTE)
        WHERE expires_at IS NULL
    ");
    echo "  ✓ Sessions mises à jour\n";
    
    // 5. Corriger last_countdown_update pour les sessions actives
    echo "\n5️⃣ Initialisation last_countdown_update...\n";
    $pdo->exec("
        UPDATE active_game_sessions_v2 
        SET last_countdown_update = COALESCE(started_at, NOW())
        WHERE status = 'active' AND last_countdown_update IS NULL
    ");
    echo "  ✓ Timestamps initialisés\n";
    
    echo "\n✅ TOUTES LES CORRECTIONS APPLIQUÉES !\n\n";
    
    // Afficher les sessions actuelles
    echo "📊 SESSIONS ACTUELLES:\n";
    $stmt = $pdo->query("
        SELECT id, username, game_name, status, total_minutes, used_minutes, 
               (total_minutes - used_minutes) as remaining_minutes,
               started_at, last_countdown_update
        FROM session_summary
        WHERE status IN ('ready', 'active', 'paused')
        ORDER BY created_at DESC
    ");
    
    $sessions = $stmt->fetchAll();
    if (empty($sessions)) {
        echo "  Aucune session active\n";
    } else {
        foreach ($sessions as $s) {
            echo "  Session #{$s['id']}\n";
            echo "    User: {$s['username']}\n";
            echo "    Game: {$s['game_name']}\n";
            echo "    Status: {$s['status']}\n";
            echo "    Time: {$s['remaining_minutes']}/{$s['total_minutes']} min\n";
            echo "    Started: {$s['started_at']}\n";
            echo "    Last update: {$s['last_countdown_update']}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n✗ ERREUR: " . $e->getMessage() . "\n";
    exit(1);
}
