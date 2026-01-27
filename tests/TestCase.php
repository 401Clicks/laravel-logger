<?php

namespace Clicks\Logger\Tests;

use Clicks\Logger\ClicksLoggerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ClicksLoggerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.name', 'TestApp');
        $app['config']->set('app.env', 'testing');
    }
}
