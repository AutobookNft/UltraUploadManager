<?php

namespace Fabio\UltraConfigManager\Providers;

use Illuminate\Support\ServiceProvider;

class ValidationServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Non è necessario registrare alcun singleton in questo provider,
        // ma possiamo aggiungere logica specifica per la validazione qui, 
        // se vogliamo separare questa logica per tenerla ben isolata.
    }

    public function boot()
    {
        // Puoi aggiungere logica di bootstrap qui se necessario
    }
}
