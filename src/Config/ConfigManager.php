<?php

namespace Cryptochart\Config;

/**
 * Configuration Manager
 * 
 * Handles loading and accessing configuration values
 */
class ConfigManager
{
    private static ?array $config = null;
    private static ?self $instance = null;

    private function __construct()
    {
        $this->loadConfig();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load configuration from file
     */
    private function loadConfig(): void
    {
        $configPath = __DIR__ . '/../../config/config.php';
        
        if (!file_exists($configPath)) {
            throw new \RuntimeException('Configuration file not found. Please copy config.example.php to config.php');
        }

        self::$config = require $configPath;
        
        // Set timezone
        if (isset(self::$config['timezone'])) {
            date_default_timezone_set(self::$config['timezone']);
        }
    }

    /**
     * Get configuration value using dot notation
     * 
     * @param string $key Configuration key (e.g., 'api.binance_base_url')
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Get all configuration
     */
    public function getAll(): array
    {
        return self::$config ?? [];
    }

    /**
     * Check if configuration key exists
     */
    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    /**
     * Get API configuration
     */
    public function getApiConfig(): array
    {
        return $this->get('api', []);
    }

    /**
     * Get indicators configuration
     */
    public function getIndicatorsConfig(): array
    {
        return $this->get('indicators', []);
    }

    /**
     * Get cache configuration
     */
    public function getCacheConfig(): array
    {
        return $this->get('cache', []);
    }

    /**
     * Get rate limit configuration
     */
    public function getRateLimitConfig(): array
    {
        return $this->get('rate_limit', []);
    }

    /**
     * Get security configuration
     */
    public function getSecurityConfig(): array
    {
        return $this->get('security', []);
    }

    /**
     * Check if debug mode is enabled
     */
    public function isDebugMode(): bool
    {
        return $this->get('debug', false);
    }

    /**
     * Check if production environment
     */
    public function isProduction(): bool
    {
        return $this->get('environment') === 'production';
    }
}