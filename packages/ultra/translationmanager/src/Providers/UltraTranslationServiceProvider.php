<?php
namespace Ultra\TranslationManager\Providers;

use Illuminate\Contracts\Translation\Translator as TranslatorContract;
use Illuminate\Support\ServiceProvider;
use Ultra\TranslationManager\TranslationManager;
use Illuminate\Translation\Translator as LaravelTranslator;

class UltraTranslationServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('ultra.translation', function ($app) {
            return new TranslationManager(); // Costruttore semplice
        });

        $this->app->extend('translator', function (LaravelTranslator $laravelTranslator, $app) {
            $utmInstance = $app->make('ultra.translation');
            $utmInstance->setLaravelTranslator($laravelTranslator); // Setter Injection
            return $utmInstance; // Restituisci UTM come implementazione di 'translator'
        });

        // Lega l'interfaccia al binding 'translator'
         $this->app->singleton(TranslatorContract::class, function ($app) {
           return $app->make('translator');
       });

        $this->mergeConfigFrom(__DIR__ . './../../config/translation-manager.php', 'translation-manager');
    }

    public function boot() {
         // ... publishes e registrazione 'core' come prima ...
          \Ultra\TranslationManager\Facades\UltraTrans::registerPackageTranslations(
            'core',
            resource_path('lang') // Carica traduzioni app/sovrascritture per 'core'
        );
    }
}
