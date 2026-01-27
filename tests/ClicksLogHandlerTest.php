<?php

use Clicks\Logger\ClicksLogHandler;
use Monolog\Level;
use Monolog\LogRecord;

it('formats log records correctly', function () {
    $handler = new ClicksLogHandler(
        url: 'https://logs.401clicks.com/api/v1/logs',
        token: 'test-token',
        level: Level::Debug,
    );

    $record = new LogRecord(
        datetime: new DateTimeImmutable('2024-01-15T12:00:00+00:00'),
        channel: 'test',
        level: Level::Info,
        message: 'Test message',
        context: ['user_id' => 123],
        extra: [],
    );

    $reflection = new ReflectionClass($handler);
    $method = $reflection->getMethod('formatRecord');
    $method->setAccessible(true);

    $formatted = $method->invoke($handler, $record);

    expect($formatted)->toHaveKey('timestamp')
        ->toHaveKey('level')
        ->toHaveKey('message')
        ->toHaveKey('context');

    expect($formatted['level'])->toBe('Info');
    expect($formatted['message'])->toBe('Test message');
    expect($formatted['context'])->toBe(['user_id' => 123]);
});

it('buffers logs before sending', function () {
    $handler = new ClicksLogHandler(
        url: 'https://logs.401clicks.com/api/v1/logs',
        token: 'test-token',
        level: Level::Debug,
    );

    $reflection = new ReflectionClass($handler);
    $bufferProperty = $reflection->getProperty('buffer');
    $bufferProperty->setAccessible(true);

    // Initially empty
    expect($bufferProperty->getValue($handler))->toBeEmpty();
});
