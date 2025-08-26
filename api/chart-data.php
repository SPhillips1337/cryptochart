<?php

/**
 * Chart Data API Endpoint
 * 
 * Returns JSON data for cryptocurrency charts with technical indicators
 */

// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load autoloader
require_once __DIR__ . '/../autoload.php';

use Cryptochart\Services\ChartDataService;
use Cryptochart\Config\ConfigManager;

try {
    // Set content type
    header('Content-Type: application/json');
    
    // Get configuration
    $config = ConfigManager::getInstance();
    $securityConfig = $config->getSecurityConfig();
    
    // Handle CORS if enabled
    if ($securityConfig['cors_enabled']) {
        $allowedOrigins = $securityConfig['allowed_origins'];
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            header('Access-Control-Allow-Origin: ' . ($origin ?: '*'));
        }
        
        header('Access-Control-Allow-Methods: ' . implode(', ', $securityConfig['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $securityConfig['allowed_headers']));
        header('Access-Control-Max-Age: ' . $securityConfig['max_age']);
    }
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }
    
    // Create service and get chart data
    $chartService = new ChartDataService();
    $chartData = $chartService->getChartData();
    
    // Enable compression if available
    if (function_exists('gzencode') && strpos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== false) {
        header('Content-Encoding: gzip');
        echo gzencode(json_encode($chartData));
    } else {
        echo json_encode($chartData);
    }
    
} catch (Exception $e) {
    // Log error if logging is enabled
    $config = ConfigManager::getInstance();
    if ($config->get('logging.enabled', false)) {
        error_log('Chart API Error: ' . $e->getMessage());
    }
    
    // Return error response
    http_response_code(500);
    
    if ($config->isDebugMode()) {
        echo json_encode([
            'error' => 'Internal server error',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    } else {
        echo json_encode(['error' => 'Internal server error']);
    }
}