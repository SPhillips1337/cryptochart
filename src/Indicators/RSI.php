<?php

namespace Cryptochart\Indicators;

/**
 * RSI Calculator - Original Implementation
 */
class RSI
{
    /**
     * Calculate RSI
     * 
     * @param array $values Price values
     * @param int $period Calculation period
     * @return array RSI values
     */
    public static function calculate(array $values, int $period = 14): array
    {
        if (count($values) < $period + 1) {
            throw new \InvalidArgumentException('Not enough data');
        }

        $rsiValues = [];
        $gains = 0;
        $losses = 0;

        // Calculate initial gains and losses
        for ($i = 1; $i <= $period; $i++) {
            $change = $values[$i] - $values[$i - 1];
            if ($change > 0) {
                $gains += $change;
            } else {
                $losses += abs($change);
            }
        }

        $avgGain = $gains / $period;
        $avgLoss = $losses / $period;

        // Calculate RSI values
        for ($i = $period; $i < count($values); $i++) {
            if ($avgLoss == 0) {
                $rsiValues[] = 100;
                continue;
            }

            $rs = $avgGain / $avgLoss;
            $rsiValues[] = 100 - (100 / (1 + $rs));

            // Update averages for next iteration
            if ($i < count($values) - 1) {
                $change = $values[$i + 1] - $values[$i];
                if ($change > 0) {
                    $avgGain = (($avgGain * ($period - 1)) + $change) / $period;
                    $avgLoss = ($avgLoss * ($period - 1)) / $period;
                } else {
                    $avgGain = ($avgGain * ($period - 1)) / $period;
                    $avgLoss = (($avgLoss * ($period - 1)) + abs($change)) / $period;
                }
            }
        }

        return $rsiValues;
    }
}