<?php

namespace Clicks\Logger;

use Monolog\Logger;

class ClicksLogger
{
    public function __construct(protected array $config) {}

    public function __invoke(array $config): Logger
    {
        $config = array_merge($this->config, $config);

        $logger = new Logger('clicks');

        $logger->pushHandler(
            new ClicksLogHandler(
                url: $config['url'] ?? 'https://logs.401clicks.com/api/v1/logs',
                token: $config['token'] ?? '',
                level: $config['level'] ?? 'debug',
                bubble: $config['bubble'] ?? true,
            )
        );

        return $logger;
    }
}
