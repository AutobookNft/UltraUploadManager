<?php

namespace Tests;

use Orchestra\Testbench\TestCase;

abstract class UltraTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Ultra\UltraLogManager\Providers\UltraLogManagerServiceProvider::class,
            \Ultra\UltraConfigManager\Providers\UConfigServiceProvider::class,
            \Ultra\ErrorManager\Providers\UltraErrorManagerServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('uconfig.use_spatie_permissions', false);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
