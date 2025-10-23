<?php
// Script pour créer les tables de contenu et tournois
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/utils.php';

// Vérifier que l'utilisateur est admin
$user = require_auth('admin');
$pdo = get_db();

try {
    $pdo->beginTransaction();
    
    // Table NEWS
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS news (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            excerpt TEXT,
            category VARCHAR(50) DEFAULT 'general',
            image_url VARCHAR(500),
            author_id INT,
            is_published TINYINT(1) DEFAULT 0,
            published_at DATETIME,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_published (is_published, published_at),
            INDEX idx_category (category)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table EVENTS
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_type VARCHAR(50) DEFAULT 'general',
            image_url VARCHAR(500),
            start_date DATETIME NOT NULL,
            end_date DATETIME,
            location VARCHAR(255),
            max_participants INT,
            registration_required TINYINT(1) DEFAULT 0,
            is_published TINYINT(1) DEFAULT 0,
            created_by INT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_dates (start_date, end_date),
            INDEX idx_published (is_published)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table EVENT_REGISTRATIONS
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS event_registrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id INT NOT NULL,
            user_id INT NOT NULL,
            status VARCHAR(20) DEFAULT 'registered',
            registered_at DATETIME NOT NULL,
            FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_registration (event_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table STREAMS
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS streams (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            stream_url VARCHAR(500) NOT NULL,
            platform VARCHAR(50) DEFAULT 'twitch',
            thumbnail_url VARCHAR(500),
            streamer_name VARCHAR(100),
            is_live TINYINT(1) DEFAULT 0,
            scheduled_at DATETIME,
            started_at DATETIME,
            ended_at DATETIME,
            viewer_count INT DEFAULT 0,
            created_by INT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_live (is_live),
            INDEX idx_scheduled (scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table GALLERY
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            description TEXT,
            image_url VARCHAR(500) NOT NULL,
            category VARCHAR(50) DEFAULT 'general',
            tags TEXT,
            uploaded_by INT,
            is_featured TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_category (category),
            INDEX idx_featured (is_featured)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table TOURNAMENTS
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tournaments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            game_id INT,
            tournament_type VARCHAR(50) DEFAULT 'single_elimination',
            prize_pool DECIMAL(10,2) DEFAULT 0,
            prize_currency VARCHAR(10) DEFAULT 'XOF',
            max_participants INT,
            entry_fee DECIMAL(10,2) DEFAULT 0,
            start_date DATETIME NOT NULL,
            end_date DATETIME,
            registration_deadline DATETIME,
            status VARCHAR(20) DEFAULT 'upcoming',
            image_url VARCHAR(500),
            rules TEXT,
            created_by INT,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_status (status),
            INDEX idx_dates (start_date, end_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table TOURNAMENT_PARTICIPANTS
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tournament_participants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT NOT NULL,
            user_id INT NOT NULL,
            team_name VARCHAR(100),
            status VARCHAR(20) DEFAULT 'registered',
            seed INT,
            final_rank INT,
            prize_won DECIMAL(10,2),
            registered_at DATETIME NOT NULL,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_participant (tournament_id, user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Table TOURNAMENT_MATCHES
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS tournament_matches (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tournament_id INT NOT NULL,
            round INT NOT NULL,
            match_number INT NOT NULL,
            participant1_id INT,
            participant2_id INT,
            winner_id INT,
            score_p1 INT DEFAULT 0,
            score_p2 INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            scheduled_at DATETIME,
            played_at DATETIME,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (tournament_id) REFERENCES tournaments(id) ON DELETE CASCADE,
            FOREIGN KEY (participant1_id) REFERENCES tournament_participants(id) ON DELETE SET NULL,
            FOREIGN KEY (participant2_id) REFERENCES tournament_participants(id) ON DELETE SET NULL,
            FOREIGN KEY (winner_id) REFERENCES tournament_participants(id) ON DELETE SET NULL,
            INDEX idx_tournament_round (tournament_id, round)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    $pdo->commit();
    
    json_response([
        'success' => true,
        'message' => 'Toutes les tables de contenu et tournois ont été créées avec succès !',
        'tables' => [
            'news',
            'events',
            'event_registrations',
            'streams',
            'gallery',
            'tournaments',
            'tournament_participants',
            'tournament_matches'
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['error' => 'Erreur lors de la création des tables', 'details' => $e->getMessage()], 500);
}
