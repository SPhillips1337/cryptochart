<?php

namespace Cryptochart\Cache;

use Cryptochart\Config\ConfigManager;

/**
 * Cache Manager for storing and retrieving cached data
 */
class CacheManager
{
    private ConfigManager $config;
    private array $cacheConfig;
    private string $cacheDir;

    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->cacheConfig = $this->config->getCacheConfig();
        $this->cacheDir = $this->cacheConfig['directory'] ?? __DIR__ . '/../../cache';
        
        $this->ensureCacheDirectory();
    }

    /**
     * Get cached data by key
     */
    public function get(string $key): ?array
    {
        if (!$this->cacheConfig['enabled']) {
            return null;
        }

        $filename = $this->getCacheFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            return null;
        }

        $cached = json_decode($data, true);
        if ($cached === null) {
            return null;
        }

        // Check if cache has expired
        if (isset($cached['expires']) && time() > $cached['expires']) {
            $this->delete($key);
            return null;
        }

        return $cached['data'] ?? null;
    }

    /**
     * Store data in cache
     */
    public function set(string $key, array $data, int $ttl = null): bool
    {
        if (!$this->cacheConfig['enabled']) {
            return false;
        }

        $ttl = $ttl ?? $this->cacheConfig['ttl'];
        $filename = $this->getCacheFilename($key);

        $cacheData = [
            'data' => $data,
            'created' => time(),
            'expires' => time() + $ttl
        ];

        $json = json_encode($cacheData);
        if ($json === false) {
            return false;
        }

        return file_put_contents($filename, $json, LOCK_EX) !== false;
    }

    /**
     * Delete cached data
     */
    public function delete(string $key): bool
    {
        $filename = $this->getCacheFilename($key);
        
        if (file_exists($filename)) {
            return unlink($filename);
        }

        return true;
    }

    /**
     * Clear all cache
     */
    public function clear(): bool
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            if (!unlink($file)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if cache exists and is valid
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Generate cache filename from key
     */
    private function getCacheFilename(string $key): string
    {
        $hash = md5($key);
        return $this->cacheDir . '/' . $hash . '.cache';
    }

    /**
     * Ensure cache directory exists
     */
    private function ensureCacheDirectory(): void
    {
        if (!is_dir($this->cacheDir)) {
            if (!mkdir($this->cacheDir, 0755, true)) {
                throw new \RuntimeException('Failed to create cache directory: ' . $this->cacheDir);
            }
        }

        if (!is_writable($this->cacheDir)) {
            throw new \RuntimeException('Cache directory is not writable: ' . $this->cacheDir);
        }
    }

    /**
     * Generate cache key for market data
     */
    public function generateMarketDataKey(string $symbol, string $interval, int $limit): string
    {
        return "market_data_{$symbol}_{$interval}_{$limit}";
    }

    /**
     * Clean expired cache files
     */
    public function cleanExpired(): int
    {
        $files = glob($this->cacheDir . '/*.cache');
        $cleaned = 0;

        foreach ($files as $file) {
            $data = file_get_contents($file);
            if ($data === false) {
                continue;
            }

            $cached = json_decode($data, true);
            if ($cached === null) {
                continue;
            }

            if (isset($cached['expires']) && time() > $cached['expires']) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}