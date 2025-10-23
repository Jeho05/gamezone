<?php
// api/utils.php
require_once __DIR__ . '/config.php';

function json_response($data, int $status = 200): void {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_method(array $allowed): void {
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        json_response(['error' => 'Method Not Allowed'], 405);
    }
}

function get_json_input(): array {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        if ($raw === false || $raw === '') return [];
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            json_response(['error' => 'Invalid JSON body'], 400);
        }
        return is_array($data) ? $data : [];
    }
    // Fallback to form fields
    return $_POST ?: [];
}

function require_auth(?string $role = null): array {
    if (!isset($_SESSION['user'])) {
        json_response(['error' => 'Unauthorized'], 401);
    }
    $user = $_SESSION['user'];
    if ($role && ($user['role'] ?? '') !== $role) {
        json_response(['error' => 'Forbidden'], 403);
    }
    return $user;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_admin(?array $user = null): bool {
    $user = $user ?? current_user();
    return $user && ($user['role'] ?? '') === 'admin';
}

function set_session_user(array $user): void {
    // Store minimal user info in session
    $_SESSION['user'] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'avatar_url' => $user['avatar_url'] ?? null,
    ];
}

function validate_email(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}

function now(): string {
    return date('Y-m-d H:i:s');
}

function update_last_active(int $userId): void {
    try {
        $pdo = get_db();
        $stmt = $pdo->prepare('UPDATE users SET last_active = ?, updated_at = ? WHERE id = ?');
        $ts = now();
        $stmt->execute([$ts, $ts, $userId]);
    } catch (Throwable $e) {
        // non-fatal
    }
}

function ensure_tables_exist(): void {
    // Optional: attempt to create critical tables if missing (safe idempotent)
    $pdo = get_db();
    $pdo->exec('CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NOT NULL,
        email VARCHAR(191) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM("player","admin") NOT NULL DEFAULT "player",
        avatar_url VARCHAR(500) NULL,
        points INT NOT NULL DEFAULT 0,
        level VARCHAR(100) NULL,
        status ENUM("active","inactive") NOT NULL DEFAULT "active",
        join_date DATE NULL,
        last_active DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS points_transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        change_amount INT NOT NULL,
        reason VARCHAR(255) NULL,
        type ENUM("game","tournament","bonus","reservation","friend","adjustment","reward") NULL,
        admin_id INT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_pt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS rewards (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        cost INT NOT NULL,
        available TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS reward_redemptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        reward_id INT NOT NULL,
        user_id INT NOT NULL,
        cost INT NOT NULL,
        created_at DATETIME NOT NULL,
        CONSTRAINT fk_rr_reward FOREIGN KEY (reward_id) REFERENCES rewards(id) ON DELETE CASCADE,
        CONSTRAINT fk_rr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        date DATE NOT NULL,
        type ENUM("tournament","event","stream","news") NOT NULL,
        image_url VARCHAR(500) NULL,
        participants INT NULL,
        winner VARCHAR(100) NULL,
        description TEXT NULL,
        likes INT NOT NULL DEFAULT 0,
        comments INT NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $pdo->exec('CREATE TABLE IF NOT EXISTS daily_bonuses (
        user_id INT PRIMARY KEY,
        last_claim_date DATE NOT NULL,
        CONSTRAINT fk_db_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
}

/**
 * Log des informations dans le fichier de log API
 */
function log_info(string $message, array $context = []): void {
    log_message('INFO', $message, $context);
}

/**
 * Log des erreurs dans le fichier de log API
 */
function log_error(string $message, array $context = []): void {
    log_message('ERROR', $message, $context);
}

/**
 * Fonction générique de logging
 */
function log_message(string $level, string $message, array $context = []): void {
    try {
        $logDir = __DIR__ . '/../logs';
        
        // Créer le dossier logs s'il n'existe pas
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/api_' . date('Y-m-d') . '.log';
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $logLine = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // Écrire dans le fichier de log
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    } catch (Throwable $e) {
        // En cas d'erreur de logging, ne pas bloquer l'application
        error_log("Erreur de logging: " . $e->getMessage());
    }
}

// Ensure tables for safety in dev (commented out to avoid errors on every request)
// Call this manually if needed: ensure_tables_exist();
