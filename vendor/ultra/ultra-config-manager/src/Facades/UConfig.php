<?php

/**
 * 📜 Oracode Facade: UConfig
 *
 * @package         Ultra\UltraConfigManager\Facades
 * @version         1.2.0 // Versione incrementata per coerenza con Manager
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Facades;

use Illuminate\Support\Facades\Facade;
// Import tipi usati nei DocBlock @method
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as IlluminateCollection;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigAuditData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigDisplayData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigEditData;


/**
 * 🎯 Purpose: Provides a convenient, static-like interface (Facade) to the core
 *    `UltraConfigManager` service registered in the Laravel service container. Allows easy
 *    access to configuration management functionalities throughout a Laravel application
 *    using a familiar syntax (e.g., `UConfig::get('key')`).
 *
 * 🧱 Structure: Extends Laravel's base `Facade` class. Specifies the container binding
 *    accessor ('uconfig') via `getFacadeAccessor`. Uses `@method` annotations in the DocBlock
 *    to provide IDE autocompletion and type hinting for the underlying `UltraConfigManager`'s
 *    public methods.
 *
 * 🧩 Context: Used anywhere within a Laravel application where static access to the
 *    `UltraConfigManager` service is desired. Requires the `UConfigServiceProvider` to have
 *    registered the 'uconfig' binding.
 *
 * 🛠️ Usage: `UConfig::get('app.name');`, `UConfig::set('feature.enabled', true);`
 *
 * 💾 State: Stateless itself. All calls are proxied to the underlying singleton instance
 *    of `UltraConfigManager`.
 *
 * 🗝️ Key Features: Static proxy to all public methods of `UltraConfigManager`.
 *
 * 🚦 Signals: Methods invoked via the Facade will return the same values or throw the
 *    same exceptions as the corresponding methods on the `UltraConfigManager` instance.
 *
 * 🛡️ Privacy (GDPR): Acts as a pass-through. Privacy considerations are identical to those
 *    of the underlying `UltraConfigManager` methods being called.
 *    - `@privacy-proxy`: Delegates all operations and data handling to the bound `UltraConfigManager` service.
 *
 * 🤝 Dependencies:
 *    - Laravel Facade system (`Illuminate\Support\Facades\Facade`).
 *    - Requires the 'uconfig' service to be bound in the container (done by `UConfigServiceProvider`).
 *    - Relies on the public API defined by `UltraConfigManager`.
 *
 * 🧪 Testing:
 *    - When testing code that *uses* this Facade, Laravel's Facade mocking features can be used:
 *      `UConfig::shouldReceive('get')->with('key', 'default')->andReturn('mocked_value');`
 *    - The Facade class itself usually doesn't require direct unit testing, as its logic is minimal.
 *
 * 💡 Logic: Standard Laravel Facade implementation. The `@method` annotations are crucial
 *    for developer experience (IDE support). Ensure these annotations are kept in sync
 *    with the public API of `UltraConfigManager`.
 *
 * @method static bool has(string $key) Check if a configuration key exists in memory.
 * @method static mixed get(string $key, mixed $default = null, bool $silent = false) Retrieve a configuration value from memory.
 * @method static void set(string $key, mixed $value, ?string $category = null, ?int $userId = null, bool $version = true, bool $audit = true) Set/update a configuration value (persists, versions, audits).
 * @method static void delete(string $key, ?int $userId = null, bool $audit = true) Soft-delete a configuration value (audits).
 * @method static array<string, mixed> all() Get all configuration values from memory.
 * @method static void loadConfig() Initialize/hydrate the in-memory configuration state.
 * @method static void refreshConfigCache(?string $key = null) Refresh the external configuration cache.
 * @method static void reload(bool $invalidateCache = true) Reload configuration from primary sources, bypassing memory cache.
 * @method static void validateConstant(string $name) Validate if a constant name exists in GlobalConstants.
 * @method static LengthAwarePaginator|IlluminateCollection<int, ConfigDisplayData> getAllEntriesForDisplay(array $filters = [], ?int $perPage = 15, int $valueMaxLength = 50) Retrieves configuration entries formatted for display listings.
 * @method static ConfigEditData findEntryForEdit(int $id) Retrieves all data required for editing a specific configuration entry.
 * @method static ConfigAuditData findEntryForAudit(int $id) Retrieves data required for viewing the audit trail of a specific configuration entry.
 *
 * @package Ultra\UltraConfigManager\Facades
 * @see \Ultra\UltraConfigManager\UltraConfigManager Underlying service class.
 */
class UConfig extends Facade
{
    /**
     * 📦 Get the registered name of the component in the service container.
     * This is the key used to bind the `UltraConfigManager` instance.
     *
     * @return string Returns 'uconfig'.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'uconfig';
    }
}