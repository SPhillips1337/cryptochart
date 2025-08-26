<?php

namespace Cryptochart\Security;

use Cryptochart\Config\ConfigManager;

/**
 * Rate Limiter for API endpoints
 */
class RateLimiter
{
    private ConfigManager $config;
    private array $rateLimitConfig;
    private string $storageDir;

    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->rateLimitConfig = $this->config->getRateLimitConfig();
        $this->storageDir = __DIR__ . '/../../cache/rate_limits';
        
        $this->ensureStorageDirectory();
    }

    /**
     * Check if request is allowed based on rate limits
     */
    public function isAllowed(string $clientId = null): bool
    {
        if (!$this->rateLimitConfig['enabled']) {
            return true;
        }

        $clientId = $clientId ?? $this->getClientId();
        
        // Check minute limit
        if (!$this->checkLimit($clientId, 'minute', $this->rateLimitConfig['requests_per_minute'], 60)) {
            return false;
        }
        
        // Check hour limit
        if (!$this->checkLimit($clientId, 'hour', $this->rateLimitConfig['requests_per_hour'], 3600)) {
            return false;
        }

        return true;
    }

    /**
     * Record a request for rate limiting
     */
    public function recordRequest(string $clientId = null): void
    {
        if (!$this->rateLimitConfig['enabled']) {
            return;
        }

        $clientId = $clientId ?? $this->getClientId();
        $now = time();
        
        // Record for minute window
        $this->recordRequestForWindow($clientId, 'minute', $now);
        
        // Record for hour window
        $this->recordRequestForWindow($clientId, 'hour', $now);
    }

    /**
     * Get remaining requests for client
     */
    public function getRemainingRequests(string $clientId = null): array
    {
        if (!$this->rateLimitConfig['enabled']) {
            return [
                'minute' => $this->rateLimitConfig['requests_per_minute'],
                'hour' => $this->rateLimitConfig['requests_per_hour']
            ];
        }

        $clientId = $clientId ?? $this->getClientId();
        
        $minuteCount = $this->getRequestCount($clientId, 'minute', 60);
        $hourCount = $this->getRequestCount($clientId, 'hour', 3600);
        
        return [
            'minute' => max(0, $this->rateLimitConfig['requests_per_minute'] - $minuteCount),
            'hour' => max(0, $this->rateLimitConfig['requests_per_hour'] - $hourCount)
        ];
    }

    /**
     * Check rate limit for specific window
     */
    private function checkLimit(string $clientId, string $window, int $limit, int $windowSize): bool
    {
        $count = $this->getRequestCount($clientId, $window, $windowSize);
        return $count < $limit;
    }

    /**
     * Get request count for time window
     */
    private function getRequestCount(string $clientId, string $window, int $windowSize): int
    {
        $filename = $this->getStorageFilename($clientId, $window);
        
        if (!file_exists($filename)) {
            return 0;
        }

        $data = file_get_contents($filename);
        if ($data === false) {
            return 0;
        }

        $requests = json_decode($data, true);
        if (!is_array($requests)) {
            return 0;
        }

        $now = time();
        $cutoff = $now - $windowSize;
        
        // Count requests within the time window
        $count = 0;
        foreach ($requests as $timestamp) {
            if ($timestamp > $cutoff) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Record request for specific window
     */
    private function recordRequestForWindow(string $clientId, string $window, int $timestamp): void
    {
        $filename = $this->getStorageFilename($clientId, $window);
        
        $requests = [];
        if (file_exists($filename)) {
            $data = file_get_contents($filename);
            if ($data !== false) {
                $decoded = json_decode($data, true);
                if (is_array($decoded)) {
                    $requests = $decoded;
                }
            }
        }

        // Add current request
        $requests[] = $timestamp;
        
        // Clean old requests (keep only last 100 for efficiency)
        $requests = array_slice($requests, -100);
        
        // Save back to file
        file_put_contents($filename, json_encode($requests), LOCK_EX);
    }

    /**
     * Get client identifier
     */
    private function getClientId(): string
    {
        // Use IP address as client identifier
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // If behind proxy, try to get real IP
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($forwarded[0]);
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        }

        return hash('sha256', $ip . $this->config->get('security.salt', 'default_salt'));
    }

    /**
     * Get storage filename for client and window
     */
    private function getStorageFilename(string $clientId, string $window): string
    {
        return $this->storageDir . '/' . $clientId . '_' . $window . '.json';
    }

    /**
     * Ensure storage directory exists
     */
    private function ensureStorageDirectory(): void
    {
        if (!is_dir($this->storageDir)) {
            if (!mkdir($this->storageDir, 0755, true)) {
                throw new \RuntimeException('Failed to create rate limit storage directory');
            }
        }
    }

    /**
     * Clean old rate limit files
     */
    public function cleanup(): int
    {
        $files = glob($this->storageDir . '/*.json');
        $cleaned = 0;
        $cutoff = time() - 3600; // Clean files older than 1 hour

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                if (unlink($file)) {
                    $cleaned++;
                }
            }
        }

        return $cleaned;
    }
}