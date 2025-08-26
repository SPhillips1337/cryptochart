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
    public static function calculate(array $prices, int $fast = 12, int $slow = 26, int $signal = 9): array
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