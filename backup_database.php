<?php
/**
 * Script de Backup Automatique de la Base de Données
 * À exécuter via CRON ou Planificateur de Tâches Windows
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300); // 5 minutes max

// Configuration
$dbHost = '127.0.0.1';
$dbName = 'gamezone';
$dbUser = 'root';
$dbPass = '';
$backupDir = __DIR__ . '/backups';
$maxBackups = 7; // Garder 7 derniers backups

echo "=== BACKUP BASE DE DONNEES ===\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Créer le dossier backups s'il n'existe pas
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
    echo "[OK] Dossier backups cree\n";
}

// Nom du fichier backup
$backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';

// Commande mysqldump
$mysqldumpPath = 'c:\\xampp\\mysql\\bin\\mysqldump.exe';
$command = sprintf(
    '"%s" --host=%s --user=%s --password=%s %s > "%s" 2>&1',
    $mysqldumpPath,
    $dbHost,
    $dbUser,
    $dbPass ? $dbPass : "''",
    $dbName,
    $backupFile
);

echo "Execution du backup...\n";
exec($command, $output, $returnCode);

if ($returnCode === 0 && file_exists($backupFile)) {
    $fileSize = filesize($backupFile);
    $fileSizeMB = round($fileSize / 1024 / 1024, 2);
    
    echo "[OK] Backup cree avec succes!\n";
    echo "Fichier: $backupFile\n";
    echo "Taille: $fileSizeMB MB\n\n";
    
    // Nettoyer les vieux backups
    echo "Nettoyage des anciens backups...\n";
    $backups = glob($backupDir . '/backup_*.sql');
    
    // Trier par date (les plus récents en premier)
    usort($backups, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Supprimer les backups au-delà de maxBackups
    $deleted = 0;
    for ($i = $maxBackups; $i < count($backups); $i++) {
        if (unlink($backups[$i])) {
            $deleted++;
            echo "  - Supprime: " . basename($backups[$i]) . "\n";
        }
    }
    
    if ($deleted > 0) {
        echo "[OK] $deleted ancien(s) backup(s) supprime(s)\n";
    } else {
        echo "[INFO] Aucun ancien backup a supprimer\n";
    }
    
    echo "\n[SUCCESS] Backup termine avec succes!\n";
    exit(0);
    
} else {
    echo "[ERREUR] Echec du backup!\n";
    if (!empty($output)) {
        echo "Details: " . implode("\n", $output) . "\n";
    }
    exit(1);
}
