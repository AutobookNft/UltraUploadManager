<?php

if (!function_exists('getDefaultHostingService')) {
    /**
     * Get the default hosting service name from the configuration.
     *
     * @return string|null
     */
    function getDefaultHostingService()
    {
        return collect(config('app.hosting_services'))
            ->firstWhere('is_default', true)['name'] ?? null;
    }
}
