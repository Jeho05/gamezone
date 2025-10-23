<?php
require_once __DIR__ . '/../utils.php';
require_method(['POST']);

$uploadBaseDir = dirname(__DIR__) . '/../uploads/files';
$publicBaseUrl = '/uploads/files';
if (!is_dir($uploadBaseDir)) {
    @mkdir($uploadBaseDir, 0777, true);
}

function random_name(string $ext): string {
    return bin2hex(random_bytes(8)) . ($ext ? ('.' . $ext) : '');
}

function save_bytes(string $data, ?string $suggestedExt = null, ?string $mime = null): array {
    global $uploadBaseDir, $publicBaseUrl;
    $ext = $suggestedExt ?? 'bin';
    $name = random_name($ext);
    $path = $uploadBaseDir . '/' . $name;
    file_put_contents($path, $data);
    if (!$mime) {
        $mime = mime_content_type($path) ?: 'application/octet-stream';
    }
    return ['url' => $publicBaseUrl . '/' . $name, 'mimeType' => $mime];
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';

try {
    if (stripos($contentType, 'multipart/form-data') !== false) {
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            json_response(['error' => 'Aucun fichier reçu'], 400);
        }
        $tmp = $_FILES['file']['tmp_name'];
        $mime = mime_content_type($tmp) ?: 'application/octet-stream';
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
        ];
        $ext = $map[$mime] ?? pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION) ?: 'bin';
        $bytes = file_get_contents($tmp);
        $saved = save_bytes($bytes, $ext, $mime);
        json_response($saved, 201);
    }

    if (stripos($contentType, 'application/json') !== false) {
        $input = get_json_input();
        if (isset($input['base64'])) {
            $b64 = $input['base64'];
            $mime = null;
            if (preg_match('#^data:([^;]+);base64,#i', $b64, $m)) {
                $mime = $m[1];
                $b64 = preg_replace('#^data:[^;]+;base64,#i', '', $b64);
            }
            $bytes = base64_decode($b64, true);
            if ($bytes === false) json_response(['error' => 'Base64 invalide'], 400);
            $map = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
            ];
            $ext = $mime && isset($map[$mime]) ? $map[$mime] : 'bin';
            $saved = save_bytes($bytes, $ext, $mime);
            json_response($saved, 201);
        }
        if (isset($input['url'])) {
            $url = $input['url'];
            $resp = @file_get_contents($url);
            if ($resp === false) json_response(['error' => 'Impossible de télécharger l\'URL fournie'], 400);
            // Best-effort mime detection
            $saved = save_bytes($resp, null, null);
            json_response($saved, 201);
        }
        json_response(['error' => 'Corps JSON invalide, fournissez "base64" ou "url"'], 400);
    }

    if (stripos($contentType, 'application/octet-stream') !== false || $contentType === '') {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) json_response(['error' => 'Flux vide'], 400);
        $saved = save_bytes($raw, null, null);
        json_response($saved, 201);
    }

    json_response(['error' => 'Type de contenu non supporté'], 415);
} catch (Throwable $e) {
    json_response(['error' => 'Échec du téléversement', 'details' => $e->getMessage()], 500);
}
