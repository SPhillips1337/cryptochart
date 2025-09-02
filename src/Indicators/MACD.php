<?php

namespace Cryptochart\Indicators;

/**
 * Moving Average Convergence Divergence
 */
class MACD
{
    /**
     * Calculate MACD
     */
*/
    public static function calculate(array $prices, int $fast = 12, int $slow = 26, int $signal = 9): array
    {
        // Input validation
        if (empty($prices)) {
            throw new \InvalidArgumentException("Prices array cannot be empty");
        }
        if ($fast <= 0 || $slow <= 0 || $signal <= 0) {
            throw new \InvalidArgumentException("Periods must be positive integers");
        }
        if (count($prices) < max($fast, $slow, $signal)) {
            throw new \InvalidArgumentException("Insufficient data for calculation");
        }

        $fastEMA = EMA::calculate($prices, $fast);
        $slowEMA = EMA::calculate($prices, $slow);
    {
        $fastEMA = EMA::calculate($prices, $fast);
        $slowEMA = EMA::calculate($prices, $slow);

        $macd = [];
        $len = min(count($fastEMA), count($slowEMA));
        
        for ($i = 0; $i < $len; $i++) {
            $macd[] = $fastEMA[$i] - $slowEMA[$i];
        }

        $signalLine = count($macd) >= $signal ? EMA::calculate($macd, $signal) : [];

        $histogram = [];
        $histLen = min(count($macd), count($signalLine));
        
        for ($i = 0; $i < $histLen; $i++) {
            $histogram[] = $macd[$i] - $signalLine[$i];
        }

        return [
            'macd' => $macd,
            'signal' => $signalLine,
            'histogram' => $histogram
        ];
    }
}