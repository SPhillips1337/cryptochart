<?php
/* 1️⃣ Fetch historical daily candles for ETH */
$dataSourceUrl = 'https://api.binance.com/api/v3/klines?symbol=ETHUSDT&interval=1d&limit=500'; // 500 days
$raw = file_get_contents($dataSourceUrl);
$candles = json_decode($raw, true);

if ($candles === null) {
    die("Failed to fetch candle data.");
}

$prices = array_map(fn($c) => (float)$c[4], $candles);   // close prices

/* 2️⃣ Calculate EMA(25) and EMA(100) */
// -------------------------------------------------------------------
// EMA helper – simple, closed-form implementation
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
    $k = 2 / ($period + 1);
    $ema = [];

    // First EMA value is a Simple MA
    $ema[0] = array_sum(array_slice($values, 0, $period)) / $period;

    // Subsequent EMA values
    for ($i = 1; $i < count($values); $i++) {
        $ema[$i] = ($values[$i] * $k) + ($ema[$i - 1] * (1 - $k));
    }

    return $ema;
}

$ema25 = ema($prices, 25);
$ema100 = ema($prices, 100);

/* 3️⃣ Calculate StochRSI (14, 3, 3) */
$calculateStochRsi = function ($values, $rsiPeriod = 14, $kPeriod = 3, $dPeriod = 3) {
    $rsi = function ($v, $p) {
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

    // Calculate RSI values
    $rsiVals = [];
    for ($i = $rsiPeriod - 1; $i < count($values); $i++) {
        $window = array_slice($values, $i - $rsiPeriod + 1, $rsiPeriod);
        $rsiVals[] = $rsi($window, $rsiPeriod);
    }

    // Smooth the RSI values with a simple moving average (SMA)
    $stochRsi = [];
    for ($i = 0; $i < count($rsiVals); $i++) {
        if ($i >= $kPeriod - 1) { // Start after kPeriod-1
            $window = array_slice($rsiVals, max(0, $i - $kPeriod + 1), $kPeriod);
            $stochRsi[] = (array_sum($window) / count($window));
        }
    }

    return $stochRsi;
};

$stochRsi = $calculateStochRsi($prices);

/* 4️⃣ Calculate MACD (12/26/9) */
// -------------------------------------------------------------------
// MACD implementation – replaces the old placeholder
// -------------------------------------------------------------------
/**
 * Calculates the MACD line, signal line, and histogram.
 *
 * @param array $closePrices Array of closing prices.
 * @param int   $fastPeriod  Fast EMA period (default 12)
 * @param int   $slowPeriod  Slow EMA period (default 26)
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
    $length = min(count($fastEma), count($slowEma));
    for ($i = 0; $i < $length; $i++) {
        $macdLine[] = $fastEma[$i] - $slowEma[$i];
    }

    // 3. Signal line = EMA of MACD line
    $signalLine = ema($macdLine, $signalPeriod);

    // 4. Histogram = MACD line – Signal line
    $histogram = [];
    $length = min(count($macdLine), count($signalLine));
    for ($i = 0; $i < $length; $i++) {
        $histogram[] = $macdLine[$i] - $signalLine[$i];
    }

    return [
        'macd'      => $macdLine,
        'signal'    => $signalLine,
        'histogram' => $histogram,
    ];
}
$macdData = macd($prices);
$macd = $macdData['macd'];
$macdSignal = $macdData['signal'];
$macdHist = $macdData['histogram'];

/* 5️⃣ Prepare JSON for Chart.js */
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
        [
            'label' => 'StochRSI',
            'data' => array_merge(array_fill(0, count($prices) - count($stochRsi), null), $stochRsi),
            'borderColor' => 'green',
            'fill' => false,
        ],
        [
            'label' => 'MACD',
            'data' => array_merge(array_fill(0, count($prices) - count($macd), null), $macd),
            'borderColor' => 'purple',
            'fill' => false,
        ],
        [
            'label' => 'Signal Line',
            'data' => array_merge(array_fill(0, count($prices) - count($macdSignal), null), $macdSignal),
            'borderColor' => 'orange',
            'fill' => false,
        ],
        [
            'label' => 'Histogram',
            'data' => array_merge(array_fill(0, count($prices) - count($macdHist), null), $macdHist),
            'borderColor' => 'yellow',
            'fill' => false,
        ]
    ]
];
header('Content-Type: application/json');
echo json_encode($chartData);
?>
