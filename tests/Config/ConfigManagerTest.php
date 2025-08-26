<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../autoload.php';

use Cryptochart\Config\ConfigManager;

class ConfigManagerTest extends TestCase
{
    public function testSingletonInstance()
    {
        $instance1 = ConfigManager::getInstance();
        $instance2 = ConfigManager::getInstance();
        
        $this->assertSame($instance1, $instance2);
    }

    public function testGetConfiguration()
    {
        $config = ConfigManager::getInstance();
        
        // Test getting API configuration
        $apiConfig = $config->getApiConfig();
        $this->assertIsArray($apiConfig);
        $this->assertArrayHasKey('binance_base_url', $apiConfig);
        
        // Test getting indicators configuration
        $indicatorsConfig = $config->getIndicatorsConfig();
        $this->assertIsArray($indicatorsConfig);
        $this->assertArrayHasKey('ema', $indicatorsConfig);
        $this->assertArrayHasKey('macd', $indicatorsConfig);
    }

    public function testDotNotationAccess()
    {
        $config = ConfigManager::getInstance();
        
        // Test dot notation access
        $baseUrl = $config->get('api.binance_base_url');
        $this->assertIsString($baseUrl);
        
        // Test with default value
        $nonExistent = $config->get('non.existent.key', 'default');
        $this->assertEquals('default', $nonExistent);
    }

    public function testHasMethod()
    {
        $config = ConfigManager::getInstance();
        
        $this->assertTrue($config->has('api'));
        $this->assertFalse($config->has('non.existent.key'));
    }

    public function testEnvironmentMethods()
    {
        $config = ConfigManager::getInstance();
        
        // These should return boolean values
        $this->assertIsBool($config->isDebugMode());
        $this->assertIsBool($config->isProduction());
    }

    public function testCacheConfiguration()
    {
        $config = ConfigManager::getInstance();
        
        $cacheConfig = $config->getCacheConfig();
        $this->assertIsArray($cacheConfig);
        $this->assertArrayHasKey('enabled', $cacheConfig);
        $this->assertArrayHasKey('ttl', $cacheConfig);
    }

    public function testSecurityConfiguration()
    {
        $config = ConfigManager::getInstance();
        
        $securityConfig = $config->getSecurityConfig();
        $this->assertIsArray($securityConfig);
        $this->assertArrayHasKey('cors_enabled', $securityConfig);
    }
}