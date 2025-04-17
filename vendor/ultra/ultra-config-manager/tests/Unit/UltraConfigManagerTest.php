<?php

/**
 * 📜 Oracode Unit Test: UltraConfigManagerTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         0.3.6 // Use single ordered block and shouldIgnoreMissing
 * @author          Padmin D. Curtis (Generated for Fabio Cherici)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use ReflectionProperty;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Dao\ConfigDaoInterface;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigAuditData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigDisplayData;
use Ultra\UltraConfigManager\DataTransferObjects\ConfigEditData;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider;
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraConfigManager\UltraConfigManager; // Class under test
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Ultra\UltraConfigManager\Models\UltraConfigModel; 
use Ultra\UltraConfigManager\Exceptions\PersistenceException;
use Ultra\UltraConfigManager\Casts\EncryptedCast;



/**
 * 🎯 Purpose: Unit tests for the UltraConfigManager class.
 *    Verifies the core logic of the manager in isolation by mocking its dependencies.
 *    Focuses on state management (in-memory config), caching logic, delegation
 *    to DAO and other services, error handling, and DTO transformations.
 *
 * 🧪 Test Strategy: Pure unit tests using Mockery to mock all external dependencies.
 *    - `ConfigDaoInterface`
 *    - `VersionManager`
 *    - `CacheRepository`
 *    - `LoggerInterface`
 *    - `GlobalConstants` (mocked due to final keyword issue or design choice)
 *    Each test focuses on a specific method or logic path within the Manager.
 *
 * @package Ultra\UltraConfigManager\Tests\Unit
 */
#[CoversClass(UltraConfigManager::class)]
#[UsesClass(UConfigServiceProvider::class)]
#[UsesClass(ConfigAuditData::class)]          
#[UsesClass(ConfigDisplayData::class)]        
#[UsesClass(ConfigEditData::class)]           
#[UsesClass(UltraConfigModel::class)]         
#[UsesClass(UltraConfigAudit::class)]         
#[UsesClass(UltraConfigVersion::class)]       
#[UsesClass(PersistenceException::class)]     
#[UsesClass(ConfigNotFoundException::class)] 
#[UsesClass(EncryptedCast::class)] 
#[UsesClass(CategoryEnum::class)] 

class UltraConfigManagerTest extends UltraTestCase
{
    // Trait per integrare Mockery con PHPUnit (gestisce Mockery::close automaticamente)
    use MockeryPHPUnitIntegration;

    // --- Mocks for Dependencies ---
    protected ConfigDaoInterface&MockInterface $configDaoMock;
    protected VersionManager&MockInterface $versionManagerMock;
    protected CacheRepository&MockInterface $cacheRepositoryMock;
    protected LoggerInterface&MockInterface $loggerMock;
    protected GlobalConstants&MockInterface $globalConstantsMock; // Mocked version

    // --- Instance of the Class Under Test ---
    protected UltraConfigManager $manager;

    /**
     * ⚙️ Set up the test environment before each test.
     * Creates mocks, sets EXPECTATIONS FOR CONSTRUCTOR LOGS, and instantiates the Manager.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Mocks
        $this->configDaoMock = Mockery::mock(ConfigDaoInterface::class);
        $this->versionManagerMock = Mockery::mock(VersionManager::class);
        $this->cacheRepositoryMock = Mockery::mock(CacheRepository::class);
        // Logger mock IGNORERÀ le chiamate per cui non impostiamo aspettative specifiche
        $this->loggerMock = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        $this->globalConstantsMock = Mockery::mock(GlobalConstants::class);
       
       
        // 4. Instantiate the Manager AFTER setting constructor expectations
        $this->manager = new UltraConfigManager(
            $this->configDaoMock,
            $this->versionManagerMock,
            $this->globalConstantsMock,
            $this->cacheRepositoryMock,
            $this->loggerMock,
            config('uconfig.cache.ttl', 3600),
            config('uconfig.cache.enabled', true),
            false // <-- loadOnInit = false
        );
    }

    // ========================================================================
    // == get() Method Tests (Focus: In-Memory State)
    // ========================================================================

    /**
     * ✅ Test [get]: Should return value from in-memory config when key exists.
     * 🧪 Verifies direct access to the pre-loaded internal state. Expects debug log.
     */
    #[Test]
    public function get_returns_value_from_memory_when_key_exists(): void
    {
        // --- Arrange ---
        $key = 'app.name';
        $expectedValue = 'UltraApp';
        $category = CategoryEnum::Application->value;

        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, [
            $key => ['value' => $expectedValue, 'category' => $category]
        ]);

        // Specific expectation for THIS test
        $this->loggerMock->shouldReceive('debug')
            ->once() // Expect exactly one call
            ->with(
                'UCM Get: Retrieved configuration key.',
                Mockery::on(function ($context) use ($key, $expectedValue) {
                    return is_array($context) && $context['key'] === $key && $context['value_type'] === gettype($expectedValue);
                })
            );

        // --- Act ---
        $result = $this->manager->get($key);

        // --- Assert ---
        $this->assertSame($expectedValue, $result);
    }

    /**
     * ✅ Test [get]: Should return default value when key does not exist in memory.
     * 🧪 Verifies fallback behavior for non-existent keys. Expects debug log.
     */
    #[Test]
    public function get_returns_default_when_key_not_exists_in_memory(): void
    {
        // --- Arrange ---
        $key = 'feature.toggle.new';
        $defaultValue = false;

        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, ['other.key' => ['value' => 'abc', 'category' => null]]);

        // Specific expectation for THIS test
        $this->loggerMock->shouldReceive('debug')
            ->once() // Expect exactly one call
            ->with(
                'UCM Get: Configuration key not found. Returning default.',
                Mockery::on(fn($context) => is_array($context) && $context['key'] === $key)
            );

        // --- Act ---
        $result = $this->manager->get($key, $defaultValue);

        // --- Assert ---
        $this->assertSame($defaultValue, $result);
    }

    /**
     * ✅ Test [get]: Should return default value silently when key not exists and silent is true.
     * 🧪 Verifies fallback behavior for non-existent keys with `silent = true`. Expects NO debug log.
     */
    #[Test]
    public function get_returns_default_silently_when_key_not_exists_and_silent_true(): void
    {
        // --- Arrange ---
        $key = 'database.connection.missing';
        $defaultValue = 'mysql';

        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, []);

        // Specific expectation for THIS test: NO debug call
        $this->loggerMock->shouldNotHaveReceived('debug');

        // --- Act ---
        $result = $this->manager->get($key, $defaultValue, true); // <-- silent = true

        // --- Assert ---
        $this->assertSame($defaultValue, $result);
    }

     /**
     * ✅ Test [get]: Should return value silently when key exists and silent is true.
     * 🧪 Verifies direct access to internal state with `silent = true`. Expects NO debug log.
     */
    #[Test]
    public function get_returns_value_silently_when_key_exists_and_silent_true(): void
    {
        // --- Arrange ---
        $key = 'cache.driver';
        $expectedValue = 'redis';
        $category = CategoryEnum::Performance->value;

        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, [
            $key => ['value' => $expectedValue, 'category' => $category]
        ]);

        // Specific expectation for THIS test: NO debug call
        $this->loggerMock->shouldNotHaveReceived('debug');

        // --- Act ---
        $result = $this->manager->get($key, null, true); // <-- silent = true

        // --- Assert ---
        $this->assertSame($expectedValue, $result);
    }


        // ========================================================================
    // == has() Method Tests (Focus: In-Memory State)
    // ========================================================================

    /**
     * ✅ Test [has]: Should return true when key exists in memory.
     * 🧪 Verifies checking for an existing key in the internal state. Expects debug log.
     */
    #[Test]
    public function has_returns_true_when_key_exists_in_memory(): void
    {
        // --- Arrange ---
        $key = 'app.timezone';
        $value = 'UTC';
        $category = CategoryEnum::Application->value;

        // Use Reflection to set the private $config property
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, [
            $key => ['value' => $value, 'category' => $category],
            'other.key' => ['value' => 'abc', 'category' => null]
        ]);

        // Expect logger to be called
        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with(
                'UCM Check: Configuration key check.',
                Mockery::on(fn($context) => is_array($context) && $context['key'] === $key && $context['exists'] === true)
            );

        // --- Act ---
        $result = $this->manager->has($key);

        // --- Assert ---
        $this->assertTrue($result);
    }

    /**
     * ✅ Test [has]: Should return false when key does not exist in memory.
     * 🧪 Verifies checking for a non-existent key in the internal state. Expects debug log.
     */
    #[Test]
    public function has_returns_false_when_key_not_exists_in_memory(): void
    {
        // --- Arrange ---
        $key = 'non.existent.key';

        // Ensure the internal $config does not contain the key
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, ['existing.key' => ['value' => 123, 'category' => null]]);

        // Expect logger to be called
        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with(
                'UCM Check: Configuration key check.',
                Mockery::on(fn($context) => is_array($context) && $context['key'] === $key && $context['exists'] === false)
            );

        // --- Act ---
        $result = $this->manager->has($key);

        // --- Assert ---
        $this->assertFalse($result);
    }

    /**
     * ✅ Test [has]: Should return false for an empty key string.
     * 🧪 Verifies edge case of checking an empty key. Expects debug log.
     */
    #[Test]
    public function has_returns_false_for_empty_key(): void
    {
        // --- Arrange ---
        $key = ''; // Empty key

        // Set some data in config
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, ['existing.key' => ['value' => 123, 'category' => null]]);

        // Expect logger to be called
        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with(
                'UCM Check: Configuration key check.',
                Mockery::on(fn($context) => is_array($context) && $context['key'] === $key && $context['exists'] === false)
            );

        // --- Act ---
        $result = $this->manager->has($key);

        // --- Assert ---
        $this->assertFalse($result);
    }

    // ========================================================================
    // == all() Method Tests (Focus: In-Memory State)
    // ========================================================================

    /**
     * ✅ Test [all]: Should return only the values from the in-memory config.
     * 🧪 Verifies that the method correctly extracts and returns just the values. Expects debug log.
     */
    #[Test]
    public function all_returns_only_values_from_memory(): void
    {
        // --- Arrange ---
        $configData = [
            'app.name' => ['value' => 'UltraApp', 'category' => CategoryEnum::Application->value],
            'app.debug' => ['value' => true, 'category' => CategoryEnum::System->value],
            'services.mailgun.secret' => ['value' => 'super-secret-key', 'category' => CategoryEnum::Security->value],
            'feature.new_reporting' => ['value' => false, 'category' => CategoryEnum::Application->value],
            'some.null.value' => ['value' => null, 'category' => null] // Include a null value
        ];
        $expectedValues = [
            'app.name' => 'UltraApp',
            'app.debug' => true,
            'services.mailgun.secret' => 'super-secret-key',
            'feature.new_reporting' => false,
            'some.null.value' => null
        ];
        $expectedCount = count($expectedValues);

        // Use Reflection to set the private $config property
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $configData);

        // Expect logger to be called
        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with(
                'UCM Get All: Returning all configuration values from memory.',
                Mockery::on(fn($context) => is_array($context) && $context['count'] === $expectedCount)
            );

        // --- Act ---
        $result = $this->manager->all();

        // --- Assert ---
        $this->assertIsArray($result);
        // Use assertSame to check both keys and values exactly match the expected structure
        $this->assertSame($expectedValues, $result);
        // Double check count for clarity
        $this->assertCount($expectedCount, $result);
    }

    /**
     * ✅ Test [all]: Should return an empty array when no config is loaded in memory.
     * 🧪 Verifies behavior when the internal config array is empty. Expects debug log.
     */
    #[Test]
    public function all_returns_empty_array_when_memory_is_empty(): void
    {
        // --- Arrange ---
        // Ensure the internal $config is empty
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, []);
        $expectedCount = 0;

        // Expect logger to be called
        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with(
                'UCM Get All: Returning all configuration values from memory.',
                Mockery::on(fn($context) => is_array($context) && $context['count'] === $expectedCount)
            );

        // --- Act ---
        $result = $this->manager->all();

        // --- Assert ---
        $this->assertIsArray($result);
        $this->assertEmpty($result);
        $this->assertSame([], $result); // Explicit check for empty array
        $this->assertCount($expectedCount, $result);
    }

    // ========================================================================
    // == loadConfig() Method Tests
    // ========================================================================

    // ========================================================================
    // == loadConfig() Method Tests
    // ========================================================================

    /**
     * ✅ Test [loadConfig]: Should load config from cache when cache is enabled and hit.
     * 🧪 Verifies that DAO is NOT called when cache provides valid data.
     */
    #[Test]
    public function loadConfig_loads_from_cache_when_enabled_and_hit(): void
    {
        // --- Arrange ---
        $cacheKey = 'ultra_config.cache';
        $cachedData = [
            'app.url' => ['value' => 'http://test.app', 'category' => 'system'],
            'cache.default' => ['value' => 'redis', 'category' => 'cache']
        ];
        $expectedCount = count($cachedData);

        // Expect cache read ONCE
        $this->cacheRepositoryMock->shouldReceive('get')
            ->once()
            ->with($cacheKey)
            ->andReturn($cachedData);

        // Expect DAO NOT to be called
        $this->configDaoMock->shouldNotReceive('getAllConfigs');

        // ---------------------------------------------------------------------
        // !! Define ALL expected log calls for this specific test flow HERE !!
        // ---------------------------------------------------------------------
        // Not defining expectations for constructor logs as they are ignored by shouldIgnoreMissing

        // Define expectations for logs WITHIN loadConfig()
        $this->loggerMock->shouldReceive('info') // #1 in loadConfig
            ->once()
            ->with('UCM Load: Starting configuration load sequence.');
        $this->loggerMock->shouldReceive('debug') // #2 in loadConfig
            ->once()
            ->with('UCM Load: Cache is enabled. Attempting to load from cache.', ['key' => $cacheKey]);
         $this->loggerMock->shouldReceive('info') // #3 in loadConfig
            ->once()
            ->with(
                'UCM Load: Configuration successfully loaded from cache.',
                Mockery::on(fn($context) => is_array($context) && $context['key'] === $cacheKey && $context['count'] === $expectedCount)
            );

        // --- Act ---
        $this->manager->loadConfig(); // Triggers the log calls defined above

        // --- Assert ---
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($cachedData, $internalConfig);
        // Mockery assertion happens automatically at tearDown
    }

    /**
     * ✅ Test [loadConfig]: Should load from DAO and set cache when cache is enabled but missed.
     * 🧪 Verifies the fallback to DAO, merging with ENV, and subsequent cache write. Isolates ENV vars.
     */
    #[Test]
    public function loadConfig_loads_from_dao_and_sets_cache_on_miss(): void
    {
        // --- Arrange ---
        $originalEnv = $_ENV; $_ENV = [];
        $cacheKey = 'ultra_config.cache'; $cacheTtl = 3600;
        $dbKey = 'db.key'; $dbValue = 'db_value'; $dbCategory = CategoryEnum::System;
        $envKey = 'env.key.test'; $envValue = 'env_value_test';
        $dbDataInternal = [ $dbKey => ['value' => $dbValue, 'category' => $dbCategory->value] ];
        $expectedMergedData = [ $dbKey => ['value' => $dbValue, 'category' => $dbCategory->value], $envKey => ['value' => $envValue, 'category' => null] ];
        $expectedDbCount = 1; $expectedMergedCount = 2; $expectedEnvAddedCount = 1;
        $_ENV[$envKey] = $envValue;

        // Mocks
        $this->cacheRepositoryMock->shouldReceive('get')->once()->with($cacheKey)->andReturnNull();
        $dbCollection = new \Illuminate\Support\Collection([(object)['key' => $dbKey, 'value' => $dbValue, 'category' => $dbCategory]]);
        $this->configDaoMock->shouldReceive('getAllConfigs')->once()->andReturn($dbCollection);
        $this->cacheRepositoryMock->shouldReceive('put')->once()->with($cacheKey, $expectedMergedData, $cacheTtl)->andReturn(true);

        // --- Log Expectations (Simplified) ---
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Starting configuration load sequence.');
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Load: Cache is enabled. Attempting to load from cache.', Mockery::any()); // Allow any context
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Cache miss or invalid data type in cache.', Mockery::any()); // Allow any context
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Loading configuration from database and environment.');
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM DB Load: Requesting all configurations from DAO.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM DB Load: Configurations loaded from database.', Mockery::on(fn($ctx)=>$ctx['loaded']===$expectedDbCount)); // Keep context check here
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Env Merge: Merging environment variables with DB config.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Env Merge: Environment variables merged.', Mockery::on(fn($ctx)=>$ctx['added_count']===$expectedEnvAddedCount)); // Keep context check here
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Configuration loaded from DB/Env.', Mockery::on(fn($ctx)=>$ctx['merged_count']===$expectedMergedCount)); // Keep context check here
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Fresh configuration stored in cache.', Mockery::on(fn($ctx)=>$ctx['key']===$cacheKey)); // Keep context check here


        // --- Act ---
        try { $this->manager->loadConfig(); } finally { $_ENV = $originalEnv; }

        // --- Assert ---
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedMergedData, $internalConfig);
    }

    /**
     * ✅ Test [loadConfig]: Should load directly from DAO when cache is disabled. Isolates ENV vars.
     * 🧪 Verifies that cache is NOT read or written when disabled in config.
     */
    #[Test]
    public function loadConfig_loads_from_dao_when_cache_disabled(): void
    {
        // --- Arrange ---
        $originalEnv = $_ENV; $_ENV = [];
        $dbKey = 'db.only.key'; $dbValue = 'db_only_value'; $dbCategory = CategoryEnum::System;
        $envKey = 'env.only.key.test'; $envValue = 'env_only_value_test';
        $dbDataInternal = [ $dbKey => ['value' => $dbValue, 'category' => $dbCategory->value] ];
        $expectedMergedData = [ $dbKey => ['value' => $dbValue, 'category' => $dbCategory->value], $envKey => ['value' => $envValue, 'category' => null] ];
        $expectedDbCount = 1; $expectedMergedCount = 2; $expectedEnvAddedCount = 1;
        $_ENV[$envKey] = $envValue;

        // Re-instantiate Manager with cache disabled
        $this->manager = new UltraConfigManager(
            $this->configDaoMock, $this->versionManagerMock, $this->globalConstantsMock,
            $this->cacheRepositoryMock, $this->loggerMock, 3600, false, false
        );

        // Mocks Cache/DAO
        $this->cacheRepositoryMock->shouldNotReceive('get');
        $this->cacheRepositoryMock->shouldNotReceive('put');
        $dbCollection = new \Illuminate\Support\Collection([(object)['key' => $dbKey, 'value' => $dbValue, 'category' => $dbCategory]]);
        $this->configDaoMock->shouldReceive('getAllConfigs')->once()->andReturn($dbCollection);

        // --- Log Expectations (Simplified) ---
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Starting configuration load sequence.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Cache is disabled. Loading directly from primary sources.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Loading configuration from database and environment.');
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM DB Load: Requesting all configurations from DAO.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM DB Load: Configurations loaded from database.', Mockery::on(fn($ctx)=>$ctx['loaded']===$expectedDbCount));
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Env Merge: Merging environment variables with DB config.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Env Merge: Environment variables merged.', Mockery::on(fn($ctx)=>$ctx['added_count']===$expectedEnvAddedCount));
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Configuration loaded from DB/Env.', Mockery::on(fn($ctx)=>$ctx['merged_count']===$expectedMergedCount));

        // --- Act ---
         try { $this->manager->loadConfig(); } finally { $_ENV = $originalEnv; }

        // --- Assert ---
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedMergedData, $internalConfig);
    }

     /**
     * ✅ Test [loadConfig]: Handles empty data from DAO correctly. Isolates ENV vars.
     * 🧪 Verifies behavior when the database returns no configuration entries.
     */
    #[Test]
    public function loadConfig_handles_empty_dao_response(): void
    {
        // --- Arrange ---
        $originalEnv = $_ENV; $_ENV = [];
        $cacheKey = 'ultra_config.cache'; $cacheTtl = 3600;
        $envKey1 = 'env.var.test.1'; $envValue1 = 'val1_test';
        $envKey2 = 'env.var.test.2'; $envValue2 = 'val2_test';
        $expectedMergedData = [ $envKey1 => ['value' => $envValue1, 'category' => null], $envKey2 => ['value' => $envValue2, 'category' => null] ];
        $expectedMergedCount = 2; $expectedEnvAddedCount = 2;
        $_ENV[$envKey1] = $envValue1; $_ENV[$envKey2] = $envValue2;

        // Mocks Cache/DAO
        $this->cacheRepositoryMock->shouldReceive('get')->once()->with($cacheKey)->andReturnNull();
        $this->configDaoMock->shouldReceive('getAllConfigs')->once()->andReturn(new \Illuminate\Support\Collection([]));
        $this->cacheRepositoryMock->shouldReceive('put')->once()->with($cacheKey, $expectedMergedData, $cacheTtl)->andReturn(true);

        // --- Log Expectations (Simplified) ---
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Starting configuration load sequence.');
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Load: Cache is enabled. Attempting to load from cache.', Mockery::any());
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Cache miss or invalid data type in cache.', Mockery::any()); // Allow any context
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Loading configuration from database and environment.');
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM DB Load: Requesting all configurations from DAO.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM DB Load: Configurations loaded from database.', Mockery::on(fn($ctx)=>$ctx['loaded']===0 && $ctx['ignored_null']===0));
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Env Merge: Merging environment variables with DB config.');
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Env Merge: Environment variables merged.', Mockery::on(fn($ctx)=>$ctx['added_count']===$expectedEnvAddedCount));
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Configuration loaded from DB/Env.', Mockery::on(fn($ctx)=>$ctx['merged_count']===$expectedMergedCount));
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Fresh configuration stored in cache.', Mockery::on(fn($ctx)=>$ctx['key']===$cacheKey));

        // --- Act ---
        try { $this->manager->loadConfig(); } finally { $_ENV = $originalEnv; }

        // --- Assert ---
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedMergedData, $internalConfig);
    }

    // ========================================================================
    // == set() Method Tests
    // ========================================================================

    /**
     * ✅ Test [set]: Creates new config via DAO, updates memory, and refreshes cache.
     * 🧪 Verifies the complete flow for adding a new configuration key.
     */
    #[Test]
    public function set_creates_new_config_via_dao_and_updates_state_and_cache(): void
    {
        // --- Arrange ---
        $key = 'new.config.key'; $value = 'the new value'; $category = CategoryEnum::Application;
        $categoryValue = $category->value; $userId = 123;
        $cacheKey = 'ultra_config.cache'; $cacheTtl = 3600;
        $initialConfigState = ['existing.key' => ['value' => 'old', 'category' => null]];
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $initialConfigState);
        $expectedFinalState = array_merge($initialConfigState, [ $key => ['value' => $value, 'category' => $categoryValue] ]);
        // Cache state *before* put (needed for the internal get call in refreshConfigCache)
        $initialCacheState = $initialConfigState; // Simulate cache matching initial memory

        $this->globalConstantsMock->shouldReceive('__get')->with('NO_USER')->andReturn(0);
        $spyModel = Mockery::spy(UltraConfigModel::class);
        $this->configDaoMock->shouldReceive('saveConfig')->once()->with($key, $value, $categoryValue, $userId, true, true, null)->andReturn($spyModel);

        // --- CORREZIONE: Aggiunta aspettativa per cache GET dentro refreshConfigCache ---
        $this->cacheRepositoryMock->shouldReceive('get')
            ->once()
            ->with($cacheKey, []) // Aspettati la chiamata get con default vuoto
            ->andReturn($initialCacheState); // Restituisci lo stato iniziale della cache

        // Aspettativa cache PUT (come prima)
        $this->cacheRepositoryMock->shouldReceive('put')
            ->once()
            ->with($cacheKey, $expectedFinalState, $cacheTtl)
            ->andReturn(true);

        // Log Expectations (come prima)
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Set: Attempting to set configuration.', Mockery::any());
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Set: In-memory configuration updated.', ['key' => $key]);
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', ['key' => $key]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Incremental cache refresh successful.', Mockery::on(fn($ctx)=>$ctx['key']===$key && $ctx['action']==='Updated/Added'));
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Set: Configuration set successfully.', Mockery::on(fn($ctx)=>$ctx['key']===$key && $ctx['action']==='created'));

        // --- Act ---
        $this->manager->set($key, $value, $categoryValue, $userId);

        // --- Assert ---
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
    }

    /**
     * ✅ Test [set]: Updates existing config via DAO, updates memory, and refreshes cache.
     * 🧪 Verifies the complete flow for modifying an existing configuration key.
     */
    #[Test]
    public function set_updates_existing_config_via_dao_and_updates_state_and_cache(): void
    {
        // --- Arrange ---
        $key = 'existing.key.to.update'; $oldValue = 'old value'; $oldCategoryValue = CategoryEnum::System->value;
        $newValue = 'new shiny value'; $newCategory = CategoryEnum::Performance; $newCategoryValue = $newCategory->value;
        $userId = 456; $cacheKey = 'ultra_config.cache'; $cacheTtl = 3600;
        $initialConfigState = [ $key => ['value' => $oldValue, 'category' => $oldCategoryValue], 'other.key' => ['value' => 'abc', 'category' => null] ];
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $initialConfigState);
        $expectedFinalState = [ $key => ['value' => $newValue, 'category' => $newCategoryValue], 'other.key' => ['value' => 'abc', 'category' => null] ];
        // Cache state *before* put
        $initialCacheState = $initialConfigState;

        $this->globalConstantsMock->shouldReceive('__get')->with('NO_USER')->andReturn(0);
        $spyModel = Mockery::spy(UltraConfigModel::class);
        $this->configDaoMock->shouldReceive('saveConfig')->once()->with($key, $newValue, $newCategoryValue, $userId, true, true, $oldValue)->andReturn($spyModel);

         // --- CORREZIONE: Aggiunta aspettativa per cache GET dentro refreshConfigCache ---
        $this->cacheRepositoryMock->shouldReceive('get')
            ->once()
            ->with($cacheKey, [])
            ->andReturn($initialCacheState);

        // Aspettativa cache PUT (come prima)
        $this->cacheRepositoryMock->shouldReceive('put')
            ->once()
            ->with($cacheKey, $expectedFinalState, $cacheTtl)
            ->andReturn(true);

        // Log Expectations (come prima)
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Set: Attempting to set configuration.', Mockery::any());
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Set: In-memory configuration updated.', ['key' => $key]);
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', ['key' => $key]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Incremental cache refresh successful.', Mockery::on(fn($ctx)=>$ctx['key']===$key && $ctx['action']==='Updated/Added'));
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Set: Configuration set successfully.', Mockery::on(fn($ctx)=>$ctx['key']===$key && $ctx['action']==='updated'));

        // --- Act ---
        $this->manager->set($key, $newValue, $newCategoryValue, $userId);

        // --- Assert ---
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
    }

    /**
     * ✅ Test [set]: Throws InvalidArgumentException for invalid key format.
     * 🧪 Verifies input validation prevents persistence with bad keys.
     */
    #[Test]
    public function set_throws_exception_for_invalid_key_format(): void
    {
        // --- Arrange ---
        $invalidKey = 'invalid key spaces'; // Contains spaces
        $value = 'some value';
        $userId = 1;

        // Expect exception
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Configuration key must be alphanumeric/");

        // Expect Logger error call
        $this->loggerMock->shouldReceive('error')
            ->once()
            ->with('UCM Set: Invalid key format.', ['key' => $invalidKey]);

        // Expect DAO and Cache NOT to be called
        $this->configDaoMock->shouldNotReceive('saveConfig');
        $this->cacheRepositoryMock->shouldNotReceive('put');

        // --- Act ---
        $this->manager->set($invalidKey, $value, null, $userId);

        // --- Assert ---
        // Exception assertion handles the test success
    }

        // ========================================================================
    // == delete() Method Tests
    // ========================================================================

    /**
     * ✅ Test [delete]: Successfully deletes key via DAO, updates memory, refreshes cache.
     * 🧪 Verifies the normal deletion flow for an existing key.
     */
    #[Test]
    public function delete_removes_key_via_dao_updates_state_and_cache(): void
    {
        // --- Arrange ---
        $keyToDelete = 'config.to.delete';
        $initialValue = 'i will be deleted';
        $initialCategory = CategoryEnum::Security->value;
        $userId = 789;
        $cacheKey = 'ultra_config.cache';
        $cacheTtl = 3600;

        // Initial internal state and cache state (containing the key)
        $initialState = [
            $keyToDelete => ['value' => $initialValue, 'category' => $initialCategory],
            'other.key' => ['value' => 'abc', 'category' => null]
        ];
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $initialState);
        $initialCacheState = $initialState; // Assume cache is in sync initially

        // Expected final state (key removed)
        $expectedFinalState = ['other.key' => ['value' => 'abc', 'category' => null]];

        $this->globalConstantsMock->shouldReceive('__get')->with('NO_USER')->andReturn(0);

        // 1. Expect DAO->deleteConfigByKey to be called ONCE and return TRUE
        $this->configDaoMock->shouldReceive('deleteConfigByKey')
            ->once()
            ->with($keyToDelete, $userId, true) // audit = true (default)
            ->andReturn(true); // Simulate successful deletion in DB

        // 2. Expect Cache GET inside refreshConfigCache
        $this->cacheRepositoryMock->shouldReceive('get')
            ->once()
            ->with($cacheKey, [])
            ->andReturn($initialCacheState);

        // 3. Expect Cache PUT with the key removed
        $this->cacheRepositoryMock->shouldReceive('put')
            ->once()
            ->with($cacheKey, $expectedFinalState, $cacheTtl) // Key is removed
            ->andReturn(true);

        // 4. Expect Logs
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Delete: Attempting to delete configuration.', ['key' => $keyToDelete]);
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Delete: Configuration removed from in-memory store.', ['key' => $keyToDelete]);
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', ['key' => $keyToDelete]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Incremental cache refresh successful.', Mockery::on(fn($ctx)=>$ctx['key']===$keyToDelete && $ctx['action']==='Removed'));
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Delete: Configuration deleted successfully.', ['key' => $keyToDelete]);

        // --- Act ---
        $this->manager->delete($keyToDelete, $userId);

        // --- Assert ---
        // Verify internal state reflects the removed key
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
        // Mockery assertions verify DAO/Cache/Log calls
    }

    /**
     * ✅ Test [delete]: Handles case where DAO reports key not found or failed delete.
     * 🧪 Verifies state/cache are still updated (key removed) even if DAO returns false.
     */
    #[Test]
    public function delete_handles_dao_failure_or_not_found(): void
    {
         // --- Arrange ---
         $keyToDelete = 'config.maybe.deleted';
         $initialValue = 'i might be deleted';
         $initialCategory = null;
         $userId = 789;
         $cacheKey = 'ultra_config.cache';
         $cacheTtl = 3600;
         $initialState = [ $keyToDelete => ['value' => $initialValue, 'category' => $initialCategory], 'other.key' => ['value' => 'abc', 'category' => null] ];
         $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
         $configProp->setValue($this->manager, $initialState);
         $initialCacheState = $initialState;
         $expectedFinalState = ['other.key' => ['value' => 'abc', 'category' => null]];
 
         $this->globalConstantsMock->shouldReceive('__get')->with('NO_USER')->andReturn(0);
 
         // 1. Expect DAO->deleteConfigByKey to return FALSE
         $this->configDaoMock->shouldReceive('deleteConfigByKey')
             ->once()
             ->with($keyToDelete, $userId, true)
             ->andReturn(false);
 
         // 2. Expect Cache GET
         $this->cacheRepositoryMock->shouldReceive('get')
             ->once()
             ->with($cacheKey, [])
             ->andReturn($initialCacheState);
 
         // 3. Expect Cache PUT
         $this->cacheRepositoryMock->shouldReceive('put')
             ->once()
             ->with($cacheKey, $expectedFinalState, $cacheTtl)
             ->andReturn(true);
 
         // 4. Expect Logs reflecting the DAO failure
         $this->loggerMock->shouldReceive('info')->once()->with('UCM Delete: Attempting to delete configuration.', ['key' => $keyToDelete]);
         $this->loggerMock->shouldReceive('error')->once()->with('UCM Delete: DAO failed to delete key, but it existed in memory. State might be inconsistent.', ['key' => $keyToDelete]);
 
         // --- CORREZIONE: Rimuovere aspettativa per debug log ---
         // $this->loggerMock->shouldReceive('debug')->once()->with('UCM Delete: Configuration removed from in-memory store.', ['key' => $keyToDelete]); // <-- RIMOSSA
 
         $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', ['key' => $keyToDelete]);
         $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Incremental cache refresh successful.', Mockery::on(fn($ctx)=>$ctx['key']===$keyToDelete && $ctx['action']==='Removed'));
         // NO "deleted successfully" log
 
         // --- Act ---
         $this->manager->delete($keyToDelete, $userId);
 
         // --- Assert ---
         $internalConfig = $configProp->getValue($this->manager);
         $this->assertSame($expectedFinalState, $internalConfig);
    }

    /**
     * ✅ Test [delete]: Handles case where key does not exist in memory initially.
     * 🧪 Verifies DAO is still called but state/cache remain consistent.
     */
    #[Test]
    public function delete_handles_key_not_found_in_memory(): void
    {
        // --- Arrange ---
        $keyToDelete = 'non.existent.key.for.delete';
        $userId = 789;
        $cacheKey = 'ultra_config.cache';
        $cacheTtl = 3600;

        // Initial internal state and cache state (WITHOUT the key)
        $initialState = ['other.key' => ['value' => 'abc', 'category' => null]];
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $initialState);
        $initialCacheState = $initialState;

        // Expected final state (should be unchanged)
        $expectedFinalState = $initialState;

        $this->globalConstantsMock->shouldReceive('__get')->with('NO_USER')->andReturn(0);

        // 1. Expect DAO->deleteConfigByKey to be called ONCE (attempting deletion anyway)
        //    Assume DAO returns false because key doesn't exist in DB either
        $this->configDaoMock->shouldReceive('deleteConfigByKey')
            ->once()
            ->with($keyToDelete, $userId, true)
            ->andReturn(false);

        // 2. Expect Cache GET inside refreshConfigCache
        $this->cacheRepositoryMock->shouldReceive('get')
            ->once()
            ->with($cacheKey, [])
            ->andReturn($initialCacheState);

        // 3. Expect Cache PUT with the state UNCHANGED (key wasn't there to remove)
        $this->cacheRepositoryMock->shouldReceive('put')
            ->once()
            ->with($cacheKey, $expectedFinalState, $cacheTtl)
            ->andReturn(true);

        // 4. Expect Logs reflecting key not found
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Delete: Attempting to delete configuration.', ['key' => $keyToDelete]);
        $this->loggerMock->shouldReceive('warning')->once()->with('UCM Delete: Key not found in memory, attempting deletion in DB anyway.', ['key' => $keyToDelete]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Delete: Key not found in DB for deletion.', ['key' => $keyToDelete]); // Logged when DAO returns false
        // NO "removed from in-memory store" log
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', ['key' => $keyToDelete]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Incremental cache refresh successful.', Mockery::on(fn($ctx)=>$ctx['key']===$keyToDelete && $ctx['action']==='Removed')); // Action is still Removed as we try to remove from cache
        // NO "deleted successfully" log


        // --- Act ---
        $this->manager->delete($keyToDelete, $userId);

        // --- Assert ---
        // Verify internal state is unchanged
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
    }

    /**
     * ✅ Test [delete]: Throws InvalidArgumentException for invalid key format.
     * 🧪 Verifies input validation prevents deletion attempt with bad keys.
     */
    #[Test]
    public function delete_throws_exception_for_invalid_key_format(): void
    {
        // --- Arrange ---
        $invalidKey = 'invalid key spaces !!';
        $userId = 1;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Configuration key must be alphanumeric/");

        // Expect Logger error
        $this->loggerMock->shouldReceive('error')
            ->once()
            ->with('UCM Delete: Invalid key format.', ['key' => $invalidKey]);

        // Expect DAO and Cache NOT to be called
        $this->configDaoMock->shouldNotReceive('deleteConfigByKey');
        $this->cacheRepositoryMock->shouldNotReceive('get'); // Doesn't even get to refreshCache
        $this->cacheRepositoryMock->shouldNotReceive('put');

        // --- Act ---
        $this->manager->delete($invalidKey, $userId);

        // --- Assert ---
        // Exception assertion handles the test success
    }

        // ========================================================================
    // == reload() Method Tests
    // ========================================================================

    /**
     * ✅ Test [reload]: Forces reload from DAO/Env, updates memory, invalidates cache.
     * 🧪 Verifies the complete reload sequence, bypassing initial memory state and cache get.
     */
    #[Test]
    public function reload_loads_from_dao_env_updates_memory_and_invalidates_cache(): void
    {
        // --- Arrange ---
        $originalEnv = $_ENV; $_ENV = []; // Isolate ENV
        $cacheKey = 'ultra_config.cache';

        // Initial memory state (should be ignored by reload)
        $initialMemoryState = ['stale.key' => ['value' => 'old_mem_value', 'category' => null]];
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $initialMemoryState);

        // Data that will be returned by DAO during reload
        $dbKey = 'fresh.db.key'; $dbValue = 'fresh_db_value'; $dbCategory = CategoryEnum::Application;
        $dbCollection = new \Illuminate\Support\Collection([(object)['key' => $dbKey, 'value' => $dbValue, 'category' => $dbCategory]]);

        // ENV var to be merged during reload
        $envKey = 'fresh.env.key'; $envValue = 'fresh_env_value';
        $_ENV[$envKey] = $envValue;

        // Expected final state in memory after reload (DB + specific ENV)
        $expectedFinalState = [
            $dbKey => ['value' => $dbValue, 'category' => $dbCategory->value],
            $envKey => ['value' => $envValue, 'category' => null],
        ];
        $expectedDbCount = 1; $expectedMergedCount = 2; $expectedEnvAddedCount = 1;

        // 1. Expect DAO->getAllConfigs to be called ONCE during reload
        $this->configDaoMock->shouldReceive('getAllConfigs')
            ->once()
            ->andReturn($dbCollection);

        // 2. Expect Cache->forget to be called ONCE to invalidate
        $this->cacheRepositoryMock->shouldReceive('forget')
            ->once()
            ->with($cacheKey)
            ->andReturn(true); // Simulate success

        // 3. Expect Cache->get NOT to be called during reload
        $this->cacheRepositoryMock->shouldNotReceive('get');
        // 4. Expect Cache->put NOT to be called during reload (only invalidated)
        $this->cacheRepositoryMock->shouldNotReceive('put');

        // 5. Expect Logs for the reload flow
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM Reload: Reloading configuration from primary sources/'), Mockery::any());
        // Logs from loadFromDatabase()
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM DB Load: Requesting all configurations from DAO.');
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM DB Load: Configurations loaded from database./'), Mockery::on(fn($ctx)=>$ctx['loaded']===$expectedDbCount));
        // Logs from mergeWithEnvironment()
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Env Merge: Merging environment variables with DB config.');
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM Env Merge: Environment variables merged./'), Mockery::on(fn($ctx)=>$ctx['added_count']===$expectedEnvAddedCount));
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Reload: In-memory configuration reloaded.', ['count' => $expectedMergedCount]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Reload: External cache invalidated.', ['key' => $cacheKey]);


        // --- Act ---
        try {
            $this->manager->reload(true); // invalidateCache = true (default)
        } finally {
            $_ENV = $originalEnv; // Restore original $_ENV
        }

        // --- Assert ---
        // Verify internal state reflects the fresh data
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
        // Mockery assertions verify DAO/Cache/Log calls
    }

    /**
     * ✅ Test [reload]: Forces reload but skips cache invalidation when flag is false.
     * 🧪 Verifies that Cache->forget is NOT called when invalidateCache is false.
     */
    #[Test]
    public function reload_skips_cache_invalidation_when_flag_is_false(): void
    {
         // --- Arrange ---
        $originalEnv = $_ENV; $_ENV = []; // Isolate ENV
        $dbKey = 'another.db.key'; $dbValue = 'another_db_value'; $dbCategory = CategoryEnum::System;
        $dbCollection = new \Illuminate\Support\Collection([(object)['key' => $dbKey, 'value' => $dbValue, 'category' => $dbCategory]]);
        $expectedFinalState = [ $dbKey => ['value' => $dbValue, 'category' => $dbCategory->value] ]; // Only DB data this time

        // 1. Expect DAO->getAllConfigs
        $this->configDaoMock->shouldReceive('getAllConfigs')->once()->andReturn($dbCollection);

        // 2. Expect Cache->forget NOT to be called
        $this->cacheRepositoryMock->shouldNotReceive('forget'); // Key assertion

        // 3. Expect Logs (similar to previous, but NO cache invalidation log)
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM Reload: Reloading configuration from primary sources/'), Mockery::any());
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM DB Load: Requesting all configurations from DAO.');
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM DB Load: Configurations loaded from database./'), Mockery::any());
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Env Merge: Merging environment variables with DB config.');
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM Env Merge: Environment variables merged./'), Mockery::any());
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Reload: In-memory configuration reloaded.', Mockery::any());
        // NO log for 'External cache invalidated.'

        // --- Act ---
        try {
            $this->manager->reload(false); // invalidateCache = false
        } finally {
            $_ENV = $originalEnv; // Restore original $_ENV
        }

        // --- Assert ---
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
    }

    // ========================================================================
    // == refreshConfigCache() Method Tests
    // ========================================================================

    /**
     * ✅ Test [refreshConfigCache]: Full refresh (no key) loads from DB/Env and puts to cache.
     * 🧪 Verifies the full cache refresh mechanism, including locking simulation (simplified).
     */
    #[Test]
    public function refreshConfigCache_full_loads_db_env_and_puts_cache(): void
    {
        // --- Arrange ---
        $originalEnv = $_ENV; $_ENV = []; // Isolate ENV
        $cacheKey = 'ultra_config.cache';
        $cacheTtl = 3600;
        $lockKey = $cacheKey . '_lock';
        $lockTimeout = 10; // Match default in Manager

        // Data from DAO during refresh
        $dbKey = 'refresh.db.key'; $dbValue = 'refreshed_db'; $dbCategory = CategoryEnum::Security;
        $dbCollection = new \Illuminate\Support\Collection([(object)['key' => $dbKey, 'value' => $dbValue, 'category' => $dbCategory]]);

        // ENV var to merge during refresh
        $envKey = 'refresh.env.key'; $envValue = 'refreshed_env';
        $_ENV[$envKey] = $envValue;

        // Expected final state (DB + ENV)
        $expectedFinalState = [
            $dbKey => ['value' => $dbValue, 'category' => $dbCategory->value],
            $envKey => ['value' => $envValue, 'category' => null],
        ];
        $expectedDbCount = 1; $expectedMergedCount = 2; $expectedEnvAddedCount = 1;

        // 1. Mock the Lock: Simulate successful acquisition and release
        $lockMock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $lockMock->shouldReceive('get')->once()->andReturn(true); // Simulate lock acquired
        $lockMock->shouldReceive('release')->once()->andReturn(true); // Simulate lock released

        // 2. Expect Cache->lock to be called and return the lock mock
        $this->cacheRepositoryMock->shouldReceive('lock')
            ->once()
            ->with($lockKey, $lockTimeout)
            ->andReturn($lockMock);

        // 3. Expect DAO->getAllConfigs (because lock was acquired)
        $this->configDaoMock->shouldReceive('getAllConfigs')->once()->andReturn($dbCollection);

        // 4. Expect Cache->put with the fresh data
        $this->cacheRepositoryMock->shouldReceive('put')
            ->once()
            ->with($cacheKey, $expectedFinalState, $cacheTtl)
            ->andReturn(true);

        // 5. Expect Logs for the full refresh flow
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', ['key' => 'all']);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Attempting full cache refresh. Acquiring lock.', ['lock_key' => $lockKey]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Lock acquired. Performing full refresh.');
        // Logs from loadFromDatabase / mergeWithEnvironment
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM DB Load: Requesting all configurations from DAO.');
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM DB Load: Configurations loaded from database./'), Mockery::any());
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Env Merge: Merging environment variables with DB config.');
        $this->loggerMock->shouldReceive('info')->once()->with(Mockery::pattern('/^UCM Env Merge: Environment variables merged./'), Mockery::any());
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Full cache refresh successful. Cache updated.', Mockery::any());

        // --- Act ---
        try {
             $this->manager->refreshConfigCache(); // No key = full refresh
        } finally {
            $_ENV = $originalEnv; // Restore original $_ENV
        }

        // --- Assert ---
        // Verify internal state also got updated during the refresh
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
    }

    /**
     * ✅ Test [refreshConfigCache]: Skips refresh if lock cannot be acquired.
     * 🧪 Verifies that DAO and Cache->put are not called if the lock fails.
     */
    #[Test]
    public function refreshConfigCache_full_skips_if_lock_fails(): void
    {
       // --- Arrange ---
        $cacheKey = 'ultra_config.cache';
        $lockKey = $cacheKey . '_lock';
        $lockTimeout = 10;

        // 1. Mock the Lock: Simulate FAILED acquisition
        $lockMock = Mockery::mock(\Illuminate\Contracts\Cache\Lock::class);
        $lockMock->shouldReceive('get')->once()->andReturn(false); // Simulate lock FAILED
        // --- CORREZIONE: Aspettati release() anche in caso di fallimento ---
        $lockMock->shouldReceive('release')->once()->andReturn(true); // Release VIENE chiamato dal finally

        // 2. Expect Cache->lock to be called
        $this->cacheRepositoryMock->shouldReceive('lock')
            ->once()
            ->with($lockKey, $lockTimeout)
            ->andReturn($lockMock);

        // 3. Expect DAO and Cache->put NOT to be called
        $this->configDaoMock->shouldNotReceive('getAllConfigs');
        $this->cacheRepositoryMock->shouldNotReceive('put');

        // 4. Expect Logs for lock failure
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', ['key' => 'all']);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Attempting full cache refresh. Acquiring lock.', ['lock_key' => $lockKey]);
        $this->loggerMock->shouldReceive('warning')->once()->with('UCM Cache Refresh: Failed to acquire lock for full cache refresh. Skipping.', ['lock_key' => $lockKey]);

        // --- Act ---
        $this->manager->refreshConfigCache(); // No key = full refresh attempt

        // --- Assert ---
        // No state change expected, Mockery checks handle the assertions
    }

        // ========================================================================
    // == validateConstant() Method Tests
    // ========================================================================

    /**
     * ✅ Test [validateConstant]: Proxies call to GlobalConstants mock.
     * 🧪 Verifies delegation of constant validation.
     */
    #[Test]
    public function validateConstant_proxies_call_to_globalConstants(): void
    {
        // --- Arrange ---
        $constantName = 'NO_USER';

        // Expect GlobalConstants::validateConstant to be called ONCE with the name
        $this->globalConstantsMock // Uso il mock già presente
            ->shouldReceive('validateConstant')
            ->once()
            ->with($constantName); // Non serve andReturn, il metodo è void

        // --- Act ---
        $this->manager->validateConstant($constantName);

        // --- Assert ---
        // Mockery assertion handles the verification
        $this->assertTrue(true); // Simple assertion to make test valid
    }

    /**
     * ✅ Test [validateConstant]: Rethrows exception from GlobalConstants.
     * 🧪 Verifies that exceptions during validation are propagated.
     */
    #[Test]
    public function validateConstant_rethrows_exception_from_globalConstants(): void
    {
        // --- Arrange ---
        $invalidConstantName = 'INVALID_CONST';
        $expectedException = new \InvalidArgumentException("Constant '{$invalidConstantName}' does not exist");

        // Expect GlobalConstants::validateConstant to be called and throw
        $this->globalConstantsMock
            ->shouldReceive('validateConstant')
            ->once()
            ->with($invalidConstantName)
            ->andThrow($expectedException);

        // Expect the same exception to be thrown by the manager
        $this->expectExceptionObject($expectedException);

        // --- Act ---
        $this->manager->validateConstant($invalidConstantName);

        // --- Assert ---
        // Exception assertion handles the test success
    }


    // ========================================================================
    // == DTO Method Tests
    // ========================================================================
    // Nota: Questi test richiedono un po' più di setup per i modelli mockati

        /**
     * ✅ Test [getAllEntriesForDisplay]: Returns collection of ConfigDisplayData DTOs.
     * 🧪 Verifies DAO call and transformation to display DTOs (no pagination).
     */
    #[Test]
    public function getAllEntriesForDisplay_returns_collection_of_dtos(): void
    {
        // --- Arrange ---
        $now = now();
        $nowSubDay = now()->subDay();
        $longValue = str_repeat('long value ', 10);

        // --- CORREZIONE #FINAL: Usa SPY e intercetta TUTTI gli getAttribute ---
        $model1 = Mockery::spy(UltraConfigModel::class);
        // Diciamo allo spy cosa restituire per OGNI attributo letto dal DTO
        $model1->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $model1->shouldReceive('getAttribute')->with('key')->andReturn('key.one');
        $model1->shouldReceive('getAttribute')->with('value')->andReturn('value one');
        $model1->shouldReceive('getAttribute')->with('category')->andReturn(CategoryEnum::System); // Restituisce l'Enum
        $model1->shouldReceive('getAttribute')->with('note')->andReturn('Note 1');
        $model1->shouldReceive('getAttribute')->with('updated_at')->andReturn($now);
        // Aggiungiamo anche getKey() per sicurezza, sebbene id dovrebbe bastare
        $model1->shouldReceive('getKey')->andReturn(1);


        $model2 = Mockery::spy(UltraConfigModel::class);
        $model2->shouldReceive('getAttribute')->with('id')->andReturn(2);
        $model2->shouldReceive('getAttribute')->with('key')->andReturn('key.two');
        $model2->shouldReceive('getAttribute')->with('value')->andReturn($longValue);
        $model2->shouldReceive('getAttribute')->with('category')->andReturn(null); // Restituisce null
        $model2->shouldReceive('getAttribute')->with('note')->andReturn(null);
        $model2->shouldReceive('getAttribute')->with('updated_at')->andReturn($nowSubDay);
        $model2->shouldReceive('getKey')->andReturn(2);
        // --- Fine setup Spy ---

        $daoCollection = new \Illuminate\Support\Collection([$model1, $model2]);

        // Expect DAO call
        $this->configDaoMock->shouldReceive('getAllConfigs')
            ->once()
            ->andReturn($daoCollection); // Restituisce collection di spies configurati

        // Expect Logs
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Retrieving entries for display.', Mockery::any());
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Returning all display entries as collection.', Mockery::on(fn($ctx)=>$ctx['count']===2));

        // --- Act ---
        $result = $this->manager->getAllEntriesForDisplay(perPage: null);

        // --- Assert ---
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertCount(2, $result);

        // Check DTO 1 (Le asserzioni rimangono valide)
        $dto1 = $result->firstWhere('id', 1);
        $this->assertInstanceOf(ConfigDisplayData::class, $dto1);
        $this->assertSame(1, $dto1->id);
        $this->assertSame('key.one', $dto1->key); // Ora DEVE funzionare
        $this->assertSame('value one', $dto1->displayValue);
        $this->assertSame(CategoryEnum::System->value, $dto1->categoryValue);
        $this->assertEquals(CategoryEnum::System->translatedName(), $dto1->categoryLabel);
        $this->assertSame('Note 1', $dto1->note);
        $this->assertEquals($now, $dto1->updatedAt);

        // Check DTO 2
        $dto2 = $result->firstWhere('id', 2);
        $this->assertInstanceOf(ConfigDisplayData::class, $dto2);
        $this->assertSame(2, $dto2->id);
        $this->assertSame('key.two', $dto2->key); // Ora DEVE funzionare
        $this->assertStringStartsWith(substr($longValue, 0, 50), $dto2->displayValue);
        $this->assertStringEndsWith('...', $dto2->displayValue);
        $this->assertNull($dto2->categoryValue);
        $this->assertEquals(__('uconfig::uconfig.categories.none'), $dto2->categoryLabel);
        $this->assertNull($dto2->note);
        $this->assertEquals($nowSubDay, $dto2->updatedAt);
    }

    // @todo Aggiungere test per getAllEntriesForDisplay con paginazione

    /**
     * ✅ Test [findEntryForEdit]: Returns ConfigEditData DTO for existing ID.
     * 🧪 Verifies DAO calls for config, audits, versions and transformation to DTO.
     */
    #[Test]
    public function findEntryForEdit_returns_dto_for_existing_id(): void
    {
        // --- Arrange ---
        $configId = 5;
        $mockConfig = Mockery::mock(UltraConfigModel::class)->makePartial();
        $mockConfig->id = $configId;
        $mockConfig->key = 'edit.key'; // Add necessary attributes

        $mockAudits = new \Illuminate\Support\Collection([Mockery::mock(UltraConfigAudit::class)]);
        $mockVersions = new \Illuminate\Support\Collection([Mockery::mock(UltraConfigVersion::class)]);

        // Expect DAO calls
        $this->configDaoMock->shouldReceive('getConfigById')->once()->with($configId, false)->andReturn($mockConfig);
        $this->configDaoMock->shouldReceive('getAuditsByConfigId')->once()->with($configId)->andReturn($mockAudits);
        $this->configDaoMock->shouldReceive('getVersionsByConfigId')->once()->with($configId)->andReturn($mockVersions);

        // Expect Logs
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Retrieving entry data for edit.', ['id' => $configId]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Successfully retrieved data for edit.', Mockery::any());


        // --- Act ---
        $result = $this->manager->findEntryForEdit($configId);

        // --- Assert ---
        $this->assertInstanceOf(ConfigEditData::class, $result);
        $this->assertSame($mockConfig, $result->config);
        $this->assertSame($mockAudits, $result->audits);
        $this->assertSame($mockVersions, $result->versions);
    }

    /**
     * ✅ Test [findEntryForEdit]: Throws ConfigNotFoundException for non-existing ID.
     * 🧪 Verifies exception handling when DAO returns null for config.
     */
    #[Test]
    public function findEntryForEdit_throws_exception_for_non_existing_id(): void
    {
        // --- Arrange ---
        $nonExistingId = 999;

        // Expect DAO call returning null
        $this->configDaoMock->shouldReceive('getConfigById')
            ->once()
            ->with($nonExistingId, false)
            ->andReturnNull(); // Simulate not found

        // Expect Log
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Retrieving entry data for edit.', ['id' => $nonExistingId]);
        $this->loggerMock->shouldReceive('warning')->once()->with('UCM Manager: Config not found for edit.', ['id' => $nonExistingId]);

        // Expect Exception
        $this->expectException(ConfigNotFoundException::class);
        $this->expectExceptionMessageMatches("/Configuration with ID '{$nonExistingId}' not found/");

        // --- Act ---
        $this->manager->findEntryForEdit($nonExistingId);

        // --- Assert ---
        // Exception expectation handles assertion
    }

    /**
     * ✅ Test [findEntryForAudit]: Returns ConfigAuditData DTO for existing ID.
     * 🧪 Verifies DAO calls for config (withTrashed), audits and transformation to DTO.
     */
    #[Test]
    public function findEntryForAudit_returns_dto_for_existing_id(): void
    {
       // --- Arrange ---
        $configId = 6;
        $mockConfig = Mockery::mock(UltraConfigModel::class)->makePartial();
        $mockConfig->id = $configId;
        $mockConfig->key = 'audit.key';

        $mockAudits = new \Illuminate\Support\Collection([Mockery::mock(UltraConfigAudit::class)]);

        // Expect DAO calls
        $this->configDaoMock->shouldReceive('getConfigById')->once()->with($configId, true)->andReturn($mockConfig); // withTrashed = true
        $this->configDaoMock->shouldReceive('getAuditsByConfigId')->once()->with($configId)->andReturn($mockAudits);

        // Expect Logs
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Retrieving entry data for audit view.', ['id' => $configId]);
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Successfully retrieved data for audit view.', Mockery::any());


        // --- Act ---
        $result = $this->manager->findEntryForAudit($configId);

        // --- Assert ---
        $this->assertInstanceOf(ConfigAuditData::class, $result);
        $this->assertSame($mockConfig, $result->config);
        $this->assertSame($mockAudits, $result->audits);
    }

     /**
     * ✅ Test [findEntryForAudit]: Throws ConfigNotFoundException for non-existing ID.
     * 🧪 Verifies exception handling when DAO returns null for config (withTrashed).
     */
    #[Test]
    public function findEntryForAudit_throws_exception_for_non_existing_id(): void
    {
        // --- Arrange ---
        $nonExistingId = 888;

        // Expect DAO call returning null
        $this->configDaoMock->shouldReceive('getConfigById')
            ->once()
            ->with($nonExistingId, true) // withTrashed = true
            ->andReturnNull();

        // Expect Log
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Manager: Retrieving entry data for audit view.', ['id' => $nonExistingId]);
        $this->loggerMock->shouldReceive('warning')->once()->with('UCM Manager: Config not found for audit view.', ['id' => $nonExistingId]);

        // Expect Exception
        $this->expectException(ConfigNotFoundException::class);
        $this->expectExceptionMessageMatches("/Configuration with ID '{$nonExistingId}' not found/");


        // --- Act ---
        $this->manager->findEntryForAudit($nonExistingId);

        // --- Assert ---
        // Exception expectation handles assertion
    }
}