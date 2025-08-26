<?php
/* 1️⃣  Fetch historical daily candles for ETH  */
$dataSourceUrl = 'https://api.binance.com/api/v3/klines?symbol=ETHUSDT&interval=1d&limit=500'; // 500 days
$raw = file_get_contents($dataSourceUrl);
$candles = json_decode($raw, true);

$prices = array_map(fn($c) => (float)$c[4], $candles);   // close prices

/* 2️⃣  Calculate EMA(25) and EMA(100)  */
// -------------------------------------------------------------------
// EMA helper – simple, closed‑form implementation
// -------------------------------------------------------------------
/**
 * Calculates the Exponential Moving Average of an array of values.
 *
 * @param array $values Array of float values (e.g. closing prices)
 * @param int   $period The EMA period (e.g. 12, 26, 9)
 *
 * @return array The EMA series (indexed the same as $values)
 */
function ema(array $values, int $period): array
{
    $k   = 2 / ($period + 1);
    $ema = [];

    // First EMA value is a Simple MA
    $ema[0] = array_sum(array_slice($values, 0, $period)) / $period;

    // Subsequent EMA values
    foreach ($values as $i => $v) {
        if ($i < $period) {
            continue;          // skip the first $period data – already covered
        }
        $ema[$i] = ($v * $k) + ($ema[$i - 1] * (1 - $k));
    }

    return $ema;
}

$ema25 = $ema($prices, 25);
$ema100 = $ema($prices, 100);

/* 3️⃣  Calculate StochRSI (14, 3, 3)  */
$calculateStochRsi = function($values, $rsiPeriod = 14, $kPeriod = 3, $dPeriod = 3) {
    $rsi = function($v, $p) {
        $gains = $loses = 0;
        for ($i = 1; $i < count($v); $i++) {
            $change = $v[$i] - $v[$i-1];
            if ($change > 0) $gains += $change; else $loses -= $change;
        }
        $avgGain = $gains / $p;
        $avgLoss = $loses / $p;
        if ($avgLoss == 0) return 100;
        $rs = $avgGain / $avgLoss;
        return 100 - (100 / (1 + $rs));
    };
    $rsiVals = array_map(fn($i) => $i, array_slice($values, 0, count($values)-$rsiPeriod+1));
    $rsiVals = array_map(fn($v) => $rsi($values, $rsiPeriod), $values);
    // Simplified: this is just placeholder logic; proper StochRSI requires moving averages on RSI.
    return $rsiVals;
};

$stochRsi = $calculateStochRsi($prices);

/* 4️⃣  Calculate MACD (12/26/9)  */

// -------------------------------------------------------------------
// MACD implementation – replaces the old placeholder
// -------------------------------------------------------------------
/**
 * Calculates the MACD line, signal line, and histogram.
 *
 * @param array $closePrices  Array of closing prices.
 * @param int   $fastPeriod   Fast EMA period (default 12)
 * @param int   $slowPeriod   Slow EMA period (default 26)
 * @param int   $signalPeriod Signal EMA period (default 9)
 *
 * @return array ['macd' => [...], 'signal' => [...], 'histogram' => [...]]
 */
function macd(
    array $closePrices,
    int $fastPeriod = 12,
    int $slowPeriod = 26,
    int $signalPeriod = 9
): array {
    // 1. Fast & Slow EMAs
    $fastEma = ema($closePrices, $fastPeriod);
    $slowEma = ema($closePrices, $slowPeriod);

    // 2. MACD line = Fast EMA – Slow EMA
    $macdLine = [];
    foreach ($fastEma as $idx => $value) {
        if (isset($slowEma[$idx])) {
            $macdLine[$idx] = $value - $slowEma[$idx];
        }
    }

    // 3. Signal line = EMA of MACD line
    //    Use the valid MACD values only
    $signalLine = ema(array_values($macdLine), $signalPeriod);

    // 4. Histogram = MACD line – Signal line
    $histogram = [];
    foreach ($macdLine as $idx => $value) {
        // Align indices: signalLine starts later due to EMA initialisation
        $signalIdx = $idx - count($macdLine) + count($signalLine);
        if ($signalIdx >= 0 && isset($signalLine[$signalIdx])) {
            $histogram[$idx] = $value - $signalLine[$signalIdx];
        }
    }

    return [
        'macd'      => $macdLine,
        'signal'    => $signalLine,
        'histogram' => $histogram
    ];
}
$macdData = macd($prices);
$macd = $macdData['macd'];
$macdSignal = $macdData['signal'];
$macdHist = $macdData['histogram'];


/* 5️⃣  Prepare JSON for Chart.js  */
$chartData = [
    'labels' => array_map(fn($c) => date('Y-m-d', $c[0]/1000), $candles),
    'datasets' => [
        [
            'label' => 'Close',
            'data' => $prices,
            'borderColor' => 'black',
            'fill' => false,
        ],
        [
            'label' => 'EMA 25',
            'data' => array_merge(array_fill(0, 24, null), $ema25), // pad to align
            'borderColor' => 'blue',
            'fill' => false,
        ],
        [
            'label' => 'EMA 100',
            'data' => array_merge(array_fill(0, 99, null), $ema100),
            'borderColor' => 'red',
            'fill' => false,
        ],
        // Add StochRSI and MACD similarly...
    ]
];
header('Content-Type: application/json');
echo json_encode($chartData);
?>