<?php

namespace Ultra\TranslationManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the Ultra Translation Manager (UTM).
 *
 * Provides a static interface to the TranslationManager service, allowing easy access
 * to translation functionalities such as registering and retrieving translations.
 * This facade acts as a convenient proxy to the underlying TranslationManager instance.
 *
 * @see \Ultra\TranslationManager\TranslationManager
 */
class UltraTrans extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * Returns the key used to bind the TranslationManager service in the Laravel
     * service container. This allows the facade to resolve the correct instance
     * when its static methods are called.
     *
     * @return string The binding key for the TranslationManager service.
     */
    protected static function getFacadeAccessor()
    {
        return 'ultra.translation';
    }
}
