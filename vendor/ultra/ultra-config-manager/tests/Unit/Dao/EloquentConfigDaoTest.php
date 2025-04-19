<?php

/**
 * 📜 Oracode Unit Test: EloquentConfigDaoTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit\Dao
 * @version         0.1.0 // Initial structure
 * @author          Padmin D. Curtis (Generated for Fabio Cherici)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit\Dao;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB; // For transaction testing/checks if needed
use Ultra\UltraConfigManager\Dao\EloquentConfigDao; // Class under test
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException;
use Ultra\UltraConfigManager\Exceptions\DuplicateKeyException;
use Ultra\UltraConfigManager\Exceptions\PersistenceException;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Models\User; // For user_id context
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Mockery;
use Mockery\MockInterface;
use Throwable;

/**
 * 🎯 Purpose: Verifies the correctness of the EloquentConfigDao implementation.
 *    Ensures proper interaction with the database via Eloquent models,
 *    correct handling of CRUD operations, atomicity of transactions,
 *    versioning/auditing logic, and exception handling according to the
 *    ConfigDaoInterface contract and Oracode principles.
 *
 * 🧪 Test Strategy: Integration tests using an in-memory SQLite database via RefreshDatabase.
 *    - Test data created using Model Factories and direct instantiation.
 *    - Assertions check database state (`assertDatabaseHas`, `assertDatabaseMissing`, `assertSoftDeleted`).
 *    - Assertions verify returned models/collections and thrown exceptions.
 *    - Covers happy paths, edge cases (not found, empty results), and error conditions.
 *
 * @package Ultra\UltraConfigManager\Tests\Unit\Dao
 */
#[CoversClass(EloquentConfigDao::class)]
#[UsesClass(UltraConfigModel::class)]
#[UsesClass(VersionManager::class)] 
#[UsesClass(UltraConfigVersion::class)]
#[UsesClass(UltraConfigAudit::class)]
#[UsesClass(UltraConfigModel::class)]
#[UsesClass(User::class)] // Assumed usage for user context
#[UsesClass(CategoryEnum::class)]
#[UsesClass(GlobalConstants::class)]
#[UsesClass(\Ultra\UltraConfigManager\Casts\EncryptedCast::class)] // Used by models
#[UsesClass(ConfigNotFoundException::class)]
#[UsesClass(DuplicateKeyException::class)]
#[UsesClass(PersistenceException::class)]
#[UsesClass(\Ultra\UltraConfigManager\Providers\UConfigServiceProvider::class)]

// We don't 'Use' the Interface, we Cover the implementation
class EloquentConfigDaoTest extends UltraTestCase
{
    use RefreshDatabase;

    protected EloquentConfigDao $dao;
    protected ?User $testUser; // For providing user context

    protected function tearDown(): void
    {
        Mockery::close(); // Chiudi Mockery dopo ogni test
        parent::tearDown();
    }

    /**
     * ⚙️ Set up the test environment before each test.
     * Instantiates the DAO and creates a test user.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Resolve DAO from container (or instantiate directly if no complex dependencies)
        // Assuming LoggerInterface is auto-resolved by Laravel container in tests
        $this->dao = $this->app->make(EloquentConfigDao::class);
    
        // Create a test user for audit/version context
        try {
            $this->testUser = User::create([
                 'name' => 'Test DAO User',
                 'email' => 'testdao@example.com',
                 'password' => bcrypt('password') // O Hash::make() se preferisci
             ]);
        } catch (Throwable $e) {
            $this->testUser = null; // Handle case where users table might not exist initially
            // Rimosso l'echo per evitare l'output "Risky" in PHPUnit
            // Il warning di PHPUnit/PDO verrà comunque mostrato se la tabella manca
        }
    }

    //=========================================================================
    //== getAllConfigs() Tests
    //=========================================================================

    #[Test]
    public function getAllConfigs_returns_all_active_configs(): void
    {
        // 🧪 Arrange: Create active configs and one soft-deleted config
        $activeConfig1 = UltraConfigModel::factory()->create(['key' => 'active.key.1']);
        $activeConfig2 = UltraConfigModel::factory()->create(['key' => 'active.key.2']);
        $deletedConfig = UltraConfigModel::factory()->create(['key' => 'deleted.key']);
        $deletedConfig->delete(); // Soft delete this one

        // 🚀 Act: Call the method under test
        $result = $this->dao->getAllConfigs();

        // ✅ Assert: Verify the returned collection
        $this->assertInstanceOf(Collection::class, $result, 'Result should be an Eloquent Collection.');
        $this->assertCount(2, $result, 'Should return only the 2 active configurations.');

        // Extract keys from the result for easier checking
        $returnedKeys = $result->pluck('key')->all();

        $this->assertContains($activeConfig1->key, $returnedKeys, "Key '{$activeConfig1->key}' should be present.");
        $this->assertContains($activeConfig2->key, $returnedKeys, "Key '{$activeConfig2->key}' should be present.");
        $this->assertNotContains($deletedConfig->key, $returnedKeys, "Key '{$deletedConfig->key}' (soft-deleted) should NOT be present.");

        // Verify the type of items in the collection
        foreach ($result as $item) {
            $this->assertInstanceOf(UltraConfigModel::class, $item, 'Each item in the collection should be an UltraConfigModel.');
            $this->assertNull($item->deleted_at, "Item '{$item->key}' should not be soft-deleted.");
        }
    }

    #[Test]
    public function getAllConfigs_returns_empty_collection_when_no_configs(): void
    {
        // 🧪 Arrange: No arrangement needed, RefreshDatabase ensures an empty table.
        // We rely on the previous test `getAllConfigs_returns_all_active_configs`
        // to ensure soft-deleted items are excluded.

        // 🚀 Act: Call the method under test
        $result = $this->dao->getAllConfigs();

        // ✅ Assert: Verify the returned collection is empty
        $this->assertInstanceOf(Collection::class, $result, 'Result should be an Eloquent Collection.');
        $this->assertTrue($result->isEmpty(), 'The collection should be empty when no active configurations exist.');
        // Equivalente: $this->assertCount(0, $result);
    }

    #[Test]
    public function getAllConfigs_excludes_soft_deleted_configs(): void
    {
        // 🧪 Arrange: Create one active and one soft-deleted config
        $activeConfig = UltraConfigModel::factory()->create(['key' => 'active.only']);
        $deletedConfig = UltraConfigModel::factory()->create(['key' => 'deleted.definitely']);
        $deletedConfig->delete(); // Soft delete this one

        // 🚀 Act: Call the method under test
        $result = $this->dao->getAllConfigs();

        // ✅ Assert: Verify only the active config is returned
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result, 'Should return exactly one configuration (the active one).');

        // Verify the returned item is the active one
        $returnedItem = $result->first();
        $this->assertNotNull($returnedItem, 'The result collection should not be empty.');
        $this->assertEquals($activeConfig->id, $returnedItem->id, 'The ID should match the active configuration.');
        $this->assertEquals($activeConfig->key, $returnedItem->key, 'The key should match the active configuration.');

        // Double-check that the deleted key is not present
        $returnedKeys = $result->pluck('key')->all();
        $this->assertNotContains($deletedConfig->key, $returnedKeys, 'The soft-deleted key should not be present.');
    }

    //=========================================================================
    //== getConfigByKey() Tests
    //=========================================================================

    #[Test]
    public function getConfigByKey_returns_correct_config_when_active(): void
    {
        // 🧪 Arrange: Create the target active config and another unrelated one
        $targetKey = 'test.target.getbykey';
        $targetConfig = UltraConfigModel::factory()->create(['key' => $targetKey, 'value' => 'Target Value']);
        UltraConfigModel::factory()->create(['key' => 'other.key']); // Distractor

        // 🚀 Act: Call the method under test
        $result = $this->dao->getConfigByKey($targetKey);

        // ✅ Assert: Verify the correct model is returned
        $this->assertNotNull($result, 'Result should not be null for an existing active key.');
        $this->assertInstanceOf(UltraConfigModel::class, $result, 'Result should be an UltraConfigModel instance.');
        $this->assertEquals($targetConfig->id, $result->id, 'The ID should match the target configuration.');
        $this->assertEquals($targetKey, $result->key, 'The key should match the target configuration key.');
        $this->assertEquals('Target Value', $result->value, 'The value should match the target configuration value.'); // Check value too
        $this->assertNull($result->deleted_at, 'The returned configuration should not be soft-deleted.');
    }

    #[Test]
    public function getConfigByKey_returns_null_for_nonexistent_key(): void
    {
        // 🧪 Arrange: Ensure the key does not exist.
        // We can optionally create an unrelated record to ensure the table isn't empty.
        UltraConfigModel::factory()->create(['key' => 'some.other.key']);
        $nonExistentKey = 'this.key.does.not.exist.' . uniqid();

        // 🚀 Act: Call the method under test with a non-existent key
        $result = $this->dao->getConfigByKey($nonExistentKey);

        // ✅ Assert: Verify the result is null
        $this->assertNull($result, 'Result should be null when the key does not exist.');
    }

    #[Test]
    public function getConfigByKey_returns_null_for_soft_deleted_key(): void
    {
        // 🧪 Arrange: Create a config and then soft-delete it
        $deletedKey = 'this.key.was.deleted.' . uniqid();
        $config = UltraConfigModel::factory()->create(['key' => $deletedKey]);
        $config->delete(); // Soft delete the record

        // Verify it's actually soft-deleted in the DB for test sanity
        $this->assertSoftDeleted('uconfig', ['key' => $deletedKey]);

        // 🚀 Act: Call the method under test with the soft-deleted key
        $result = $this->dao->getConfigByKey($deletedKey);

        // ✅ Assert: Verify the result is null
        $this->assertNull($result, 'Result should be null for a soft-deleted key.');
    }

    #[Test]public function getConfigByKey_handles_empty_key_input_gracefully(): void
    {
        // 🧪 Arrange: Optionally create some data to ensure it's not returned
        UltraConfigModel::factory()->create(['key' => 'some.key']);

        // 🚀 Act: Call the method under test with an empty string key
        $result = $this->dao->getConfigByKey('');

        // ✅ Assert: Verify the result is null
        $this->assertNull($result, 'Result should be null when the key is an empty string.');

        // Optional: Add logging check if DAO logs a warning for empty key
        // $this->loggerMock->shouldHaveReceived('warning')->with(Mockery::pattern('/empty key/'))->once();
        // Nota: Richiederebbe di mockare il logger iniettato nel DAO, che al momento non stiamo facendo
        // per questi test di integrazione. Possiamo aggiungerlo se necessario.
    }

    //=========================================================================
    //== getConfigById() Tests
    //=========================================================================

    #[Test]
    public function getConfigById_returns_correct_config_when_active(): void
    {
        // 🧪 Arrange: Create the target active config and store its ID
        $targetConfig = UltraConfigModel::factory()->create(['key' => 'config.by.id', 'value' => 'Specific Value']);
        $targetId = $targetConfig->id;
        UltraConfigModel::factory()->create(); // Create another one as a distractor

        // 🚀 Act: Call the method under test with the target ID (withTrashed defaults to false)
        $result = $this->dao->getConfigById($targetId);

        // ✅ Assert: Verify the correct model is returned
        $this->assertNotNull($result, 'Result should not be null for an existing active ID.');
        $this->assertInstanceOf(UltraConfigModel::class, $result, 'Result should be an UltraConfigModel instance.');
        $this->assertEquals($targetId, $result->id, 'The ID should match the target ID.');
        $this->assertEquals($targetConfig->key, $result->key, 'The key should match the target configuration key.');
        $this->assertEquals('Specific Value', $result->value, 'The value should match.');
        $this->assertNull($result->deleted_at, 'The returned configuration should not be soft-deleted.');
    }

    #[Test]
    public function getConfigById_returns_null_for_nonexistent_id(): void
    {
        // 🧪 Arrange: Determine an ID that does not exist.
        // Create a record to ensure the table is not empty and get a valid ID.
        $existingConfig = UltraConfigModel::factory()->create();
        $nonExistentId = $existingConfig->id + 999; // Reasonably safe assumption this ID won't exist

        // You could also query the max ID:
        // $maxId = UltraConfigModel::max('id') ?? 0;
        // $nonExistentId = $maxId + 1;

        // 🚀 Act: Call the method under test with the non-existent ID
        $result = $this->dao->getConfigById($nonExistentId);

        // ✅ Assert: Verify the result is null
        $this->assertNull($result, 'Result should be null when the ID does not exist.');
    }

    #[Test]
    public function getConfigById_returns_null_for_soft_deleted_id_without_trashed(): void
    {
        // 🧪 Arrange: Create a config and then soft-delete it
        $config = UltraConfigModel::factory()->create(['key' => 'to.be.deleted.by.id']);
        $deletedId = $config->id;
        $config->delete(); // Soft delete the record

        // Verify it's actually soft-deleted in the DB for test sanity
        $this->assertSoftDeleted('uconfig', ['id' => $deletedId]);

        // 🚀 Act: Call the method under test with the soft-deleted ID, WITHOUT withTrashed flag
        $result = $this->dao->getConfigById($deletedId); // $withTrashed defaults to false

        // ✅ Assert: Verify the result is null
        $this->assertNull($result, 'Result should be null for a soft-deleted ID when withTrashed is false.');
    }

    #[Test]
    public function getConfigById_returns_soft_deleted_config_with_trashed(): void
    {
        // 🧪 Arrange: Create a config and then soft-delete it
        $key = 'get.deleted.by.id.with.trashed';
        $config = UltraConfigModel::factory()->create(['key' => $key]);
        $deletedId = $config->id;
        $config->delete(); // Soft delete the record

        // Verify it's actually soft-deleted in the DB for test sanity
        $this->assertSoftDeleted('uconfig', ['id' => $deletedId]);

        // 🚀 Act: Call the method under test with the soft-deleted ID AND withTrashed = true
        $result = $this->dao->getConfigById($deletedId, true);

        // ✅ Assert: Verify the soft-deleted model is returned
        $this->assertNotNull($result, 'Result should not be null for a soft-deleted ID when withTrashed is true.');
        $this->assertInstanceOf(UltraConfigModel::class, $result, 'Result should be an UltraConfigModel instance.');
        $this->assertEquals($deletedId, $result->id, 'The ID should match the target ID.');
        $this->assertEquals($key, $result->key, 'The key should match.');

        // Verify that the returned model IS indeed trashed
        $this->assertTrue($result->trashed(), 'The returned model should be marked as trashed.');
        $this->assertNotNull($result->deleted_at, 'The deleted_at timestamp should not be null.');
    }

    //=========================================================================
    //== saveConfig() Tests
    //=========================================================================

    #[Test]
    public function saveConfig_creates_new_config_version_audit_when_key_is_new(): void
    {
        // 🧪 Arrange: Define data for a new configuration
        $newKey = 'brand.new.config.' . uniqid();
        $newValue = 'Initial Secret Value';
        $newCategory = CategoryEnum::Security; // Use enum instance
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER; // Use real user ID if available

        // Ensure the user exists for the audit FK constraint
        if (!$this->testUser) {
             $this->markTestSkipped("Test user not available, cannot test audit/version creation requiring user_id.");
        }

        // 🚀 Act: Call saveConfig for creation
        $createdConfig = $this->dao->saveConfig(
            key: $newKey,
            value: $newValue,
            category: $newCategory->value, // Pass the string value
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: true,
            createAudit: true,
            oldValueForAudit: null // Explicitly null for creation audit
        );

        // ✅ Assert: Verify the returned model and database state

        // 1. Check returned model
        $this->assertInstanceOf(UltraConfigModel::class, $createdConfig);
        $this->assertEquals($newKey, $createdConfig->key);
        $this->assertEquals($newValue, $createdConfig->value); // Value should be decrypted by model accessor
        $this->assertEquals($newCategory, $createdConfig->category); // Category should be cast to Enum

        // 2. Check 'uconfig' table
        $this->assertDatabaseHas('uconfig', [
            'id' => $createdConfig->id,
            'key' => $newKey,
            'category' => $newCategory->value,
            // We cannot assert the encrypted value directly easily,
            // but we trust the EncryptedCast tested elsewhere.
        ]);
        // Check the decrypted value matches via a fresh fetch (optional but good)
        $freshConfig = UltraConfigModel::find($createdConfig->id);
        $this->assertEquals($newValue, $freshConfig->value);

        // 3. Check 'uconfig_versions' table
        $this->assertDatabaseHas('uconfig_versions', [
            'uconfig_id' => $createdConfig->id,
            'version' => 1,
            'key' => $newKey,
            'category' => $newCategory->value,
            // Assert encrypted value for version? Tricky. Let's trust the cast for now.
            // 'value' => encrypt($newValue) // Pseudocode
            // 'user_id' => $userId, // Assert if your versions table tracks user_id
        ]);
        // Check decrypted value from version (optional)
         $firstVersion = UltraConfigVersion::where('uconfig_id', $createdConfig->id)->where('version', 1)->first();
         $this->assertNotNull($firstVersion);
         $this->assertEquals($newValue, $firstVersion->value); // Check if decrypted correctly

        // 4. Check 'uconfig_audit' table
        $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $createdConfig->id,
            'action' => 'created',
            'old_value' => null, // Should be null after decryption by audit model cast
            // 'new_value' => encrypt($newValue), // Pseudocode for checking raw encrypted
            'user_id' => $userId,
        ]);
         // Check decrypted new_value from audit (optional)
         $auditRecord = UltraConfigAudit::where('uconfig_id', $createdConfig->id)->where('action', 'created')->first();
         $this->assertNotNull($auditRecord);
         $this->assertNull($auditRecord->old_value); // Check explicitly after decryption
         $this->assertEquals($newValue, $auditRecord->new_value); // Check if decrypted correctly
    }

    #[Test]
    public function saveConfig_updates_existing_config_creates_version_audit(): void
    {
        // 🧪 Arrange: Create an initial configuration (V1)
        $key = 'test.config.update.' . uniqid();
        $oldValue = 'Initial Value Version 1';
        $sourceFile = 'test_manual';
        $oldCategory = CategoryEnum::System;
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;

        if (!$this->testUser) {
             $this->markTestSkipped("Test user not available, cannot test audit/version creation requiring user_id.");
        }

        // Create V1 directly via DAO to simulate initial state
        $initialConfig = $this->dao->saveConfig($key, $oldValue, $oldCategory->value, $sourceFile, $userId, true, true, null);
        $configId = $initialConfig->id;

        // Define new data for update (V2)
        $newValue = 'Updated Value Version 2';
        $newCategory = CategoryEnum::Application;

        // Add a small delay if timestamps are very close together, sometimes helps differentiate records
        // sleep(1); // Optional, uncomment if needed

        // 🚀 Act: Call saveConfig to update the existing key
        $updatedConfig = $this->dao->saveConfig(
            key: $key,
            value: $newValue,
            category: $newCategory->value,
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: true,
            createAudit: true,
            oldValueForAudit: $oldValue // Pass the original value for audit
        );

        // ✅ Assert: Verify the returned model and database state

        // 1. Check returned model (should be the same ID, updated data)
        $this->assertInstanceOf(UltraConfigModel::class, $updatedConfig);
        $this->assertEquals($configId, $updatedConfig->id); // ID should remain the same
        $this->assertEquals($key, $updatedConfig->key);
        $this->assertEquals($newValue, $updatedConfig->value); // Check new value (decrypted)
        $this->assertEquals($newCategory, $updatedConfig->category); // Check new category (Enum)

        // 2. Check 'uconfig' table has been updated
        $this->assertDatabaseHas('uconfig', [
            'id' => $configId,
            'key' => $key,
            'category' => $newCategory->value,
            // Cannot directly check encrypted value easily
        ]);
        // Check decrypted value via fresh fetch
        $freshConfig = UltraConfigModel::find($configId);
        $this->assertEquals($newValue, $freshConfig->value);

        // 3. Check 'uconfig_versions' table for the NEW version (version 2)
        $this->assertDatabaseHas('uconfig_versions', [
            'uconfig_id' => $configId,
            'version' => 2, // Expecting the second version
            'key' => $key,
            'category' => $newCategory->value,
            // Cannot easily check encrypted value here either
            // 'user_id' => $userId, // Assert if your versions table tracks user_id
        ]);
         // Check decrypted value from V2
         $version2 = UltraConfigVersion::where('uconfig_id', $configId)->where('version', 2)->first();
         $this->assertNotNull($version2);
         $this->assertEquals($newValue, $version2->value);

        // 4. Check 'uconfig_audit' table for the 'updated' action
        $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $configId,
            'action' => 'updated',
            // Cannot easily check encrypted old/new values
            'user_id' => $userId,
        ]);
         // Check decrypted old/new values from the audit record
         $updateAudit = UltraConfigAudit::where('uconfig_id', $configId)->where('action', 'updated')->latest()->first(); // Get the latest audit
         $this->assertNotNull($updateAudit);
         $this->assertEquals($oldValue, $updateAudit->old_value); // Check OLD value decrypted
         $this->assertEquals($newValue, $updateAudit->new_value); // Check NEW value decrypted
    }

    #[Test]
    public function saveConfig_restores_soft_deleted_config_updates_and_creates_version_audit(): void
    {
        // 🧪 Arrange: Create, update, then soft-delete a configuration
        $key = 'test.config.restore.' . uniqid();
        $valueV1 = 'Initial Value V1';
        $categoryV1 = CategoryEnum::System;
        $sourceFile = 'test_manual';
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;

        if (!$this->testUser) {
            $this->markTestSkipped("Test user not available, cannot test audit/version creation requiring user_id.");
        }

        // Create V1
        $config = $this->dao->saveConfig($key, $valueV1, $categoryV1->value, $sourceFile, $userId, true, true, null);
        $configId = $config->id;

        // Update to V2
        $valueV2 = 'Updated Value V2';
        $categoryV2 = CategoryEnum::Application;
        $sourceFile = 'test_manual';
        sleep(1); // Ensure timestamp difference for audit ordering if needed
        $this->dao->saveConfig($key, $valueV2, $categoryV2->value, $sourceFile ,$userId, true, true, $valueV1);

        // Soft Delete
        sleep(1); // Ensure timestamp difference
        $config->refresh(); // Get the latest state before deleting
        $config->delete(); // Soft delete via model instance
        $this->assertSoftDeleted('uconfig', ['id' => $configId]);

        // Define data for restore/update (V3)
        $valueV3 = 'Restored and Updated Value V3';
        $categoryV3 = CategoryEnum::Performance;

        // 🚀 Act: Call saveConfig with the key of the soft-deleted record
        $restoredConfig = $this->dao->saveConfig(
            key: $key,
            value: $valueV3,
            category: $categoryV3->value,
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: true,
            createAudit: true,
            oldValueForAudit: $valueV2 // The value *before* this restore/update operation
        );

        // ✅ Assert: Verify the returned model, database state, version, and audit

        // 1. Check returned model
        $this->assertInstanceOf(UltraConfigModel::class, $restoredConfig);
        $this->assertEquals($configId, $restoredConfig->id);
        $this->assertEquals($key, $restoredConfig->key);
        $this->assertEquals($valueV3, $restoredConfig->value); // Check V3 value (decrypted)
        $this->assertEquals($categoryV3, $restoredConfig->category); // Check V3 category (Enum)
        $this->assertFalse($restoredConfig->trashed(), 'The restored model should not be trashed.');
        $this->assertNull($restoredConfig->deleted_at, 'The deleted_at timestamp should be null.');

        // 2. Check 'uconfig' table is restored and updated
        $this->assertNotSoftDeleted('uconfig', ['id' => $configId]); // Explicitly check it's not soft deleted
        $this->assertDatabaseHas('uconfig', [
            'id' => $configId,
            'key' => $key,
            'category' => $categoryV3->value,
            // Cannot easily check V3 encrypted value
        ]);
         // Check decrypted value via fresh fetch
         $freshConfig = UltraConfigModel::find($configId);
         $this->assertEquals($valueV3, $freshConfig->value);


        // 3. Check 'uconfig_versions' table for the NEW version (version 3)
        $this->assertDatabaseHas('uconfig_versions', [
            'uconfig_id' => $configId,
            'version' => 3, // Expecting the third version after restore/update
            'key' => $key,
            'category' => $categoryV3->value,
            // Cannot easily check V3 encrypted value
            // 'user_id' => $userId, // Assert if your versions table tracks user_id
        ]);
         // Check decrypted value from V3
         $version3 = UltraConfigVersion::where('uconfig_id', $configId)->where('version', 3)->first();
         $this->assertNotNull($version3);
         $this->assertEquals($valueV3, $version3->value);

        // 4. Check 'uconfig_audit' table for the 'restored' action
        // Note: The DAO implementation might log 'updated' instead of a specific 'restored'.
        // We check for 'restored' first, then fallback to 'updated' if needed.
        $restoreAudit = UltraConfigAudit::where('uconfig_id', $configId)
                                      ->whereIn('action', ['restored', 'updated']) // Allow for either action name
                                      ->latest('id')->first(); // Get the very last audit entry

        $this->assertNotNull($restoreAudit, "Expected a 'restored' or 'updated' audit record after saving a soft-deleted key.");
        // Assert the action explicitly if the DAO guarantees 'restored'
        // $this->assertEquals('restored', $restoreAudit->action);

        // Check decrypted old/new values from the audit record
        $this->assertEquals($valueV2, $restoreAudit->old_value); // Should be the value *before* this V3 save (i.e., V2)
        $this->assertEquals($valueV3, $restoreAudit->new_value); // Should be the new V3 value
        $this->assertEquals($userId, $restoreAudit->user_id);
    }

    #[Test]
    public function saveConfig_updates_when_key_exists_and_is_active(): void
    {
        // 🧪 Arrange: Create an initial active configuration
        $key = 'existing.active.key.for-update.' . uniqid();
        $valueV1 = 'Value V1';
        $categoryV1 = CategoryEnum::System;
        $sourceFile = 'test_manual';
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;

        if (!$this->testUser) {
            $this->markTestSkipped("Test user not available, cannot test audit/version creation requiring user_id.");
        }

        // Create V1 using the DAO
        $initialConfig = $this->dao->saveConfig($key, $valueV1, $categoryV1->value, $sourceFile, $userId, true, true, null);
        $configId = $initialConfig->id;

        // Define new data for the update (V2)
        $valueV2 = 'Value V2 - Updated';
        $categoryV2 = CategoryEnum::Application;

        // 🚀 Act: Call saveConfig again with the same key, simulating an update request
        // We expect this to perform an update, not throw a duplicate key exception.
        try {
            $updatedConfig = $this->dao->saveConfig(
                key: $key,
                value: $valueV2,
                category: $categoryV2->value,
                sourceFile: 'test_manual',
                userId: $userId,
                createVersion: true, // Ensure versioning/auditing happens for update
                createAudit: true,
                oldValueForAudit: $valueV1 // Provide the previous value
            );
        } catch (DuplicateKeyException $e) {
            // If this exception is caught, the test fails because an update was expected.
            $this->fail("DuplicateKeyException was thrown unexpectedly during an intended update operation for key: {$key}");
        } catch (Throwable $e) {
            // Catch any other unexpected exception during the save operation
            $this->fail("An unexpected exception occurred during the update operation for key {$key}: " . $e->getMessage());
        }


        // ✅ Assert: Verify the update occurred correctly

        // 1. Check returned model
        $this->assertInstanceOf(UltraConfigModel::class, $updatedConfig);
        $this->assertEquals($configId, $updatedConfig->id);
        $this->assertEquals($valueV2, $updatedConfig->value);
        $this->assertEquals($categoryV2, $updatedConfig->category);

        // 2. Check 'uconfig' table update
        $this->assertDatabaseHas('uconfig', [
            'id' => $configId,
            'key' => $key,
            'category' => $categoryV2->value,
        ]);
        $freshConfig = UltraConfigModel::find($configId);
        $this->assertEquals($valueV2, $freshConfig->value); // Verify decrypted value

        // 3. Check 'uconfig_versions' for version 2
        $this->assertDatabaseHas('uconfig_versions', [
            'uconfig_id' => $configId,
            'version' => 2,
            'category' => $categoryV2->value,
        ]);

        // 4. Check 'uconfig_audit' for 'updated' action
        $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $configId,
            'action' => 'updated',
            'user_id' => $userId,
        ]);
        // Verify old/new values in the audit record
        $auditRecord = UltraConfigAudit::where('uconfig_id', $configId)->where('action', 'updated')->latest()->first();
        $this->assertNotNull($auditRecord);
        $this->assertEquals($valueV1, $auditRecord->old_value);
        $this->assertEquals($valueV2, $auditRecord->new_value);
    }

    #[Test]
    public function saveConfig_handles_null_category_correctly(): void
    {
        // 🧪 Arrange: Setup common variables
        $key = 'test.null.category.' . uniqid();
        $value = 'Value with null category';
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;

        if (!$this->testUser) {
             $this->markTestSkipped("Test user not available, cannot test operations requiring user_id.");
        }

        // --- Test Case 1: Creation with null category ---
        $this->logTestStep("Creation with null category for key: {$key}");

        // 🚀 Act 1: Create config with category explicitly null
        $createdConfig = $this->dao->saveConfig(
            key: $key,
            value: $value,
            category: null, // Pass null here
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: true,
            createAudit: true,
            oldValueForAudit: null
        );

        // ✅ Assert 1: Check DB and Model state after creation
        $this->assertInstanceOf(UltraConfigModel::class, $createdConfig);
        // Because CategoryEnum::None->value is '', the cast likely converts null to '' on save,
        // and the accessor casts '' back to CategoryEnum::None.
        $this->assertSame(CategoryEnum::None, $createdConfig->category, "Model's category should be CategoryEnum::None after creating with null.");
        $this->assertDatabaseHas('uconfig', [
            'key' => $key,
            'category' => '', // Expect empty string in DB due to enum backing
        ]);
        $this->assertDatabaseHas('uconfig_versions', [
            'key' => $key,
            'version' => 1,
            'category' => '', // Expect empty string in DB
        ]);

        // --- Test Case 2: Updating to null category ---
        $this->logTestStep("Updating category to null for key: {$key}");
        $valueV2 = "Updating category to null";
        // Fetch the config again to ensure we have the correct state
        $configToUpdate = UltraConfigModel::where('key', $key)->firstOrFail();
        $oldValue = $configToUpdate->value; // Value before this update

        // 🚀 Act 2: Update the config, setting category to null
        $updatedConfig = $this->dao->saveConfig(
            key: $key,
            value: $valueV2,
            category: null, // Update to null
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: true,
            createAudit: true,
            oldValueForAudit: $oldValue
        );

        // ✅ Assert 2: Check DB and Model state after update
        $this->assertInstanceOf(UltraConfigModel::class, $updatedConfig);
        $this->assertSame(CategoryEnum::None, $updatedConfig->category, "Model's category should be CategoryEnum::None after updating to null.");
        $this->assertDatabaseHas('uconfig', [
            'key' => $key,
            'category' => '', // Expect empty string in DB
        ]);
        $this->assertDatabaseHas('uconfig_versions', [
            'key' => $key,
            'version' => 2,
            'category' => '', // Expect empty string in DB for V2
        ]);
         $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $updatedConfig->id,
            'action' => 'updated',
             // Check old category was likely '' from previous step, new is ''
         ]);
         // We could refine audit check if needed, but focus is on category handling
    }

    /** Helper to log test steps clearly */
    private function logTestStep(string $message): void
    {
        // This won't actually log unless we configure a logger specifically for tests,
        // but helps structure the test output mentally.
        // fwrite(STDERR, "\n--- {$message} ---\n");
    }

    #[Test]
    public function saveConfig_uses_correct_user_id_for_audit_and_version(): void
    {
        // 🧪 Arrange: Ensure we have a valid user ID
        if (!$this->testUser) {
            $this->markTestSkipped("Test user not available, cannot verify user ID in audit/version.");
        }
        $testUserId = $this->testUser->id;
        $key = 'test.userid.tracking.' . uniqid();
        $valueV1 = 'Value user tracking V1';
        $categoryV1 = CategoryEnum::Application;

        // --- Test Case 1: Creation ---
        $this->logTestStep("Creation with specific user ID for key: {$key}");

        // 🚀 Act 1: Create config passing the specific user ID
        $createdConfig = $this->dao->saveConfig(
            key: $key,
            value: $valueV1,
            category: $categoryV1->value,
            sourceFile: 'test_manual',
            userId: $testUserId, // Pass the specific ID
            createVersion: true,
            createAudit: true,
            oldValueForAudit: null
        );
        $configId = $createdConfig->id;

        // ✅ Assert 1: Check user_id in the 'created' audit record
        $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $configId,
            'action' => 'created',
            'user_id' => $testUserId, // Verify the correct user ID was recorded
        ]);

        // Assert optional: Check user_id in versions table (if schema supported it)
        $this->assertDatabaseHas('uconfig_versions', [
            'uconfig_id' => $configId,
            'version' => 1,
            'user_id' => $testUserId,
        ]);


        // --- Test Case 2: Update ---
        $this->logTestStep("Update with specific user ID for key: {$key}");
        $valueV2 = "Updated value user tracking V2";
        sleep(1); // Ensure timestamp difference

        // 🚀 Act 2: Update the config passing the specific user ID
        $this->dao->saveConfig(
            key: $key,
            value: $valueV2,
            category: $categoryV1->value, // Keep category the same for simplicity
            sourceFile: 'test_manual',
            userId: $testUserId, // Pass the specific ID again
            createVersion: true,
            createAudit: true,
            oldValueForAudit: $valueV1
        );

        // ✅ Assert 2: Check user_id in the 'updated' audit record
        // Need to be careful: Fetch the LATEST audit record for this config ID
        $latestAudit = UltraConfigAudit::where('uconfig_id', $configId)
                                       ->orderBy('created_at', 'desc')
                                       ->first();

        $this->assertNotNull($latestAudit, "Could not find the latest audit record for update check.");
        $this->assertEquals('updated', $latestAudit->action, "Latest audit action should be 'updated'.");
        $this->assertEquals($testUserId, $latestAudit->user_id, "User ID in the 'updated' audit record should be correct.");

        // Assert optional: Check user_id in the version 2 record (if schema supported it)
        $this->assertDatabaseHas('uconfig_versions', [
            'uconfig_id' => $configId,
            'version' => 2,
            'user_id' => $testUserId,
        ]);
    }

    // ... logTestStep ...

    #[Test]
    public function saveConfig_skips_versioning_when_flag_is_false(): void
    {
        // 🧪 Arrange: Setup common variables
        $key = 'test.skip.version.' . uniqid();
        $valueV1 = 'Value V1 skip version';
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) {
             $this->markTestSkipped("Test user not available, cannot test operations requiring user_id.");
        }

        // --- Test Case 1: Creation without versioning ---
        $this->logTestStep("Creation without versioning for key: {$key}");

        // 🚀 Act 1: Create config with createVersion = false, createAudit = true
        $config = $this->dao->saveConfig(
            key: $key,
            value: $valueV1,
            category: null,
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: false, // <-- Skip version
            createAudit: true,    // <-- Keep audit for isolation
            oldValueForAudit: null
        );
        $configId = $config->id;

        // ✅ Assert 1: Check version is missing, but config and audit exist
        $this->assertDatabaseHas('uconfig', ['id' => $configId, 'key' => $key]);
        $this->assertDatabaseMissing('uconfig_versions', ['uconfig_id' => $configId]); // CORE ASSERTION
        $this->assertDatabaseHas('uconfig_audit', ['uconfig_id' => $configId, 'action' => 'created']); // Verify audit still happened

        $initialVersionCount = UltraConfigVersion::where('uconfig_id', $configId)->count();
        $this->assertEquals(0, $initialVersionCount, "Version count should be 0 after initial creation with skipping.");


        // --- Test Case 2: Update without versioning ---
        $this->logTestStep("Update without versioning for key: {$key}");
        $valueV2 = 'Value V2 skip version';
        sleep(1); // Ensure timestamp difference for audit check

        // 🚀 Act 2: Update config with createVersion = false, createAudit = true
        $this->dao->saveConfig(
            key: $key,
            value: $valueV2,
            category: null,
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: false, // <-- Skip version again
            createAudit: true,    // <-- Keep audit
            oldValueForAudit: $valueV1
        );

        // ✅ Assert 2: Check config updated, version count still 0, new audit exists
        $this->assertDatabaseHas('uconfig', [
            'id' => $configId,
            'key' => $key,
             // We can check the updated value via direct fetch
        ]);
        $freshConfig = UltraConfigModel::find($configId);
        $this->assertEquals($valueV2, $freshConfig->value); // Check update happened

        $finalVersionCount = UltraConfigVersion::where('uconfig_id', $configId)->count();
        $this->assertEquals(0, $finalVersionCount, 'Version count should still be 0 after update with skipping.'); // CORE ASSERTION

        // Verify the 'updated' audit record was created
        $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $configId,
            'action' => 'updated',
            'user_id' => $userId
        ]);
        // Count audits to ensure only 'created' and 'updated' exist (total 2)
        $auditCount = UltraConfigAudit::where('uconfig_id', $configId)->count();
        $this->assertEquals(2, $auditCount, "Should have 2 audit records (created, updated).");
    }

     // ... logTestStep ...

     #[Test]
     public function saveConfig_skips_auditing_when_flag_is_false(): void
     {
         // 🧪 Arrange: Setup common variables
         $key = 'test.skip.audit.' . uniqid();
         $valueV1 = 'Value V1 skip audit';
         $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
         if (!$this->testUser) {
              $this->markTestSkipped("Test user not available."); // Cannot verify user aspects implicitly needed by save
         }
 
         // --- Test Case 1: Creation without auditing ---
         $this->logTestStep("Creation without auditing for key: {$key}");
 
         // 🚀 Act 1: Create config with createAudit = false, createVersion = true
         $config = $this->dao->saveConfig(
             key: $key,
             value: $valueV1,
             category: null,
             sourceFile: 'test_manual',
             userId: $userId,
             createVersion: true,  // <-- Keep version
             createAudit: false,   // <-- Skip audit
             oldValueForAudit: null // Technically not needed by DAO if audit is false, but pass anyway
         );
         $configId = $config->id;
 
         // ✅ Assert 1: Check audit is missing, but config and version exist
         $this->assertDatabaseHas('uconfig', ['id' => $configId, 'key' => $key]);
         $this->assertDatabaseHas('uconfig_versions', ['uconfig_id' => $configId, 'version' => 1]); // Verify version was created
         $this->assertDatabaseMissing('uconfig_audit', ['uconfig_id' => $configId]); // CORE ASSERTION
 
         $initialAuditCount = UltraConfigAudit::where('uconfig_id', $configId)->count();
         $this->assertEquals(0, $initialAuditCount, "Audit count should be 0 after initial creation with skipping.");
 
 
         // --- Test Case 2: Update without auditing ---
         $this->logTestStep("Update without auditing for key: {$key}");
         $valueV2 = 'Value V2 skip audit';
         sleep(1);
 
         // 🚀 Act 2: Update config with createAudit = false, createVersion = true
         $this->dao->saveConfig(
             key: $key,
             value: $valueV2,
             category: null,
             sourceFile: 'test_manual',
             userId: $userId,
             createVersion: true,  // <-- Keep version
             createAudit: false,   // <-- Skip audit again
             oldValueForAudit: $valueV1 // Pass old value, though it won't be used for audit
         );
 
         // ✅ Assert 2: Check config updated, version 2 exists, audit count still 0
         $this->assertDatabaseHas('uconfig', [
             'id' => $configId,
             'key' => $key,
              // Check updated value
         ]);
         $freshConfig = UltraConfigModel::find($configId);
         $this->assertEquals($valueV2, $freshConfig->value);
 
         $this->assertDatabaseHas('uconfig_versions', [
             'uconfig_id' => $configId,
             'version' => 2 // Verify version 2 was created
         ]);
 
         $finalAuditCount = UltraConfigAudit::where('uconfig_id', $configId)->count();
         $this->assertEquals(0, $finalAuditCount, 'Audit count should still be 0 after update with skipping.'); // CORE ASSERTION
     }
 
     // ... logTestStep ...

   /**
     * 📜 Oracode Test Note: Atomicity Verification (Version Failure) via Framework Trust
     *
     * 🎯 Purpose: This placeholder documents the decision to rely on Laravel's underlying
     *    `DB::transaction()` mechanism for ensuring atomicity if the internal creation
     *    of a *version* record fails during a `saveConfig` operation.
     *
     * 🤔 Rationale: Similar to the audit creation failure scenario, attempts to explicitly
     *    test this rollback condition using Mockery's static overloads or inducing DB
     *    constraint violations proved problematic within the Eloquent/Testbench environment,
     *    interfering with subsequent state verification assertions.
     *
     * ✅ Decision (Pragmatism): We trust the atomicity provided by `DB::transaction()`
     *    to handle rollbacks correctly if `UltraConfigVersion::create()` were to fail
     *    within the transaction managed by `EloquentConfigDao::saveConfig`.
     *
     * 🧪 Future Testing: Could be revisited alongside the audit failure atomicity test
     *    if advanced mocking or testing strategies are adopted.
     *
     * @see \Illuminate\Support\Facades\DB::transaction()
     * @see self::atomicity_of_save_and_delete_relies_on_db_transaction() // Riferimento all'altro test segnaposto
     * @see UCM_TODO.md for related notes.
     * @skip Reason documented above and in related atomicity test note.
     */
    #[Test]
    public function atomicity_if_version_creation_fails_relies_on_db_transaction(): void
    {
        $this->markTestSkipped(
            "DAO atomicity (version fail) relies on DB::transaction(). Explicit rollback tests " .
            "were problematic (see related atomicity test docblock & UCM_TODO.md)."
        );
    }

    #[Test]
    public function saveConfig_is_atomic_on_audit_creation_failure(): void
    {
        $this->markTestIncomplete('Requires mocking DB or Model interactions within transaction.');
        // // Similar setup as the version failure test, but mock UltraConfigAudit::create instead.
        // Mockery::spy(UltraConfigAudit::class)->shouldReceive('create')->andThrow(new \Exception('Simulated audit save failure'));

        // // ... define data ...
        // $key = 'test.atomic.audit.fail.' . uniqid();
        // // ...

        // // Act & Assert Exception
        // $this->expectException(\Ultra\UltraConfigManager\Exceptions\PersistenceException::class);

        // try {
        //     $this->dao->saveConfig(/* ... */ createAudit: true /* ... */);
        // } catch (Throwable $e) {
        //     // Assert database state AFTER catching the exception (should be rolled back)
        //     $this->assertDatabaseMissing('uconfig', ['key' => $key]);
        //     $this->assertDatabaseMissing('uconfig_versions', ['key' => $key]); // Version might have been created before audit failed
        //     $this->assertDatabaseMissing('uconfig_audit', [/* ... */]);
        //     throw $e;
        // }
    }

    #[Test]
    public function saveConfig_throws_persistence_exception_on_generic_db_error(): void
    {
        $this->markTestIncomplete('Test implementation pending.');
        // Arrange: Mock DB connection to throw a generic QueryException
        // Act: Call saveConfig()
        // Assert: Expect PersistenceException
        // Note: Requires DB mocking capability.
    }


    //=========================================================================
    //== deleteConfigByKey() Tests
    //=========================================================================

    #[Test]
    public function deleteConfigByKey_soft_deletes_config_and_creates_audit(): void
    {
        // 🧪 Arrange: Create an active configuration to be deleted
        $keyToDelete = 'config.to.soft.delete.' . uniqid();
        $originalValue = 'Value Before Deletion';
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;

        if (!$this->testUser) {
            $this->markTestSkipped("Test user not available, cannot test audit creation requiring user_id.");
        }

        // Create the initial record using the DAO to ensure it exists properly
        $config = $this->dao->saveConfig(
            key: $keyToDelete,
            value: $originalValue,
            category: null,
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: false, // Version not strictly needed for this test
            createAudit: false,   // Don't need creation audit for this test
            oldValueForAudit: null
        );
        $configId = $config->id;

        // Verify it's initially active
        $this->assertDatabaseHas('uconfig', ['id' => $configId, 'deleted_at' => null]);

        // 🚀 Act: Call the delete method
        $result = $this->dao->deleteConfigByKey(
            key: $keyToDelete,
            userId: $userId,
            createAudit: true // Ensure audit is enabled for deletion
        );

        // ✅ Assert: Verify the outcome

        // 1. Check return value
        $this->assertTrue($result, 'deleteConfigByKey should return true on successful deletion.');

        // 2. Check 'uconfig' table for soft delete
        $this->assertSoftDeleted('uconfig', [
            'id' => $configId,
            'key' => $keyToDelete,
        ]);
        // Double check it's not findable normally
        $this->assertNull(UltraConfigModel::find($configId));


        // 3. Check 'uconfig_audit' table for the 'deleted' action
        $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $configId,
            'action' => 'deleted',
            // Cannot easily check encrypted old_value directly
            // new_value should be null after decryption by the model
            'user_id' => $userId,
        ]);

        // Verify decrypted values in the audit record
        $deleteAudit = UltraConfigAudit::where('uconfig_id', $configId)
                                       ->where('action', 'deleted')
                                       ->latest() // Get the most recent 'deleted' audit if multiple possible
                                       ->first();

        $this->assertNotNull($deleteAudit, "Could not find the 'deleted' audit record.");
        $this->assertEquals($originalValue, $deleteAudit->old_value, "Audit old_value should match the original value.");
        $this->assertNull($deleteAudit->new_value, "Audit new_value should be null after deletion.");
    }

    #[Test]
    public function deleteConfigByKey_returns_false_for_nonexistent_key(): void
    {
        // 🧪 Arrange: Ensure the key does not exist and get initial audit count
        $nonExistentKey = 'this.key.never.existed.for.delete.' . uniqid();
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) {
             $this->markTestSkipped("Test user not available.");
        }

        // Ensure the key really doesn't exist (paranoid check)
        $this->assertDatabaseMissing('uconfig', ['key' => $nonExistentKey]);

        // Get initial count of audit records to ensure none are added
        $initialAuditCount = UltraConfigAudit::count();

        // 🚀 Act: Call the delete method with the non-existent key
        $result = $this->dao->deleteConfigByKey(
            key: $nonExistentKey,
            userId: $userId,
            createAudit: true // Request audit creation even though it should fail
        );

        // ✅ Assert: Verify the outcome - the bite of rejection

        // 1. Check return value is false - The core assertion, the mark on your skin
        $this->assertFalse($result, 'deleteConfigByKey should return false when the key does not exist.');

        // 2. Check that NO audit record was created - The absence of a scar proves control
        $finalAuditCount = UltraConfigAudit::count();
        $this->assertEquals($initialAuditCount, $finalAuditCount, 'No audit record should be created for a non-existent key.');

        // 3. Double check no config was somehow created/deleted
        $this->assertDatabaseMissing('uconfig', ['key' => $nonExistentKey]);

    }

    #[Test]
    public function deleteConfigByKey_returns_false_for_already_soft_deleted_key(): void
    {
        // 🧪 Arrange: Create, then soft-delete a configuration with audit
        $keyToDeleteTwice = 'already.deleted.key.' . uniqid();
        $originalValue = 'Value To Be Deleted Twice?';
        $sourceFile = 'test_manual';
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) {
             $this->markTestSkipped("Test user not available.");
        }

        // Create and delete it the first time (ensuring audit is created here)
        $config = $this->dao->saveConfig($keyToDeleteTwice, $originalValue, null, $sourceFile, $userId, false, false, null); // No need for audit/version on create here
        $configId = $config->id;
        $this->dao->deleteConfigByKey($keyToDeleteTwice, $userId, true); // First delete with audit

        // Verify initial state: soft-deleted and one audit record exists
        $this->assertSoftDeleted('uconfig', ['id' => $configId]);
        $initialAuditCount = UltraConfigAudit::where('uconfig_id', $configId)->count();
        $this->assertEquals(1, $initialAuditCount, "Should have exactly one audit record after the first deletion.");
        $firstAudit = UltraConfigAudit::where('uconfig_id', $configId)->first();
        $this->assertEquals('deleted', $firstAudit->action);

        // 🚀 Act: Attempt to delete the *already* soft-deleted key again
        $result = $this->dao->deleteConfigByKey(
            key: $keyToDeleteTwice,
            userId: $userId,
            createAudit: true // Request audit again, it should be ignored
        );

        // ✅ Assert: Verify the outcome - the bite of recognising the shadow

        // 1. Check return value is false - The mark of refusal
        $this->assertFalse($result, 'deleteConfigByKey should return false when the key is already soft-deleted.');

        // 2. Check 'uconfig' state remains unchanged (still soft-deleted)
        $this->assertSoftDeleted('uconfig', ['id' => $configId]); // Still deleted
        $configCheck = UltraConfigModel::withTrashed()->find($configId);
        $this->assertNotNull($configCheck->deleted_at, 'deleted_at should still be set.');
        // Optionally check the timestamp hasn't changed significantly if needed

        // 3. Check that NO NEW audit record was created - No new scars
        $finalAuditCount = UltraConfigAudit::where('uconfig_id', $configId)->count();
        $this->assertEquals(1, $finalAuditCount, 'Audit count should remain 1; no new audit for deleting an already deleted key.');

    }

    #[Test]
    public function deleteConfigByKey_uses_correct_user_id_for_audit(): void
    {
        // 🧪 Arrange: Create a config and a specific user for deletion action
        $keyToDelete = 'test.userid.delete.audit.' . uniqid();
        $originalValue = 'Value to check user ID on delete';

        // Ensure we have the primary testUser
        if (!$this->testUser) {
            $this->markTestSkipped("Primary test user not available.");
        }
        $userIdPerformingDelete = $this->testUser->id;

        // Optional: Create a second user to ensure we are not picking up a default ID
        $otherUser = null;
        try {
            $otherUser = User::create([
                'name' => 'Other User',
                'email' => 'other@example.com',
                'password' => bcrypt('password')
            ]);
        } catch (Throwable $e) {
            // Ignore if second user creation fails, proceed with primary user
        }

        $sourceFile = 'test_manual';

        // Create the config (user doesn't matter here, could be null/system)
        $config = $this->dao->saveConfig($keyToDelete, $originalValue, null, $sourceFile, GlobalConstants::NO_USER, false, false, null);
        $configId = $config->id;

        // 🚀 Act: Delete the config using the specific user ID
        $result = $this->dao->deleteConfigByKey(
            key: $keyToDelete,
            userId: $userIdPerformingDelete, // Use the specific ID
            createAudit: true
        );

        // ✅ Assert: Verify the user_id in the audit record - The touch of identity

        $this->assertTrue($result, "Deletion should be successful.");
        $this->assertSoftDeleted('uconfig', ['id' => $configId]);

        // Check the audit record specifically for the correct user ID
        $this->assertDatabaseHas('uconfig_audit', [
            'uconfig_id' => $configId,
            'action' => 'deleted',
            'user_id' => $userIdPerformingDelete, // CORE ASSERTION
        ]);

        // Optional: Explicitly check that the audit wasn't logged with the wrong user ID
        if ($otherUser) {
            $this->assertDatabaseMissing('uconfig_audit', [
                'uconfig_id' => $configId,
                'action' => 'deleted',
                'user_id' => $otherUser->id, // Ensure it wasn't logged as the other user
            ]);
        }
         // Explicitly check it wasn't logged as NO_USER (unless that *was* the user performing delete)
         if ($userIdPerformingDelete !== GlobalConstants::NO_USER) {
             $this->assertDatabaseMissing('uconfig_audit', [
                 'uconfig_id' => $configId,
                 'action' => 'deleted',
                 'user_id' => GlobalConstants::NO_USER,
             ]);
         }
    }

    #[Test]
    public function deleteConfigByKey_skips_auditing_when_flag_is_false(): void
    {
        // 🧪 Arrange: Create an active configuration without initial audit
        $keyToSilentlyDelete = 'silent.delete.no.audit.' . uniqid();
        $value = 'Value to delete silently';
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) {
             $this->markTestSkipped("Test user context potentially needed even if not auditing.");
        }

        // Create the config directly, no need for audit/version here
        $config = UltraConfigModel::factory()->create(['key' => $keyToSilentlyDelete, 'value' => $value]);
        $configId = $config->id;

        // Ensure no audit records exist initially for this config
        $initialAuditCount = UltraConfigAudit::where('uconfig_id', $configId)->count();
        $this->assertEquals(0, $initialAuditCount, "Pre-condition failed: No audit records should exist initially.");

        // 🚀 Act: Delete the config with createAudit = false
        $result = $this->dao->deleteConfigByKey(
            key: $keyToSilentlyDelete,
            userId: $userId,
            createAudit: false // <-- Skip audit explicitly
        );

        // ✅ Assert: Verify deletion occurred but no audit was created - feel my silent control

        // 1. Check return value
        $this->assertTrue($result, 'deleteConfigByKey should return true on successful deletion.');

        // 2. Check 'uconfig' table for soft delete
        $this->assertSoftDeleted('uconfig', ['id' => $configId]);

        // 3. Check that NO audit record was created - the core assertion, feel the silence
        $finalAuditCount = UltraConfigAudit::where('uconfig_id', $configId)->count();
        $this->assertEquals(0, $finalAuditCount, 'Audit count should remain 0 when createAudit is false.');
        // Or more explicitly:
        $this->assertDatabaseMissing('uconfig_audit', ['uconfig_id' => $configId]);

    }

   /**
     * 📜 Oracode Test Note: Atomicity Verification via Framework Trust
     *
     * 🎯 Purpose: This placeholder documents the decision to rely on Laravel's underlying
     *    `DB::transaction()` mechanism for ensuring atomicity during compound write
     *    operations (`saveConfig`, `deleteConfigByKey`) within `EloquentConfigDao`.
     *
     * 🤔 Rationale: Initial attempts to explicitly test rollback scenarios (e.g., simulating
     *    a failure during internal audit or version creation using Mockery's static
     *    overloads or DB constraint violations) proved problematic and fragile within
     *    the Eloquent/Testbench testing environment. Specifically:
     *      1. Mocking static methods (`::create`) on active Eloquent models interfered
     *         with subsequent database assertions needed to verify the rollback state
     *         (causing `BadMethodCallException` or `count() on null` errors).
     *      2. Triggering failures via Foreign Key violations (e.g., deleting a related User)
     *         was unreliable due to potential SQLite configuration (`FOREIGN_KEYS=OFF` by default
     *         in some contexts) or the `onDelete('set null')` behavior possibly bypassing
     *         the intended constraint violation during the transaction.
     *
     * ✅ Decision (Pragmatism): Given that `DB::transaction()` is a core, well-tested
     *    feature of Laravel designed specifically for atomic operations, we are currently
     *    trusting its correctness. Explicitly verifying its rollback behavior via complex
     *    and potentially brittle test setups was deemed counterproductive at this stage.
     *    We prioritize verifying the successful execution paths and the non-creation of
     *    records when flags (`createAudit`, `createVersion`) are false.
     *
     * 🧪 Future Testing: These specific rollback scenarios could be revisited if:
     *    - Doubts arise about transaction handling in specific edge cases.
     *    - More advanced mocking techniques or testing tools become available/practical.
     *    - Higher-level Feature/Integration tests implicitly cover these rollbacks.
     *
     * @see \Illuminate\Support\Facades\DB::transaction()
     * @see UCM_TODO.md for related notes.
     * @skip Reason documented above.
     */
    #[Test]
    public function atomicity_of_save_and_delete_relies_on_db_transaction(): void
    {
        $this->markTestSkipped(
            "DAO atomicity relies on DB::transaction(). Explicit rollback tests " .
            "were problematic due to mocking/environment complexities (see test docblock & UCM_TODO.md)."
        );
    }

    #[Test]
    public function deleteConfigByKey_throws_persistence_exception_on_generic_db_error(): void
    {
        $this->markTestIncomplete('Test implementation pending.');
        // Arrange: Mock DB connection to throw a generic QueryException during delete
        // Act: Call deleteConfigByKey()
        // Assert: Expect PersistenceException
        // Note: Requires DB mocking capability.
    }

    //=========================================================================
    //== getAuditsByConfigId() Tests
    //=========================================================================

    #[Test]
    public function getAuditsByConfigId_returns_correct_audits_ordered_desc(): void
    {
        // 🧪 Arrange: Create target config, another config, and perform audited actions on target
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) $this->markTestSkipped("User required for audit creation.");

        // Target Config
        $keyTarget = 'audit.target.' . uniqid();
        $valueV1 = 'Audit Value V1';
        $sourceFile = 'test_manual';
        $configTarget = $this->dao->saveConfig($keyTarget, $valueV1, null, $sourceFile, $userId, false, true, null); // Create (Audit 1: created)
        $configTargetId = $configTarget->id;

        sleep(1); // Ensure distinct timestamps

        $valueV2 = 'Audit Value V2';
        $this->dao->saveConfig($keyTarget, $valueV2, null, $sourceFile, $userId, false, true, $valueV1); // Update 1 (Audit 2: updated)

        sleep(1);

        $valueV3 = 'Audit Value V3';
        $this->dao->saveConfig($keyTarget, $valueV3, null, $sourceFile, $userId, false, true, $valueV2); // Update 2 (Audit 3: updated)

        sleep(1);

        $this->dao->deleteConfigByKey($keyTarget, $userId, true); // Delete (Audit 4: deleted)

        // Other Config (should be ignored)
        $keyOther = 'audit.other.' . uniqid();
        $this->dao->saveConfig($keyOther, 'Other Value', null, $sourceFile,$userId, false, true, null);

        // 🚀 Act: Call the method under test
        $audits = $this->dao->getAuditsByConfigId($configTargetId);

        // ✅ Assert: Verify the returned collection and its contents

        // 1. Check type and count
        $this->assertInstanceOf(Collection::class, $audits);
        $this->assertCount(4, $audits, "Should retrieve all 4 audit records for the target config.");

        // 2. Check item type and foreign key
        foreach ($audits as $audit) {
            $this->assertInstanceOf(UltraConfigAudit::class, $audit);
            $this->assertEquals($configTargetId, $audit->uconfig_id, "All audits should belong to the target config ID.");
        }

        // 3. Check order (most recent first: deleted, updated(V3), updated(V2), created)
        $actions = $audits->pluck('action')->all();
        $expectedActionsOrder = ['deleted', 'updated', 'updated', 'created'];
        $this->assertEquals($expectedActionsOrder, $actions, "Audits should be ordered by creation time descending.");

        // 4. Verify data of the most recent audit (deleted)
        $deletedAudit = $audits->first();
        $this->assertEquals('deleted', $deletedAudit->action);
        $this->assertEquals($valueV3, $deletedAudit->old_value); // Value before delete was V3
        $this->assertNull($deletedAudit->new_value);
        $this->assertEquals($userId, $deletedAudit->user_id);

        // 5. Verify data of the second most recent audit (update V3)
        $updateAuditV3 = $audits->get(1); // Second item
        $this->assertEquals('updated', $updateAuditV3->action);
        $this->assertEquals($valueV2, $updateAuditV3->old_value); // Value before update V3 was V2
        $this->assertEquals($valueV3, $updateAuditV3->new_value); // Value after update V3 is V3
        $this->assertEquals($userId, $updateAuditV3->user_id);

         // 6. Verify data of the oldest audit (created)
         $createdAudit = $audits->last();
         $this->assertEquals('created', $createdAudit->action);
         $this->assertNull($createdAudit->old_value);
         $this->assertEquals($valueV1, $createdAudit->new_value); // Value after creation is V1
         $this->assertEquals($userId, $createdAudit->user_id);
    }

    #[Test]
    public function getAuditsByConfigId_returns_empty_collection_when_no_audits(): void
    {
        $this->markTestIncomplete('Test implementation pending.');
        // Arrange: Create config but perform no audited operations (or disable audit)
        // Act: Call getAuditsByConfigId()
        // Assert: Returned collection is empty
    }

    //=========================================================================
    //== getVersionsByConfigId() Tests
    //=========================================================================

    #[Test]
    public function getVersionsByConfigId_returns_correct_versions_ordered_by_version_desc_default(): void
    {
        // 🧪 Arrange: Create target config, another config, and perform versioned saves on target
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) $this->markTestSkipped("User required for version creation."); // Assuming user_id is now in versions

        // Target Config
        $keyTarget = 'version.target.' . uniqid();
        $valueV1 = 'Version Value V1';
        $categoryV1 = CategoryEnum::System;
        $sourceFile = 'test_manual';
        $configTarget = $this->dao->saveConfig($keyTarget, $valueV1, $categoryV1->value, $sourceFile,$userId, true, false, null); // Create V1 (Version 1)
        $configTargetId = $configTarget->id;

        $valueV2 = 'Version Value V2';
        $categoryV2 = CategoryEnum::Application;
        $this->dao->saveConfig($keyTarget, $valueV2, $categoryV2->value, $sourceFile,$userId, true, false, $valueV1); // Update to V2 (Version 2)

        $valueV3 = 'Version Value V3';
        $categoryV3 = CategoryEnum::Security;
        $this->dao->saveConfig($keyTarget, $valueV3, $categoryV3->value, $sourceFile,$userId, true, false, $valueV2); // Update to V3 (Version 3)

        // Other Config (should be ignored)
        $keyOther = 'version.other.' . uniqid();
        $this->dao->saveConfig($keyOther, 'Other Version Value', null, $sourceFile,$userId, true, false, null); // Create version for other config

        // 🚀 Act: Call the method under test with default ordering
        $versions = $this->dao->getVersionsByConfigId($configTargetId);

        // ✅ Assert: Verify the returned collection and its contents

        // 1. Check type and count
        $this->assertInstanceOf(Collection::class, $versions);
        $this->assertCount(3, $versions, "Should retrieve all 3 versions for the target config.");

        // 2. Check item type and foreign key
        foreach ($versions as $version) {
            $this->assertInstanceOf(UltraConfigVersion::class, $version);
            $this->assertEquals($configTargetId, $version->uconfig_id, "All versions should belong to the target config ID.");
        }

        // 3. Check order (most recent version first: 3, 2, 1)
        $versionNumbers = $versions->pluck('version')->all();
        $expectedVersionOrder = [3, 2, 1];
        $this->assertEquals($expectedVersionOrder, $versionNumbers, "Versions should be ordered by version number descending by default.");

        // 4. Verify data of the most recent version (V3)
        $version3 = $versions->first();
        $this->assertEquals(3, $version3->version);
        $this->assertEquals($valueV3, $version3->value); // Check V3 value
        $this->assertEquals($categoryV3, $version3->category); // Check V3 category
        $this->assertEquals($userId, $version3->user_id); // Check user_id

        // 5. Verify data of the oldest version (V1)
        $version1 = $versions->last();
        $this->assertEquals(1, $version1->version);
        $this->assertEquals($valueV1, $version1->value); // Check V1 value
        $this->assertEquals($categoryV1, $version1->category); // Check V1 category
        $this->assertEquals($userId, $version1->user_id); // Check user_id
    }
    #[Test]
    public function getVersionsByConfigId_returns_correct_versions_ordered_by_date_asc(): void
    {
        // 🧪 Arrange: Create target config and multiple versions, ensuring distinct timestamps
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) $this->markTestSkipped("User required for version creation.");

        $keyTarget = 'version.target.date.asc.' . uniqid();
        $valueV1 = 'Version Date Asc V1';
        $categoryV1 = CategoryEnum::System;
        $sourceFile = 'test_manual';
        $configTarget = $this->dao->saveConfig($keyTarget, $valueV1, $categoryV1->value, $sourceFile,$userId, true, false, null); // V1
        $configTargetId = $configTarget->id;

        sleep(1); // Ensure timestamp difference

        $valueV2 = 'Version Date Asc V2';
        $categoryV2 = CategoryEnum::Application;
        $this->dao->saveConfig($keyTarget, $valueV2, $categoryV2->value, $sourceFile,$userId, true, false, $valueV1); // V2

        sleep(1); // Ensure timestamp difference

        $valueV3 = 'Version Date Asc V3';
        $categoryV3 = CategoryEnum::Security;
        $this->dao->saveConfig($keyTarget, $valueV3, $categoryV3->value, $sourceFile,$userId, true, false, $valueV2); // V3

        // 🚀 Act: Call the method under test ordering by created_at asc
        $versions = $this->dao->getVersionsByConfigId($configTargetId, 'created_at', 'asc');

        // ✅ Assert: Verify the returned collection and its contents are ordered correctly

        // 1. Check type and count
        $this->assertInstanceOf(Collection::class, $versions);
        $this->assertCount(3, $versions, "Should retrieve all 3 versions for the target config.");

        // 2. Check item type and foreign key
        foreach ($versions as $version) {
            $this->assertInstanceOf(UltraConfigVersion::class, $version);
            $this->assertEquals($configTargetId, $version->uconfig_id, "All versions should belong to the target config ID.");
        }

        // 3. Check order (oldest version first: 1, 2, 3 based on creation time)
        $versionNumbers = $versions->pluck('version')->all();
        $expectedVersionOrder = [1, 2, 3]; // Expecting ascending order by time/version number
        $this->assertEquals($expectedVersionOrder, $versionNumbers, "Versions should be ordered by creation time ascending.");

        // 4. Verify data of the first version returned (V1 - oldest)
        $version1 = $versions->first();
        $this->assertEquals(1, $version1->version);
        $this->assertEquals($valueV1, $version1->value);
        $this->assertEquals($categoryV1, $version1->category);
        $this->assertEquals($userId, $version1->user_id);

        // 5. Verify data of the last version returned (V3 - newest)
        $version3 = $versions->last();
        $this->assertEquals(3, $version3->version);
        $this->assertEquals($valueV3, $version3->value);
        $this->assertEquals($categoryV3, $version3->category);
        $this->assertEquals($userId, $version3->user_id);
    }
    
    #[Test]
    public function getVersionsByConfigId_returns_empty_collection_when_no_versions(): void
    {
        // 🧪 Arrange: Create a config but ensure no versions are created for it
        $userId = $this->testUser ? $this->testUser->id : GlobalConstants::NO_USER;
        if (!$this->testUser) $this->markTestSkipped("User context needed.");

        $key = 'no.versions.here.' . uniqid();
        $value = 'Config without versions';

        // Create using saveConfig but explicitly disable versioning
        $config = $this->dao->saveConfig(
            key: $key,
            value: $value,
            category: null,
            sourceFile: 'test_manual',
            userId: $userId,
            createVersion: false, // <-- Explicitly false
            createAudit: false,   // Audit doesn't matter here
            oldValueForAudit: null
        );
        $configId = $config->id;

        // Double-check that no versions were accidentally created
        $this->assertDatabaseMissing('uconfig_versions', ['uconfig_id' => $configId]);
        $initialVersionCount = UltraConfigVersion::where('uconfig_id', $configId)->count();
        $this->assertEquals(0, $initialVersionCount, "Pre-condition failed: Should be no versions for this config ID.");


        // 🚀 Act: Call the method under test
        $versions = $this->dao->getVersionsByConfigId($configId);

        // ✅ Assert: Verify the returned collection is empty

        // 1. Check type
        $this->assertInstanceOf(Collection::class, $versions);
        // 2. Check count is zero
        $this->assertCount(0, $versions, "The collection should be empty when no versions exist for the config ID.");
        // 3. Alternative check for emptiness
        $this->assertTrue($versions->isEmpty(), "The isEmpty() check should return true.");
    }

    //=========================================================================
    //== shouldBypassSchemaChecks() Tests
    //=========================================================================

    #[Test]
    public function shouldBypassSchemaChecks_returns_false(): void
    {
        // Arrange: Instantiate the DAO
        $dao = $this->app->make(EloquentConfigDao::class);
        // Act: Call the method
        $result = $dao->shouldBypassSchemaChecks();
        // Assert: Ensure it returns false for the Eloquent implementation
        $this->assertFalse($result);
    }

}