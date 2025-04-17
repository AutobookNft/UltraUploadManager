<?php

return [
    'default_locale' => 'en',
    'available_locales' => ['en', 'it', 'fr', 'es', 'de', 'pt'],
    'fallback_locale' => 'en',
    'cache_enabled' => env('TRANSLATION_CACHE_ENABLED', false),
    'cache_prefix' => 'ultra_translations',
];
