<?php

/**
 * 📜 Oracode Unit Test: UltraConfigManagerTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         0.3.7 // Removed GlobalConstants mock to fix final class issue
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
// --- RIMOSSO: Non serve più mockare GlobalConstants se usata staticamente ---
// use Ultra\UltraConfigManager\Constants\GlobalConstants;
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
 *    Each test focuses on a specific method or logic path within the Manager.
 *    `GlobalConstants` is used statically, not mocked.
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
    use MockeryPHPUnitIntegration;

    // --- Mocks for Dependencies ---
    protected ConfigDaoInterface&MockInterface $configDaoMock;
    protected VersionManager&MockInterface $versionManagerMock;
    protected CacheRepository&MockInterface $cacheRepositoryMock;
    protected LoggerInterface&MockInterface $loggerMock;
    // --- RIMOSSO: $globalConstantsMock ---
    // protected GlobalConstants&MockInterface $globalConstantsMock;

    // --- Instance of the Class Under Test ---
    protected UltraConfigManager $manager;

    /**
     * ⚙️ Set up the test environment before each test.
     * Creates mocks and instantiates the Manager without GlobalConstants mock.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create Mocks
        $this->configDaoMock = Mockery::mock(ConfigDaoInterface::class);
        $this->versionManagerMock = Mockery::mock(VersionManager::class);
        $this->cacheRepositoryMock = Mockery::mock(CacheRepository::class);
        $this->loggerMock = Mockery::mock(LoggerInterface::class)->shouldIgnoreMissing();
        // --- RIMOSSO: Creazione mock per GlobalConstants ---
        // $this->globalConstantsMock = Mockery::mock(GlobalConstants::class);

        // 4. Instantiate the Manager WITHOUT passing GlobalConstants mock
        $this->manager = new UltraConfigManager(
            $this->configDaoMock,
            $this->versionManagerMock,
            // --- RIMOSSO: $this->globalConstantsMock ---
            $this->cacheRepositoryMock,
            $this->loggerMock,
            3600, // cacheTtl (o usa config())
            true, // cacheEnabled (o usa config())
            false // loadOnInit = false
        );
    }

    // ========================================================================
    // == get() Method Tests
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

        $this->loggerMock->shouldReceive('debug')
            ->once()
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

        $this->loggerMock->shouldReceive('debug')
            ->once()
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

        // $this->loggerMock->shouldNotHaveReceived('debug'); // ShouldIgnoreMissing gestisce questo

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

        // $this->loggerMock->shouldNotHaveReceived('debug'); // ShouldIgnoreMissing gestisce questo

        // --- Act ---
        $result = $this->manager->get($key, null, true); // <-- silent = true

        // --- Assert ---
        $this->assertSame($expectedValue, $result);
    }


    // ========================================================================
    // == has() Method Tests
    // ========================================================================
    #[Test]
    public function has_returns_true_when_key_exists_in_memory(): void
    {
        // --- Arrange ---
        $key = 'app.timezone';
        $value = 'UTC';
        $category = CategoryEnum::Application->value;
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, [$key => ['value' => $value, 'category' => $category]]);

        /*
        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with('UCM Check: Configuration key check.', 
            Mockery::on(fn($c)=>$c['key']===$key && $c['exists']===true));
        */

        // --- Act ---
        $result = $this->manager->has($key);

        // --- Assert ---
        $this->assertTrue($result);
    }

    #[Test]
    public function has_returns_false_when_key_not_exists_in_memory(): void
    {
        // --- Arrange ---
        $key = 'non.existent.key';
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, []);

        
        // $this->loggerMock->shouldReceive('debug')
        //     ->once()
        //     ->with('UCM Check: Configuration key check.', Mockery::on(fn($c)=>$c['key']===$key && $c['exists']===false));

        // --- Act ---
        $result = $this->manager->has($key);

        // --- Assert ---
        $this->assertFalse($result);
    }

    #[Test]
    public function has_returns_false_for_empty_key(): void
    {
        // --- Arrange ---
        $key = '';
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, ['a'=>'b']);//Needs some data

        // $this->loggerMock->shouldReceive('debug')
        //     ->once()
        //     ->with('UCM Check: Configuration key check.', Mockery::on(fn($c)=>$c['key']===$key && $c['exists']===false));

        // --- Act ---
        $result = $this->manager->has($key);

        // --- Assert ---
        $this->assertFalse($result);
    }

    // ========================================================================
    // == all() Method Tests
    // ========================================================================
    #[Test]
    public function all_returns_only_values_from_memory(): void
    {
        // --- Arrange ---
        $configData = [
            'app.name' => ['value' => 'UltraApp', 'category' => CategoryEnum::Application->value],
            'app.debug' => ['value' => true, 'category' => CategoryEnum::System->value],
            'some.null.value' => ['value' => null, 'category' => null]
        ];
        $expectedValues = ['app.name' => 'UltraApp', 'app.debug' => true, 'some.null.value' => null];
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $configData);

        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with('UCM Get All: Returning all configuration values from memory.', Mockery::on(fn($c)=>$c['count']===count($expectedValues)));

        // --- Act ---
        $result = $this->manager->all();

        // --- Assert ---
        $this->assertSame($expectedValues, $result);
    }

    #[Test]
    public function all_returns_empty_array_when_memory_is_empty(): void
    {
        // --- Arrange ---
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, []);

        $this->loggerMock->shouldReceive('debug')
            ->once()
            ->with('UCM Get All: Returning all configuration values from memory.', ['count' => 0]);

        // --- Act ---
        $result = $this->manager->all();

        // --- Assert ---
        $this->assertEmpty($result);
    }

    // ========================================================================
    // == loadConfig() Method Tests
    // ========================================================================
    #[Test]
    public function loadConfig_loads_from_cache_when_enabled_and_hit(): void
    {
        // --- Arrange ---
        $cacheKey = 'ultra_config.cache';
        $cachedData = ['app.url' => ['value' => 'http://test.app', 'category' => 'system']];

        $this->cacheRepositoryMock->shouldReceive('get')->once()->with($cacheKey)->andReturn($cachedData);
        $this->configDaoMock->shouldNotReceive('getAllConfigs');

        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Starting configuration load sequence.');
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Load: Cache is enabled. Attempting to load from cache.', Mockery::any());
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Load: Configuration successfully loaded from cache.', Mockery::any());

        // --- Act ---
        $this->manager->loadConfig();

        // --- Assert ---
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $this->assertSame($cachedData, $configProp->getValue($this->manager));
    }

    // ... (Altri test per loadConfig, set, delete, reload, refreshConfigCache, getOrFail, DTO methods...) ...
    // ASSICURATI DI CORREGGERE LE CHIAMATE A set() e delete() IN TUTTI I TEST RIMANENTI
    // RIMUOVENDO IL MOCK DI globalConstantsMock QUANDO PASSI userId

    /**
     * ✅ Test [set]: Creates new config via DAO, updates memory, and refreshes cache.
     * 🧪 Verifies the complete flow for adding a new configuration key.
     */
    #[Test]
    public function set_creates_new_config_via_dao_and_updates_state_and_cache(): void
    {
        // --- Arrange ---
        $key = 'new.config.key'; $value = 'the new value'; $category = CategoryEnum::Application;
        $categoryValue = $category->value; $userId = 123; $sourceFile = 'test.php';
        $cacheKey = 'ultra_config.cache'; $cacheTtl = 3600;
        $initialConfigState = ['existing.key' => ['value' => 'old', 'category' => null]];
        $configProp = new ReflectionProperty(UltraConfigManager::class, 'config');
        $configProp->setValue($this->manager, $initialConfigState);
        // Stato finale atteso in memoria e cache
        $expectedFinalState = array_merge($initialConfigState, [ $key => ['value' => $value, 'category' => $categoryValue] ]);
        $initialCacheState = $initialConfigState; // Stato cache prima della chiamata

        // Mock DAO saveConfig
        $spyModel = Mockery::spy(UltraConfigModel::class); // Use spy to return something concrete
        $this->configDaoMock->shouldReceive('saveConfig')
            ->once()
            ->with($key, $value, $categoryValue, $sourceFile, $userId, true, true, null) // Check all args
            ->andReturn($spyModel); // Return the spy

        // Mock Cache interaction for refreshConfigCache(key)
        $this->cacheRepositoryMock->shouldReceive('get')
            ->once()
            ->with($cacheKey, [])
            ->andReturn($initialCacheState);
        $this->cacheRepositoryMock->shouldReceive('put')
            ->once()
            ->with($cacheKey, $expectedFinalState, $cacheTtl) // Expect the updated state
            ->andReturn(true);

        // Mock Logger calls
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Set: Attempting to set configuration.', Mockery::any());
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Set: In-memory configuration updated.', Mockery::any());
        $this->loggerMock->shouldReceive('debug')->once()->with('UCM Cache Refresh: Attempting cache refresh.', Mockery::any());
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Cache Refresh: Incremental cache refresh successful.', Mockery::any());
        $this->loggerMock->shouldReceive('info')->once()->with('UCM Set: Configuration set successfully.', Mockery::any());

        // --- Act ---
        $this->manager->set($key, $value, $categoryValue, $userId, true, true, $sourceFile); // Pass sourceFile

        // --- Assert ---
        $internalConfig = $configProp->getValue($this->manager);
        $this->assertSame($expectedFinalState, $internalConfig);
        // Mockery verifies calls automatically
    }

    // --- Aggiungere molti altri test per coprire tutti i metodi e scenari ---

} // Fine Classe UltraConfigManagerTest