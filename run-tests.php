<?php

/**
 * Simple test runner for Cryptochart
 * 
 * This script provides a basic test runner when PHPUnit is not available
 */

require_once 'autoload.php';

class SimpleTestRunner
{
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function runTests(): void
    {
        echo "Running Cryptochart Tests\n";
        echo str_repeat("=", 50) . "\n\n";

        $this->testEMACalculation();
        $this->testMACDCalculation();
        $this->testConfigManager();
        $this->testCacheManager();

        $this->printResults();
    }

    private function testEMACalculation(): void
    {
        echo "Testing EMA Calculation...\n";
        
        try {
            $prices = [10, 12, 13, 12, 15, 16, 14, 13, 15, 17];
            $ema = \Cryptochart\Indicators\EMA::calculate($prices, 3);
            
            $this->assert(count($ema) === count($prices), "EMA should return same length as input");
            $this->assert(is_numeric($ema[0]), "EMA values should be numeric");
            
            // Test smoothing factor
            $factor = \Cryptochart\Indicators\EMA::getSmoothingFactor(10);
            $expected = 2 / 11;
            $this->assert(abs($factor - $expected) < 0.0001, "Smoothing factor calculation");
            
            echo "  ✓ EMA calculation tests passed\n";
            
        } catch (Exception $e) {
            $this->recordFailure("EMA Calculation", $e->getMessage());
        }
    }

    private function testMACDCalculation(): void
    {
        echo "Testing MACD Calculation...\n";
        
        try {
            $prices = [];
            for ($i = 1; $i <= 50; $i++) {
                $prices[] = 100 + sin($i * 0.1) * 10;
            }
            
            $result = \Cryptochart\Indicators\MACD::calculate($prices, 12, 26, 9);
            
            $this->assert(is_array($result), "MACD should return array");
            $this->assert(array_key_exists('macd', $result), "MACD should have 'macd' key");
            $this->assert(array_key_exists('signal', $result), "MACD should have 'signal' key");
            $this->assert(array_key_exists('histogram', $result), "MACD should have 'histogram' key");
            
            echo "  ✓ MACD calculation tests passed\n";
            
        } catch (Exception $e) {
            $this->recordFailure("MACD Calculation", $e->getMessage());
        }
    }

    private function testConfigManager(): void
    {
        echo "Testing Config Manager...\n";
        
        try {
            $config = \Cryptochart\Config\ConfigManager::getInstance();
            
            $this->assert($config instanceof \Cryptochart\Config\ConfigManager, "Should return ConfigManager instance");
            
            $apiConfig = $config->getApiConfig();
            $this->assert(is_array($apiConfig), "API config should be array");
            
            $baseUrl = $config->get('api.binance_base_url');
            $this->assert(is_string($baseUrl), "Base URL should be string");
            
            echo "  ✓ Config Manager tests passed\n";
            
        } catch (Exception $e) {
            $this->recordFailure("Config Manager", $e->getMessage());
        }
    }

    private function testCacheManager(): void
    {
        echo "Testing Cache Manager...\n";
        
        try {
            $cache = new \Cryptochart\Cache\CacheManager();
            
            // Test cache operations
            $testData = ['test' => 'data'];
            $key = 'test_key';
            
            $this->assert($cache->set($key, $testData), "Should be able to set cache");
            
            $retrieved = $cache->get($key);
            $this->assert($retrieved === $testData, "Should retrieve same data");
            
            $this->assert($cache->has($key), "Should confirm cache exists");
            
            $this->assert($cache->delete($key), "Should be able to delete cache");
            
            $this->assert($cache->get($key) === null, "Should return null after deletion");
            
            echo "  ✓ Cache Manager tests passed\n";
            
        } catch (Exception $e) {
            $this->recordFailure("Cache Manager", $e->getMessage());
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            $this->passed++;
        } else {
            $this->failed++;
            $this->failures[] = $message;
        }
    }

    private function recordFailure(string $test, string $message): void
    {
        $this->failed++;
        $this->failures[] = "$test: $message";
        echo "  ✗ $test failed: $message\n";
    }

    private function printResults(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Test Results:\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        if (!empty($this->failures)) {
            echo "\nFailures:\n";
            foreach ($this->failures as $failure) {
                echo "  - $failure\n";
            }
        }
        
        echo "\n" . ($this->failed === 0 ? "All tests passed! ✓" : "Some tests failed! ✗") . "\n";
    }
}

// Run tests
$runner = new SimpleTestRunner();
$runner->runTests();