<?php

namespace Ultra\AuthSandbox\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class UltraAuthSandboxServiceProvider extends ServiceProvider
{
    public function boot(): void
    {

        $this->app->booted(function () {
            $router = $this->app->make(Router::class);

            $router->group([
                'middleware' => 'web',
            ], __DIR__.'/../../routes/authsandbox.php');
        });

        $this->loadViewsFrom(__DIR__.'/../../resources/views', 'authsandbox');

        // Publish views if needed
        $this->publishes([
            __DIR__.'/../../resources/views' => resource_path('views/vendor/authsandbox'),
        ], 'authsandbox-views');

        // Register seeders
        $this->publishes([
            __DIR__.'/../../database/seeders/UltraAuthSeeder.php' => database_path('seeders/UltraAuthSeeder.php'),
        ], 'authsandbox-seeder');
    }

    public function register(): void
    {
        //
    }
}
