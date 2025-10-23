<?php
/**
 * Système de Monitoring Simple
 * Surveille les performances et la santé du système
 */

class SystemMonitor {
    private $metricsFile;
    
    public function __construct() {
        $this->metricsFile = __DIR__ . '/../logs/metrics.json';
        
        // Créer le dossier logs s'il n'existe pas
        $logDir = dirname($this->metricsFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Enregistrer une métrique
     */
    public function record($metric, $value, $tags = []) {
        $entry = [
            'metric' => $metric,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s')
        ];
        
        // Ajouter au fichier de métriques
        file_put_contents(
            $this->metricsFile,
            json_encode($entry) . "\n",
            FILE_APPEND
        );
    }
    
    /**
     * Surveiller le temps d'exécution d'une fonction
     */
    public function time($name, callable $callback) {
        $start = microtime(true);
        $result = $callback();
        $duration = (microtime(true) - $start) * 1000; // en ms
        
        $this->record('execution_time', $duration, ['name' => $name]);
        
        return $result;
    }
    
    /**
     * Vérifier la santé du système
     */
    public function healthCheck() {
        $health = [
            'status' => 'healthy',
            'timestamp' => time(),
            'checks' => []
        ];
        
        // Vérifier la connexion BD
        try {
            require_once __DIR__ . '/db.php';
            $pdo = get_db_connection();
            $pdo->query("SELECT 1");
            $health['checks']['database'] = 'ok';
        } catch (Exception $e) {
            $health['checks']['database'] = 'error';
            $health['status'] = 'unhealthy';
        }
        
        // Vérifier l'espace disque
        $diskFree = disk_free_space(__DIR__);
        $diskTotal = disk_total_space(__DIR__);
        $diskPercent = round(($diskFree / $diskTotal) * 100, 2);
        
        $health['checks']['disk_space'] = [
            'free' => $this->formatBytes($diskFree),
            'total' => $this->formatBytes($diskTotal),
            'percent_free' => $diskPercent
        ];
        
        if ($diskPercent < 10) {
            $health['status'] = 'warning';
        }
        
        // Vérifier la mémoire
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        
        $health['checks']['memory'] = [
            'used' => $this->formatBytes($memoryUsage),
            'limit' => $memoryLimit
        ];
        
        // Vérifier le dossier logs
        $logDir = __DIR__ . '/../logs';
        $health['checks']['logs_writable'] = is_writable($logDir) ? 'ok' : 'error';
        
        return $health;
    }
    
    /**
     * Obtenir les statistiques récentes
     */
    public function getStats($hours = 24) {
        if (!file_exists($this->metricsFile)) {
            return [];
        }
        
        $lines = file($this->metricsFile);
        $cutoff = time() - ($hours * 3600);
        $stats = [];
        
        foreach ($lines as $line) {
            $entry = json_decode(trim($line), true);
            
            if ($entry && $entry['timestamp'] >= $cutoff) {
                $metric = $entry['metric'];
                
                if (!isset($stats[$metric])) {
                    $stats[$metric] = [
                        'count' => 0,
                        'sum' => 0,
                        'min' => PHP_FLOAT_MAX,
                        'max' => PHP_FLOAT_MIN,
                        'values' => []
                    ];
                }
                
                $value = $entry['value'];
                $stats[$metric]['count']++;
                $stats[$metric]['sum'] += $value;
                $stats[$metric]['min'] = min($stats[$metric]['min'], $value);
                $stats[$metric]['max'] = max($stats[$metric]['max'], $value);
                $stats[$metric]['values'][] = $value;
            }
        }
        
        // Calculer les moyennes
        foreach ($stats as $metric => &$data) {
            $data['avg'] = $data['sum'] / $data['count'];
            unset($data['values']); // Ne pas retourner toutes les valeurs
        }
        
        return $stats;
    }
    
    /**
     * Formater les bytes en format lisible
     */
    private function formatBytes($bytes) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}

/**
 * Instance globale du moniteur
 */
function get_monitor() {
    static $monitor = null;
    
    if ($monitor === null) {
        $monitor = new SystemMonitor();
    }
    
    return $monitor;
}

/**
 * Endpoint de health check
 */
if (basename($_SERVER['PHP_SELF']) === 'monitoring.php') {
    header('Content-Type: application/json');
    $monitor = new SystemMonitor();
    echo json_encode($monitor->healthCheck(), JSON_PRETTY_PRINT);
}
