<?php

namespace Cryptochart\Services;

use Cryptochart\Config\ConfigManager;
use Cryptochart\Data\DataFetcher;
use Cryptochart\Cache\CacheManager;
use Cryptochart\Indicators\EMA;
use Cryptochart\Indicators\StochRSI;
use Cryptochart\Indicators\MACD;

/**
 * Chart Data Service
 */
class ChartDataService
{
    private ConfigManager $config;
    private DataFetcher $dataFetcher;
    private CacheManager $cache;

    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
        $this->dataFetcher = new DataFetcher();
        $this->cache = new CacheManager();
    }

    /**
     * Get chart data with indicators
     */
    public function getChartData(): array
    {
        $cacheKey = 'chart_data_main';
        $cached = $this->cache->get($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }

        $rawData = $this->dataFetcher->getData();
        $prices = $this->dataFetcher->processPrices($rawData);
        $labels = $this->dataFetcher->processLabels($rawData);

        $indicators = $this->calculateIndicators($prices);
        $chartData = $this->formatChartData($labels, $prices, $indicators);

        $this->cache->set($cacheKey, $chartData);

        return $chartData;
    }

    /**
     * Calculate technical indicators
     */
    private function calculateIndicators(array $prices): array
    {
        $config = $this->config->getIndicatorsConfig();

        $emaFast = EMA::calculate($prices, $config['ema']['fast_period']);
        $emaSlow = EMA::calculate($prices, $config['ema']['slow_period']);
        
        $stochRSI = StochRSI::calculate(
            $prices,
            $config['stoch_rsi']['rsi_period'],
            $config['stoch_rsi']['k_period'],
            $config['stoch_rsi']['d_period']
        );

        $macdData = MACD::calculate(
            $prices,
            $config['macd']['fast_period'],
            $config['macd']['slow_period'],
            $config['macd']['signal_period']
        );

        return [
            'ema_fast' => $emaFast,
            'ema_slow' => $emaSlow,
            'stoch_rsi' => $stochRSI,
            'macd' => $macdData['macd'],
            'signal' => $macdData['signal'],
            'histogram' => $macdData['histogram']
        ];
    }

    /**
     * Format data for Chart.js
     */
    private function formatChartData(array $labels, array $prices, array $indicators): array
    {
        $colors = $this->config->get('chart.colors', []);

        $datasets = [
            [
                'label' => 'Close',
                'data' => $prices,
                'borderColor' => $colors['close'] ?? '#000000',
                'fill' => false
            ],
            [
                'label' => 'EMA 25',
                'data' => $this->padArray($indicators['ema_fast'], count($prices), 24),
                'borderColor' => $colors['ema_fast'] ?? '#0066CC',
                'fill' => false
            ],
            [
                'label' => 'EMA 100',
                'data' => $this->padArray($indicators['ema_slow'], count($prices), 99),
                'borderColor' => $colors['ema_slow'] ?? '#CC0000',
                'fill' => false
            ],
            [
                'label' => 'StochRSI',
                'data' => $this->padArray($indicators['stoch_rsi'], count($prices)),
                'borderColor' => $colors['stoch_rsi'] ?? '#00CC00',
                'fill' => false
            ],
            [
                'label' => 'MACD',
                'data' => $this->padArray($indicators['macd'], count($prices)),
                'borderColor' => $colors['macd'] ?? '#9900CC',
                'fill' => false
            ],
            [
                'label' => 'Signal Line',
                'data' => $this->padArray($indicators['signal'], count($prices)),
                'borderColor' => $colors['signal'] ?? '#FF6600',
                'fill' => false
            ],
            [
                'label' => 'Histogram',
                'data' => $this->padArray($indicators['histogram'], count($prices)),
                'borderColor' => $colors['histogram'] ?? '#FFCC00',
                'fill' => false
            ]
        ];

        return [
            'labels' => $labels,
            'datasets' => $datasets
        ];
    }

    /**
     * Pad array with nulls for alignment
     */
    private function padArray(array $data, int $totalLength, int $offset = 0): array
    {
        if ($offset > 0) {
            return array_merge(array_fill(0, $offset, null), $data);
        }

        $dataLength = count($data);
        if ($dataLength < $totalLength) {
            $padding = $totalLength - $dataLength;
            return array_merge(array_fill(0, $padding, null), $data);
        }

        return array_slice($data, 0, $totalLength);
    }
}