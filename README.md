# Cryptochart - Technical Analysis Dashboard

A PHP-based cryptocurrency charting application that fetches market data and calculates technical indicators for visualization.

## Features

- Real-time ETH/USDT price data from Binance API
- Technical indicators: EMA (25, 100), StochRSI, MACD
- Interactive charts using Chart.js
- Caching for improved performance
- Rate limiting and security features

## Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Optional: Redis for caching

## Installation

1. Clone the repository
2. Configure your web server to serve the project directory
3. Copy `config/config.example.php` to `config/config.php` and adjust settings
4. Access `index.html` in your browser

## API Endpoints

- `GET /api/chart-data.php` - Returns chart data with technical indicators

## Configuration

Edit `config/config.php` to customize:
- API endpoints and parameters
- Caching settings
- Rate limiting rules
- Technical indicator periods

## Security Features

- Input validation and sanitization
- Rate limiting to prevent API abuse
- CORS headers configuration
- Error handling without information leakage

## Performance

- API response caching (configurable TTL)
- Optimized calculation algorithms
- Response compression
- Memory-efficient data processing

## Testing

Run tests with PHPUnit:
```bash
vendor/bin/phpunit tests/
```

## License

MIT License - see LICENSE file for details