<?php

namespace Cryptochart\Indicators;

/**
 * Stochastic RSI Calculator
 */
class StochRSI
{
    /**
     * Calculate Stochastic RSI
     * 
     * @param array $prices Price values
     * @param int $rsiPeriod RSI period
     * @param int $kPeriod K period for smoothing
     * @param int $dPeriod D period for smoothing
     * @return array StochRSI values
     */
    public static function calculate(array $prices, int $rsiPeriod = 14, int $kPeriod = 3, int $dPeriod = 3): array
    {
        // First calculate RSI
        $rsiValues = RSI::calculate($prices, $rsiPeriod);
        
        if (count($rsiValues) < $kPeriod) {
            throw new \InvalidArgumentException('Not enough RSI values for StochRSI calculation');
        }

        $stochRSI = [];

        // Calculate StochRSI for each window
        for ($i = $kPeriod - 1; $i < count($rsiValues); $i++) {
            $window = array_slice($rsiValues, $i - $kPeriod + 1, $kPeriod);
            $minRSI = min($window);
            $maxRSI = max($window);
            
            if ($maxRSI - $minRSI == 0) {
                $stochRSI[] = 0;
            } else {
                $currentRSI = $rsiValues[$i];
                $stochRSI[] = (($currentRSI - $minRSI) / ($maxRSI - $minRSI)) * 100;
            }
        }

        return $stochRSI;
    }
}