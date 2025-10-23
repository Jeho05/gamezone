<?php
// api/gamification/levels.php
// Get level progression information
require_once __DIR__ . '/../utils.php';
require_method(['GET']);

$pdo = get_db();
$userId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Get all levels
$stmt = $pdo->query('SELECT level_number, name, points_required, points_bonus, color FROM levels ORDER BY level_number ASC');
$levels = $stmt->fetchAll();

if ($userId) {
    // Get user info
    $stmt = $pdo->prepare('SELECT points, level FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['error' => 'Utilisateur introuvable'], 404);
    }
    
    $userPoints = (int)$user['points'];
    $currentLevelName = $user['level'];
    
    // Find current level
    $currentLevel = null;
    $nextLevel = null;
    
    foreach ($levels as $idx => $level) {
        if ($level['name'] === $currentLevelName || $userPoints >= (int)$level['points_required']) {
            $currentLevel = $level;
            if (isset($levels[$idx + 1])) {
                $nextLevel = $levels[$idx + 1];
            }
        } else {
            if (!$nextLevel) {
                $nextLevel = $level;
            }
            break;
        }
    }
    
    if (!$currentLevel) {
        $currentLevel = $levels[0]; // Default to first level
        $nextLevel = isset($levels[1]) ? $levels[1] : null;
    }
    
    // Calculate progress to next level
    $progressPercentage = 0;
    $pointsToNext = 0;
    
    if ($nextLevel) {
        $currentRequired = (int)$currentLevel['points_required'];
        $nextRequired = (int)$nextLevel['points_required'];
        $pointsToNext = $nextRequired - $userPoints;
        
        if ($nextRequired > $currentRequired) {
            $progressPercentage = (int)((($userPoints - $currentRequired) / ($nextRequired - $currentRequired)) * 100);
            $progressPercentage = max(0, min(100, $progressPercentage));
        }
    } else {
        // Max level reached
        $progressPercentage = 100;
    }
    
    json_response([
        'user' => [
            'points' => $userPoints,
            'current_level' => [
                'number' => (int)$currentLevel['level_number'],
                'name' => $currentLevel['name'],
                'points_required' => (int)$currentLevel['points_required'],
                'color' => $currentLevel['color']
            ],
            'next_level' => $nextLevel ? [
                'number' => (int)$nextLevel['level_number'],
                'name' => $nextLevel['name'],
                'points_required' => (int)$nextLevel['points_required'],
                'points_bonus' => (int)$nextLevel['points_bonus'],
                'color' => $nextLevel['color']
            ] : null,
            'progress_percentage' => $progressPercentage,
            'points_to_next' => max(0, $pointsToNext)
        ],
        'all_levels' => array_map(function($level) use ($userPoints) {
            return [
                'number' => (int)$level['level_number'],
                'name' => $level['name'],
                'points_required' => (int)$level['points_required'],
                'points_bonus' => (int)$level['points_bonus'],
                'color' => $level['color'],
                'unlocked' => $userPoints >= (int)$level['points_required']
            ];
        }, $levels)
    ]);
} else {
    json_response([
        'levels' => array_map(function($level) {
            return [
                'number' => (int)$level['level_number'],
                'name' => $level['name'],
                'points_required' => (int)$level['points_required'],
                'points_bonus' => (int)$level['points_bonus'],
                'color' => $level['color']
            ];
        }, $levels)
    ]);
}
