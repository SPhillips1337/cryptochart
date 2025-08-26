<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../autoload.php';

use Cryptochart\Indicators\MACD;

class MACDTest extends TestCase
{
    public function testMACDCalculation()
    {
        // Create test data with enough points for MACD calculation
        $prices = [];
        for ($i = 1; $i <= 50; $i++) {
            $prices[] = 100 + sin($i * 0.1) * 10; // Sine wave pattern
        }
        
        $result = MACD::calculate($prices, 12, 26, 9);
        
        // Should return array with required keys
        $this->assertArrayHasKey('macd', $result);
        $this->assertArrayHasKey('signal', $result);
        $this->assertArrayHasKey('histogram', $result);
        
        // MACD line should have values
        $this->assertNotEmpty($result['macd']);
        
        // All values should be numeric
        foreach ($result['macd'] as $value) {
            $this->assertIsNumeric($value);
        }
        
        if (!empty($result['signal'])) {
            foreach ($result['signal'] as $value) {
                $this->assertIsNumeric($value);
            }
        }
        
        if (!empty($result['histogram'])) {
            foreach ($result['histogram'] as $value) {
                $this->assertIsNumeric($value);
            }
        }
    }

    public function testMACDWithInsufficientData()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $prices = [10, 12, 13]; // Not enough for MACD
        
        MACD::calculate($prices, 12, 26, 9);
    }

    public function testMACDHistogramCalculation()
    {
        $prices = [];
        for ($i = 1; $i <= 50; $i++) {
            $prices[] = 100 + $i * 0.5; // Trending upward
        }
        
        $result = MACD::calculate($prices, 5, 10, 3);
        
        // If we have both MACD and signal, histogram should be their difference
        if (!empty($result['signal']) && !empty($result['histogram'])) {
            $minLength = min(count($result['macd']), count($result['signal']));
            
            for ($i = 0; $i < min($minLength, count($result['histogram'])); $i++) {
                $expectedHistogram = $result['macd'][$i] - $result['signal'][$i];
                $this->assertEquals($expectedHistogram, $result['histogram'][$i], '', 0.0001);
            }
        }
    }

    public function testMACDWithCustomPeriods()
    {
        $prices = [];
        for ($i = 1; $i <= 40; $i++) {
            $prices[] = 100 + cos($i * 0.2) * 5;
        }
        
        $result = MACD::calculate($prices, 5, 15, 5);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('macd', $result);
        $this->assertNotEmpty($result['macd']);
    }
}