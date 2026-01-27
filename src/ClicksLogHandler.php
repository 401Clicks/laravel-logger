<?php

namespace Clicks\Logger;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

class ClicksLogHandler extends AbstractProcessingHandler
{
    protected Client $client;

    protected array $buffer = [];

    protected int $batchSize = 10;

    protected int $flushInterval = 5; // seconds

    protected ?float $lastFlush = null;

    public function __construct(
        protected string $url,
        protected string $token,
        Level|string|int $level = Level::Debug,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);

        $this->client = new Client([
            'timeout' => 5,
            'connect_timeout' => 2,
        ]);

        $this->lastFlush = microtime(true);

        // Register shutdown function to flush remaining logs
        register_shutdown_function([$this, 'flush']);
    }

    protected function write(LogRecord $record): void
    {
        $this->buffer[] = $this->formatRecord($record);

        // Flush if buffer is full or interval has passed
        if (
            count($this->buffer) >= $this->batchSize ||
            (microtime(true) - $this->lastFlush) >= $this->flushInterval
        ) {
            $this->flush();
        }
    }

    protected function formatRecord(LogRecord $record): array
    {
        return [
            'timestamp' => $record->datetime->format('c'),
            'level' => $record->level->name,
            'message' => $record->message,
            'context' => $record->context,
            'extra' => array_merge($record->extra, [
                'hostname' => gethostname(),
                'app_env' => config('app.env', 'production'),
                'app_name' => config('app.name', 'Laravel'),
            ]),
        ];
    }

    public function flush(): void
    {
        if (empty($this->buffer)) {
            return;
        }

        $logs = $this->buffer;
        $this->buffer = [];
        $this->lastFlush = microtime(true);

        try {
            $this->client->post($this->url, [
                'headers' => [
                    'Authorization' => 'Bearer '.$this->token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => [
                    'logs' => $logs,
                ],
            ]);
        } catch (GuzzleException $e) {
            // Silently fail - we don't want logging failures to break the app
            // Optionally log to a fallback handler or stderr
            error_log('401Clicks/laravel-logger: Failed to send logs - '.$e->getMessage());
        }
    }

    public function close(): void
    {
        $this->flush();
        parent::close();
    }
}
