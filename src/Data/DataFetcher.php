<?php

namespace Cryptochart\Data;

use Cryptochart\Config\ConfigManager;

/**
 * Data Fetcher for market information
 */
class DataFetcher
{
    private ConfigManager $config;

    public function __construct()
    {
        $this->config = ConfigManager::getInstance();
    }

    /**
     * Get market data from external source
     */
    public function getData(): array
    {
        $apiConfig = $this->config->getApiConfig();
        
        $url = $apiConfig['binance_base_url'] . '/klines';
        $params = [
            'symbol' => $apiConfig['default_symbol'],
            'interval' => $apiConfig['default_interval'],
            'limit' => $apiConfig['default_limit']
        ];

        $fullUrl = $url . '?' . http_build_query($params);

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'CryptoChart'
            ]
        ]);

        $result = file_get_contents($fullUrl, false, $context);

        if ($result === false) {
            throw new \Exception('Failed to fetch data');
        }

        $decoded = json_decode($result, true);
        
        if ($decoded === null) {
            throw new \Exception('Invalid data format');
        }

        return $decoded;
    }

    /**
     * Process raw data into prices
     */
    public function processPrices(array $rawData): array
    {
        $prices = [];
        foreach ($rawData as $item) {
            $prices[] = (float) $item[4]; // Close price
        }
        return $prices;
    }

    /**
     * Process timestamps into labels
     */
    public function processLabels(array $rawData): array
    {
        $labels = [];
        foreach ($rawData as $item) {
            $labels[] = date('Y-m-d', $item[0] / 1000);
        }
        return $labels;
    }
}