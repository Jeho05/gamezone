<?php
// api/cron/countdown_sessions.php
// Script CRON pour décompter automatiquement le temps des sessions actives
// À exécuter chaque minute via cron ou task scheduler
// Windows: schtasks /create /tn "GameZone Countdown" /tr "php C:\xampp\htdocs\projet ismo\api\cron\countdown_sessions.php" /sc minute /mo 1

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../utils.php';

// Vérifier que c'est bien exécuté en CLI ou avec un token secret
$isCliMode = php_sapi_name() === 'cli';
// Accept token from query string or Authorization: Bearer header
$providedToken = $_GET['token'] ?? '';
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (!$providedToken && $authHeader && preg_match('/^Bearer\s+(.*)$/i', $authHeader, $m)) {
    $providedToken = $m[1];
}
$validToken = getenv('CRON_SECRET') ?: 'DEV_CHANGE_ME';

if (!$isCliMode && $providedToken !== $validToken) {
    http_response_code(403);
    die(json_encode(['error' => 'Accès refusé']));
}

$pdo = get_db();

try {
    $startTime = microtime(true);
    $logFile = __DIR__ . '/../../logs/countdown_' . date('Y-m-d') . '.log';
    
    // Logger le début
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Début du décompte automatique\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Appeler la procédure stockée de décompte
    $stmt = $pdo->query('CALL countdown_active_sessions()');
    
    // Récupérer les statistiques
    $stmt = $pdo->query('
        SELECT 
            COUNT(*) as total_active,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as currently_active,
            SUM(CASE WHEN status = "completed" AND completed_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) as just_completed,
            SUM(CASE WHEN status = "expired" AND updated_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) as just_expired
        FROM active_game_sessions_v2
        WHERE status IN ("ready", "active", "paused", "completed", "expired")
    ');
    $stats = $stmt->fetch();
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    // Logger les résultats
    $logMessage = sprintf(
        "[%s] Décompte terminé en %sms - Actives: %d, Complétées: %d, Expirées: %d\n",
        date('Y-m-d H:i:s'),
        $duration,
        $stats['currently_active'],
        $stats['just_completed'],
        $stats['just_expired']
    );
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Envoyer des notifications si nécessaire (sessions avec peu de temps restant)
    $stmt = $pdo->query('
        SELECT s.id, s.remaining_minutes, u.username, u.email, g.name as game_name
        FROM active_game_sessions_v2 s
        INNER JOIN users u ON s.user_id = u.id
        INNER JOIN games g ON s.game_id = g.id
        WHERE s.status = "active" 
        AND s.remaining_minutes <= 5 
        AND s.remaining_minutes > 0
        AND NOT EXISTS (
            SELECT 1 FROM session_events 
            WHERE session_id = s.id 
            AND event_type = "warning_low_time" 
            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        )
    ');
    $lowTimeSessions = $stmt->fetchAll();
    
    foreach ($lowTimeSessions as $session) {
        $logMessage = sprintf(
            "[%s] ALERTE: Session #%d (%s) - Il reste %d minute(s)\n",
            date('Y-m-d H:i:s'),
            $session['id'],
            $session['username'],
            $session['remaining_minutes']
        );
        file_put_contents($logFile, $logMessage, FILE_APPEND);
        
        // TODO: Envoyer notification push/email à l'utilisateur et admin
    }
    
    $response = [
        'success' => true,
        'execution_time_ms' => $duration,
        'stats' => $stats,
        'low_time_warnings' => count($lowTimeSessions)
    ];
    
    if ($isCliMode) {
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    
} catch (Exception $e) {
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] ERREUR: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND);
    
    if ($isCliMode) {
        echo "ERREUR: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
