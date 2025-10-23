<?php
/**
 * Système de Cache Simple
 * En attendant Redis, utilise le cache fichier
 */

class SimpleCache {
    private $cacheDir;
    private $defaultTTL = 3600; // 1 heure par défaut
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../cache';
        
        // Créer le dossier cache s'il n'existe pas
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Récupérer une valeur du cache
     */
    public function get($key) {
        $filename = $this->getCacheFile($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($filename));
        
        // Vérifier si le cache a expiré
        if ($data['expires_at'] < time()) {
            $this->delete($key);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Stocker une valeur dans le cache
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?? $this->defaultTTL;
        $filename = $this->getCacheFile($key);
        
        $data = [
            'value' => $value,
            'expires_at' => time() + $ttl,
            'created_at' => time()
        ];
        
        return file_put_contents($filename, serialize($data)) !== false;
    }
    
    /**
     * Supprimer une valeur du cache
     */
    public function delete($key) {
        $filename = $this->getCacheFile($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }
        
        return true;
    }
    
    /**
     * Vider tout le cache
     */
    public function clear() {
        $files = glob($this->cacheDir . '/*');
        
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        
        return true;
    }
    
    /**
     * Vérifier si une clé existe dans le cache
     */
    public function has($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Récupérer ou créer une valeur (cache-aside pattern)
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Obtenir le chemin du fichier cache
     */
    private function getCacheFile($key) {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }
    
    /**
     * Nettoyer les caches expirés
     */
    public function cleanup() {
        $files = glob($this->cacheDir . '/*');
        $cleaned = 0;
        
        foreach ($files as $file) {
            if (is_file($file)) {
                $data = unserialize(file_get_contents($file));
                
                if ($data['expires_at'] < time()) {
                    unlink($file);
                    $cleaned++;
                }
            }
        }
        
        return $cleaned;
    }
}

/**
 * Instance globale du cache
 */
function get_cache() {
    static $cache = null;
    
    if ($cache === null) {
        $cache = new SimpleCache();
    }
    
    return $cache;
}
