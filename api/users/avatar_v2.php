<?php
/**
 * VERSION ULTRA-SIMPLE - Avatar upload avec débogage complet
 */

// Headers CORS en premier
header('Access-Control-Allow-Origin: https://gamezoneismo.vercel.app');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Activer les erreurs pour debug
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Ne pas afficher dans la réponse
ini_set('log_errors', '1');

try {
    require_once __DIR__ . '/../config.php';
    require_once __DIR__ . '/../utils.php';
} catch (Exception $e) {
    echo json_encode(['error' => 'Erreur chargement config', 'details' => $e->getMessage()]);
    exit;
}

// GET: Récupérer avatar
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $auth = require_auth();
        $user_id = (int)$auth['id'];
        $pdo = get_db();
        
        $stmt = $pdo->prepare('SELECT avatar_url FROM users WHERE id = ?');
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        echo json_encode(['avatar_url' => $user['avatar_url'] ?? null]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Vérifier authentification
try {
    $auth = require_auth();
    $user_id = (int)$auth['id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Non authentifié', 'details' => $e->getMessage()]);
    exit;
}

// Vérifier fichier
if (!isset($_FILES['avatar'])) {
    echo json_encode(['error' => 'Aucun fichier uploadé', 'files' => array_keys($_FILES)]);
    exit;
}

if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'Fichier trop volumineux (limite PHP)',
        UPLOAD_ERR_FORM_SIZE => 'Fichier trop volumineux (limite formulaire)',
        UPLOAD_ERR_PARTIAL => 'Upload partiel',
        UPLOAD_ERR_NO_FILE => 'Aucun fichier',
        UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
        UPLOAD_ERR_CANT_WRITE => 'Échec écriture',
        UPLOAD_ERR_EXTENSION => 'Extension PHP bloquée'
    ];
    $errorMsg = $errors[$_FILES['avatar']['error']] ?? 'Erreur inconnue';
    echo json_encode(['error' => $errorMsg, 'code' => $_FILES['avatar']['error']]);
    exit;
}

$file = $_FILES['avatar'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileName = $file['name'];

// Validation extension
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode([
        'error' => 'Format non autorisé',
        'extension' => $fileExtension,
        'allowed' => $allowedExtensions
    ]);
    exit;
}

// Validation taille (2MB)
if ($fileSize > 2 * 1024 * 1024) {
    echo json_encode([
        'error' => 'Fichier trop volumineux',
        'size' => $fileSize,
        'max' => 2 * 1024 * 1024
    ]);
    exit;
}

// Lire le fichier
$imageData = @file_get_contents($fileTmpName);
if ($imageData === false) {
    echo json_encode(['error' => 'Impossible de lire le fichier']);
    exit;
}

// Encoder en base64
$base64Image = base64_encode($imageData);
$mimeType = 'image/' . ($fileExtension === 'jpg' ? 'jpeg' : $fileExtension);

try {
    $pdo = get_db();
    
    // Stocker directement dans users en data URL (plus simple)
    $dataUrl = "data:$mimeType;base64," . $base64Image;
    
    $stmt = $pdo->prepare('UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE id = ?');
    $success = $stmt->execute([$dataUrl, $user_id]);
    
    if (!$success) {
        echo json_encode(['error' => 'Échec UPDATE SQL', 'pdo_error' => $stmt->errorInfo()]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Avatar enregistré',
        'avatar_url' => $dataUrl,
        'size' => strlen($base64Image),
        'method' => 'inline_data_url'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Erreur base de données',
        'details' => $e->getMessage(),
        'code' => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Erreur inattendue',
        'details' => $e->getMessage()
    ]);
}
