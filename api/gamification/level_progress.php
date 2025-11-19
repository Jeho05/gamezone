<?php
// api/gamification/level_progress.php
// Get level progression for the authenticated user
require_once __DIR__ . '/../utils.php';
require_method(['GET']);

$user = require_auth();
$pdo = get_db();
$userId = (int)$user['id'];

// Get all levels
$stmt = $pdo->query('SELECT level_number, name, points_required, points_bonus, color FROM levels ORDER BY level_number ASC');
$levels = $stmt->fetchAll();

if (!$levels) {
    json_response(['error' => 'Aucun niveau défini'], 500);
}

$userPoints = (int)$user['points'];

// Determine current and next level based on user points
$currentLevel = null;
$nextLevel = null;

foreach ($levels as $idx => $level) {
    $required = (int)$level['points_required'];

    if ($userPoints >= $required) {
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
    // Default to first level if user has very few points
    $currentLevel = $levels[0];
    $nextLevel = isset($levels[1]) ? $levels[1] : null;
}

// Calculate progress towards next level
$progressPercentage = 0;
$pointsToNext = 0;
$nextLevelPoints = $userPoints;

if ($nextLevel) {
    $currentRequired = (int)$currentLevel['points_required'];
    $nextRequired = (int)$nextLevel['points_required'];
    $nextLevelPoints = $nextRequired;
    $pointsToNext = max(0, $nextRequired - $userPoints);

    if ($nextRequired > $currentRequired) {
        $progressPercentage = (int)((($userPoints - $currentRequired) / ($nextRequired - $currentRequired)) * 100);
        $progressPercentage = max(0, min(100, $progressPercentage));
    }
} else {
    // Max level reached: bar full
    $progressPercentage = 100;
    $nextLevelPoints = $userPoints > 0 ? $userPoints : (int)$currentLevel['points_required'];
    $pointsToNext = 0;
}

// Build list of all levels with unlocked flag
$allLevels = array_map(function ($level) use ($userPoints) {
    return [
        'number' => (int)$level['level_number'],
        'name' => $level['name'],
        'points_required' => (int)$level['points_required'],
        'points_bonus' => (int)$level['points_bonus'],
        'color' => $level['color'],
        'unlocked' => $userPoints >= (int)$level['points_required'],
    ];
}, $levels);

json_response([
    'level' => [
        'level' => (int)$currentLevel['level_number'],
        'level_name' => $currentLevel['name'],
        'current_level_points' => $userPoints,
        'next_level_points' => $nextLevelPoints,
        'progress_percentage' => $progressPercentage,
        'points_to_next' => $pointsToNext,
    ],
    'user_points' => $userPoints,
    'all_levels' => $allLevels,
]);
