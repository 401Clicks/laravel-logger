# 401 Clicks Laravel Logger

Simple log aggregation for Laravel applications. Send your logs to 401 Clicks with just a few environment variables.

## Installation

```bash
composer require 401Clicks/laravel-logger
```

## Quick Start

Add these to your `.env` file:

```env
LOG_CHANNEL=stack
LOG_STACK=single,clicks

CLICKS_API_TOKEN=your-api-token-here
```

That's it! Your logs will now be sent to 401 Clicks.

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `CLICKS_API_TOKEN` | Your API token from project settings | *required* |
| `CLICKS_LOG_URL` | API endpoint (for self-hosted) | `https://logs.401clicks.com/api/v1/logs` |
| `CLICKS_LOG_LEVEL` | Minimum log level to send | `debug` |
| `CLICKS_BATCH_SIZE` | Number of logs to batch | `10` |
| `CLICKS_FLUSH_INTERVAL` | Max seconds between flushes | `5` |

### Publishing Config

To customize the configuration:

```bash
php artisan vendor:publish --tag=clicks-logger-config
```

### Manual Channel Configuration

If you prefer to configure the channel manually in `config/logging.php`:

```php
'channels' => [
    // ... other channels

    'clicks' => [
        'driver' => 'clicks',
        'url' => env('CLICKS_LOG_URL', 'https://logs.401clicks.com/api/v1/logs'),
        'token' => env('CLICKS_API_TOKEN'),
        'level' => env('CLICKS_LOG_LEVEL', 'debug'),
    ],
],
```

## Usage

Once configured, just use Laravel's standard logging:

```php
use Illuminate\Support\Facades\Log;

Log::info('User logged in', ['user_id' => $user->id]);
Log::error('Payment failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
```

### Using with Stack Channel

The recommended setup uses Laravel's stack channel to log locally AND send to 401 Clicks:

```env
LOG_CHANNEL=stack
LOG_STACK=single,clicks
```

This way you keep local logs while also sending them to 401 Clicks.

### Logging to Clicks Only

To only send logs to 401 Clicks:

```env
LOG_CHANNEL=clicks
```

## How It Works

- Logs are batched (default: 10 logs or 5 seconds)
- Batches are sent via HTTP POST to the 401 Clicks API
- Failed sends are logged to stderr (won't break your app)
- Remaining logs are flushed on shutdown

## Getting Your API Token

1. Go to [401clicks.com](https://401clicks.com)
2. Navigate to your project settings
3. Create a new API token
4. Copy the token to your `.env` file

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- Guzzle HTTP client

## License

MIT License
