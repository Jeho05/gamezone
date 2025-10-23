<?php
// api/admin/invoice_dashboard.php
// Dashboard pour les statistiques des factures et sessions (ADMIN ONLY)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

$user = require_auth();
if (!is_admin($user)) {
    json_response(['error' => 'Accès refusé - Admin uniquement'], 403);
}

$pdo = get_db();

// Statistiques globales des factures
$stmt = $pdo->query('
    SELECT 
        COUNT(*) as total_invoices,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = "used" THEN 1 ELSE 0 END) as used,
        SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN status = "cancelled" THEN 1 ELSE 0 END) as cancelled,
        SUM(amount) as total_revenue,
        SUM(CASE WHEN status = "used" THEN amount ELSE 0 END) as used_revenue,
        SUM(CASE WHEN status = "expired" THEN amount ELSE 0 END) as expired_revenue,
        COUNT(DISTINCT user_id) as unique_users
    FROM invoices
');
$invoiceStats = $stmt->fetch();

// Statistiques des sessions
$stmt = $pdo->query('
    SELECT 
        COUNT(*) as total_sessions,
        SUM(CASE WHEN status = "ready" THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = "paused" THEN 1 ELSE 0 END) as paused,
        SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = "expired" THEN 1 ELSE 0 END) as expired,
        SUM(total_minutes) as total_minutes_allocated,
        SUM(used_minutes) as total_minutes_used,
        AVG(CASE WHEN status = "completed" THEN (used_minutes / total_minutes) * 100 END) as avg_completion_rate
    FROM active_game_sessions_v2
');
$sessionStats = $stmt->fetch();

// Statistiques de scan (sécurité)
$stmt = $pdo->query('
    SELECT 
        COUNT(*) as total_scans,
        SUM(CASE WHEN scan_result = "success" THEN 1 ELSE 0 END) as successful_scans,
        SUM(CASE WHEN scan_result = "invalid_code" THEN 1 ELSE 0 END) as invalid_codes,
        SUM(CASE WHEN scan_result = "fraud_detected" THEN 1 ELSE 0 END) as fraud_detected,
        SUM(CASE WHEN scan_result = "expired" THEN 1 ELSE 0 END) as expired_scans,
        COUNT(DISTINCT ip_address) as unique_ips,
        COUNT(DISTINCT scanned_by) as unique_scanners
    FROM invoice_scans
    WHERE scanned_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
');
$scanStats = $stmt->fetch();

// Factures suspectes
$stmt = $pdo->query('
    SELECT i.*, u.username, u.email,
           (SELECT COUNT(*) FROM invoice_scans WHERE invoice_id = i.id) as total_scan_attempts
    FROM invoices i
    INNER JOIN users u ON i.user_id = u.id
    WHERE i.is_suspicious = 1
    ORDER BY i.last_scan_attempt DESC
    LIMIT 10
');
$suspiciousInvoices = $stmt->fetchAll();

// Sessions actives en ce moment
$stmt = $pdo->query('
    SELECT s.*, u.username, g.name as game_name,
           ROUND((s.used_minutes / s.total_minutes) * 100, 1) as progress_percent
    FROM active_game_sessions_v2 s
    INNER JOIN users u ON s.user_id = u.id
    INNER JOIN games g ON s.game_id = g.id
    WHERE s.status = "active"
    ORDER BY s.remaining_minutes ASC
    LIMIT 20
');
$activeSessions = $stmt->fetchAll();

// Factures expirant bientôt (dans les 7 prochains jours)
$stmt = $pdo->query('
    SELECT i.*, u.username, u.email,
           DATEDIFF(i.expires_at, NOW()) as days_until_expiry
    FROM invoices i
    INNER JOIN users u ON i.user_id = u.id
    WHERE i.status = "pending" 
    AND i.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY i.expires_at ASC
    LIMIT 20
');
$expiringInvoices = $stmt->fetchAll();

// Statistiques par jeu (top 10)
$stmt = $pdo->query('
    SELECT 
        i.game_name,
        COUNT(*) as total_invoices,
        SUM(CASE WHEN i.status = "used" THEN 1 ELSE 0 END) as used_count,
        SUM(i.amount) as total_revenue,
        SUM(i.duration_minutes) as total_minutes_sold,
        AVG(i.amount) as avg_price
    FROM invoices i
    GROUP BY i.game_name
    ORDER BY total_revenue DESC
    LIMIT 10
');
$gameStats = $stmt->fetchAll();

// Activité récente (dernières 24h)
$stmt = $pdo->query('
    SELECT 
        DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00") as hour,
        COUNT(*) as invoices_created
    FROM invoices
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY hour
    ORDER BY hour ASC
');
$hourlyActivity = $stmt->fetchAll();

// Taux de conversion (factures activées vs créées)
$stmt = $pdo->query('
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as created,
        SUM(CASE WHEN status IN ("active", "used") THEN 1 ELSE 0 END) as activated,
        ROUND((SUM(CASE WHEN status IN ("active", "used") THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as activation_rate
    FROM invoices
    WHERE created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
');
$conversionStats = $stmt->fetchAll();

json_response([
    'success' => true,
    'invoice_stats' => $invoiceStats,
    'session_stats' => $sessionStats,
    'scan_stats' => $scanStats,
    'suspicious_invoices' => $suspiciousInvoices,
    'active_sessions' => $activeSessions,
    'expiring_invoices' => $expiringInvoices,
    'game_stats' => $gameStats,
    'hourly_activity' => $hourlyActivity,
    'conversion_stats' => $conversionStats,
    'generated_at' => date('Y-m-d H:i:s')
]);
