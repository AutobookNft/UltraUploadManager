<?php

namespace Tests\Traits;

use Ultra\UltraConfigManager\Providers\UConfigServiceProvider;

trait UseUltraConfigManager
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        // Configurazioni specifiche per UCM
        $app['config']->set('uconfig.use_spatie_permissions', false);
        $app['config']->set('cache.default', 'array');
    }

    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app) ?? [], [
            UConfigServiceProvider::class,
        ]);
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(base_path('database/migrations'));

        // Carica le migrazioni delle tabelle uconfig
        $migrationsPath = base_path('vendor/ultra/ultra-config-manager/database/migrations');
        if (file_exists($migrationsPath)) {
            $this->loadMigrationsFrom($migrationsPath);
        }
    }
}
