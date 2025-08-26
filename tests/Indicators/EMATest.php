<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../autoload.php';

use Cryptochart\Indicators\EMA;

class EMATest extends TestCase
{
    public function testEMACalculation()
    {
        $prices = [10, 12, 13, 12, 15, 16, 14, 13, 15, 17];
        $period = 3;
        
        $ema = EMA::calculate($prices, $period);
        
        // Should return array with same length as input
        $this->assertCount(count($prices), $ema);
        
        // First value should be SMA of first 3 values
        $expectedFirstValue = (10 + 12 + 13) / 3;
        $this->assertEquals($expectedFirstValue, $ema[0], '', 0.01);
        
        // EMA values should be numeric
        foreach ($ema as $value) {
            $this->assertIsNumeric($value);
        }
    }

    public function testEMAWithInsufficientData()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $prices = [10, 12];
        $period = 5;
        
        EMA::calculate($prices, $period);
    }

    public function testEMAWithEmptyArray()
    {
        $this->expectException(InvalidArgumentException::class);
        
        EMA::calculate([], 5);
    }

    public function testEMAWithInvalidPeriod()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $prices = [10, 12, 13, 14, 15];
        
        EMA::calculate($prices, 0);
    }

    public function testSmoothingFactor()
    {
        $period = 10;
        $expected = 2 / ($period + 1);
        
        $actual = EMA::getSmoothingFactor($period);
        
        $this->assertEquals($expected, $actual, '', 0.0001);
    }

    public function testEMAIncreasingTrend()
    {
        $prices = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
        $period = 3;
        
        $ema = EMA::calculate($prices, $period);
        
        // In an increasing trend, EMA should generally increase
        $this->assertGreaterThan($ema[0], $ema[count($ema) - 1]);
    }

    public function testEMADecreasingTrend()
    {
        $prices = [20, 19, 18, 17, 16, 15, 14, 13, 12, 11];
        $period = 3;
        
        $ema = EMA::calculate($prices, $period);
        
        // In a decreasing trend, EMA should generally decrease
        $this->assertLessThan($ema[0], $ema[count($ema) - 1]);
    }
}