<?php

namespace Ultra\UltraConfigManager\Tests;

use Orchestra\Testbench\TestCase;

abstract class UltraTestCase extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            \Spatie\Permission\PermissionServiceProvider::class,
            \Ultra\UltraLogManager\Providers\UltraLogManagerServiceProvider::class,
            \Ultra\UltraConfigManager\Providers\UConfigServiceProvider::class,
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

        $app['config']->set('permission.models.permission', \Spatie\Permission\Models\Permission::class);
        $app['config']->set('permission.models.role', \Spatie\Permission\Models\Role::class);

        // Tentativo: Imposta cache store per Spatie se necessario (a volte lo richiede)
        $app['config']->set('permission.cache.store', 'array'); // Usa la cache array che abbiamo definito
        $app['config']->set('permission.cache.key', 'spatie.permission.cache');
        $app['config']->set('permission.cache.expiration_time', \DateInterval::createFromDateString('24 hours'));


    }

    /**
     * Define database migrations required for tests.
     * This method ensures migrations run in the correct order.
     */
    protected function defineDatabaseMigrations()
    {
        // Questo fa sì che Testbench esegua le migrazioni predefinite (inclusa users)
        // se non vengono trovate migrazioni nella directory di default 'database/migrations'
        // del pacchetto in test (che nel nostro caso non abbiamo, usiamo loadMigrationsFrom)
        // O, più comunemente, carica le migrazioni definite in un TestbenchServiceProvider se presente.
        // Per sicurezza, carichiamo esplicitamente quelle del nostro pacchetto *dopo*.
        // $this->loadLaravelMigrations(['--database' => 'sqlite']); // Specifica il database di test

         // Carica prima le migrazioni di base definite localmente per i test
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // Poi carica le migrazioni del nostro pacchetto
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function setUp(): void
    {
        parent::setUp();
        // $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}
