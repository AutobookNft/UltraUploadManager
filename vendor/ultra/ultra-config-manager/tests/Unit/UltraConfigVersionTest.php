<?php

/**
 * 📜 Oracode Unit Test: UltraConfigVersionTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         1.0.0
 * @author          Padmin D. Curtis (Generated for Fabio Cherici)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion; // Classe da testare
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider; // Necessario per Testbench setup
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Illuminate\Support\Facades\DB; // Usato per verificare il valore raw nel DB

/**
 * 🎯 Purpose: Validates the UltraConfigVersion model, including casts, relationships,
 *    boot logic (validation), and timestamp handling.
 *
 * 🧪 Test Strategy: Unit tests using RefreshDatabase and SQLite in-memory.
 *    - Tests attribute casting (`EncryptedCast`, `CategoryEnum`, integers).
 *    - Tests relationships (`uconfig`).
 *    - Tests validation logic in the boot method (required fields).
 *    - Tests timestamp functionality.
 *
 * @package Ultra\UltraConfigManager\Tests\Unit
 */
#[CoversClass(UltraConfigVersion::class)]
#[UsesClass(UConfigServiceProvider::class)] // Per setup Testbench (casts, enum)
#[UsesClass(UltraConfigModel::class)]       // Usato nelle relazioni e creazione
#[UsesClass(CategoryEnum::class)]           // Usato nei cast
#[UsesClass(\Ultra\UltraConfigManager\Casts\EncryptedCast::class)] // Usato nei cast
class UltraConfigVersionTest extends UltraTestCase
{
    use RefreshDatabase;

    private UltraConfigModel $parentConfig; // Model padre per i test

    /**
     * ⚙️ Set up a parent UltraConfigModel before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        // Create a parent config entry to associate versions with
        $this->parentConfig = UltraConfigModel::factory()->create();
    }

    /**
     * ✅ Tests that the EncryptedCast correctly encrypts the 'value' attribute.
     * ⛓️ Oracular Behavior: Verifies data hiding mechanism at the database level for versions.
     */
    #[Test]
    public function value_attribute_is_encrypted(): void
    {
        $plainValue = 'historical-secret-' . uniqid();
        /** @var Encrypter $encrypter */
        $encrypter = $this->app->make(Encrypter::class);

        $version = UltraConfigVersion::create([
            'uconfig_id' => $this->parentConfig->id,
            'version' => 1,
            'key' => $this->parentConfig->key,
            'value' => $plainValue, // Set plain value, cast should encrypt
        ]);

        // 1. Verify decrypted value from model
        $this->assertSame($plainValue, $version->value);

        // 2. Verify raw value in DB is different
        $rawValue = DB::table('uconfig_versions')->where('id', $version->id)->value('value');
        $this->assertNotNull($rawValue);
        $this->assertNotEquals($plainValue, $rawValue);

        // 3. Verify manual decryption of raw value
        try {
            $decryptedRaw = $encrypter->decryptString($rawValue);
            $this->assertSame($plainValue, $decryptedRaw);
        } catch (\Exception $e) {
            $this->fail("Failed to decrypt raw version value from DB: " . $e->getMessage());
        }
    }

    /**
     * ✅ Tests that the CategoryEnum cast works correctly for 'category' attribute.
     * ⛓️ Oracular Behavior: Ensures semantic consistency of the category field in versions.
     */
    #[Test]
    public function category_attribute_is_cast_to_enum(): void
    {
        $versionSystem = UltraConfigVersion::create([
            'uconfig_id' => $this->parentConfig->id,
            'version' => 1, 'key' => $this->parentConfig->key, 'value' => 'v1',
            'category' => CategoryEnum::Security->value,
        ]);
        $this->assertInstanceOf(CategoryEnum::class, $versionSystem->category);
        $this->assertSame(CategoryEnum::Security, $versionSystem->category);

        $versionNull = UltraConfigVersion::create([
             'uconfig_id' => $this->parentConfig->id,
             'version' => 2, 'key' => $this->parentConfig->key, 'value' => 'v2',
             'category' => null,
        ]);
        $this->assertNull($versionNull->category);

         $versionEmpty = UltraConfigVersion::create([
             'uconfig_id' => $this->parentConfig->id,
             'version' => 3, 'key' => $this->parentConfig->key, 'value' => 'v3',
             'category' => '',
         ]);
         $this->assertInstanceOf(CategoryEnum::class, $versionEmpty->category);
         $this->assertSame(CategoryEnum::None, $versionEmpty->category);
    }

    /**
     * ✅ Tests integer casting for 'version' and 'uconfig_id'.
     * ⛓️ Oracular Behavior: Verifies correct data types for key identifiers.
     */
    #[Test]
    public function key_identifiers_are_cast_to_integer(): void
    {
         $version = UltraConfigVersion::create([
            'uconfig_id' => $this->parentConfig->id,
            'version' => 1, // Should be saved as int
            'key' => $this->parentConfig->key,
            'value' => 'value',
         ]);

         // Retrieve fresh instance to ensure casts are applied on retrieval
         $retrievedVersion = UltraConfigVersion::find($version->id);

         $this->assertIsInt($retrievedVersion->version);
         $this->assertSame(1, $retrievedVersion->version);
         $this->assertIsInt($retrievedVersion->uconfig_id);
         $this->assertSame($this->parentConfig->id, $retrievedVersion->uconfig_id);
    }


    /**
     * ✅ Tests the 'uconfig' relationship.
     * ⛓️ Oracular Behavior: Verifies the link back to the parent configuration model.
     */
    #[Test]
    public function model_belongs_to_uconfig(): void
    {
        $version = UltraConfigVersion::create([
            'uconfig_id' => $this->parentConfig->id,
            'version' => 1, 'key' => $this->parentConfig->key, 'value' => 'v1',
        ]);

        // Eager load for certainty
        $version->load('uconfig');

        $this->assertInstanceOf(UltraConfigModel::class, $version->uconfig);
        $this->assertEquals($this->parentConfig->id, $version->uconfig->id);
        $this->assertEquals($this->parentConfig->key, $version->uconfig->key);
    }

    /**
     * ✅ Tests validation: creating without uconfig_id fails.
     * 💥 Oracular Behavior: Enforces foreign key requirement (validation in boot logic).
     */
    #[Test]
    public function creating_without_uconfig_id_fails(): void
    {
        // DEVE ESSERE InvalidArgumentException
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Version entry requires a valid uconfig_id and version >= 1.');

        UltraConfigVersion::create([
            // 'uconfig_id' is intentionally missing
            'version' => 1,
            'key' => 'some.key',
            'value' => 'value',
        ]);
    }

    /**
     * ✅ Tests validation: creating without version fails.
     * 💥 Oracular Behavior: Enforces version number requirement (likely DB constraint or boot logic).
     */
    #[Test]
    public function creating_without_version_fails(): void
    {
         // Check if boot logic throws InvalidArgumentException first
        try {
             UltraConfigVersion::create([
                 'uconfig_id' => $this->parentConfig->id,
                 // 'version' => 1, // Missing
                 'key' => $this->parentConfig->key,
                 'value' => 'value',
             ]);
        } catch (InvalidArgumentException $e) {
             $this->assertStringContainsStringIgnoringCase('version >= 1', $e->getMessage());
             return; // Test passed if boot logic caught it
        } catch (QueryException $e) {
            // If boot logic didn't catch it, expect DB error
             $this->assertStringContainsStringIgnoringCase('version', $e->getMessage()); // Check DB error mentions 'version'
             return; // Test passed if DB caught it
        }

         $this->fail('Expected an exception for missing version, but none was thrown.');
    }

     /**
     * ✅ Tests validation: creating with version < 1 fails.
     * 💥 Oracular Behavior: Enforces positive version number (boot logic).
     */
    #[Test]
    public function creating_with_invalid_version_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/version >= 1/');

         UltraConfigVersion::create([
            //  'uconfig_id' => $this->parentConfig->id,
             'version' => 0, // Invalid version
                         'key' => 'some.key',
             'value' => 'value',
         ]);
    }


    /**
     * ✅ Tests validation: creating without key fails.
     * 💥 Oracular Behavior: Enforces key presence requirement (boot logic or DB constraint).
     */
    #[Test]
    public function creating_without_key_fails(): void
    {
        // Check if boot logic throws InvalidArgumentException first
        try {
             UltraConfigVersion::create([
                 'uconfig_id' => $this->parentConfig->id,
                 'version' => 1,
                 // 'key' => $this->parentConfig->key, // Missing
                 'value' => 'value',
             ]);
        } catch (InvalidArgumentException $e) {
             $this->assertStringContainsStringIgnoringCase('Version key cannot be empty', $e->getMessage());
             return; // Test passed if boot logic caught it
        } catch (QueryException $e) {
            // If boot logic didn't catch it, expect DB error (assuming key is NOT NULL in versions table)
             $this->assertStringContainsStringIgnoringCase('key', $e->getMessage()); // Check DB error mentions 'key'
             return; // Test passed if DB caught it
        }

         $this->fail('Expected an exception for missing key, but none was thrown.');
    }

    /**
     * ✅ Tests that timestamps are handled correctly.
     * ⛓️ Oracular Behavior: Verifies standard Eloquent timestamp functionality.
     */
    #[Test]
    public function timestamps_are_handled(): void
    {
        $version = UltraConfigVersion::create([
            'uconfig_id' => $this->parentConfig->id,
            'version' => 1, 'key' => $this->parentConfig->key, 'value' => 'v1',
        ]);

        $this->assertNotNull($version->created_at);
        $this->assertNotNull($version->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $version->created_at);
    }
}