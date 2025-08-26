<?php

namespace Cryptochart\Indicators;

/**
 * Exponential Moving Average Calculator
 * 
 * Original implementation for calculating EMA values
 */
class EMA
{
    /**
     * Calculate Exponential Moving Average
     * 
     * @param array $prices Array of price values
     * @param int $period Period for EMA calculation
     * @return array EMA values
     */
    public static function calculate(array $prices, int $period): array
    {
        if (empty($prices)) {
            throw new \InvalidArgumentException('Price array cannot be empty');
        }

        if ($period <= 0) {
            throw new \InvalidArgumentException('Period must be positive');
        }

        $count = count($prices);
        if ($count < $period) {
            throw new \InvalidArgumentException('Insufficient data for period');
        }

        $k = 2.0 / ($period + 1);
        $ema = [];

        // Initialize with SMA for first value
        $sum = 0;
        for ($i = 0; $i < $period; $i++) {
            $sum += $prices[$i];
        }
        $ema[0] = $sum / $period;

        // Calculate EMA for remaining values
        for ($i = 1; $i < $count; $i++) {
            $ema[$i] = ($prices[$i] * $k) + ($ema[$i - 1] * (1 - $k));
        }

        return $ema;
    }

    /**
     * Get smoothing factor for period
     */
    public static function getSmoothingFactor(int $period): float
    {
        return 2.0 / ($period + 1);
    }
}