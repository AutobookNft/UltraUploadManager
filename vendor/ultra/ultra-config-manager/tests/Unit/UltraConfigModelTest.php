<?php

/**
 * 📜 Oracode Unit Test: UltraConfigModelTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         1.0.0
 * @author          Padmin D. Curtis (Generated for Fabio Cherici)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit;

use Illuminate\Contracts\Encryption\Encrypter; // Per type hint nel test di criptazione
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase; // Per pulire il DB
use InvalidArgumentException;
use LogicException;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Models\UltraConfigAudit;
use Ultra\UltraConfigManager\Models\UltraConfigModel; // Classe da testare
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider; // Necessario per Testbench setup
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use Illuminate\Support\Facades\DB; // Usato per verificare il valore raw nel DB

/**
 * 🎯 Purpose: Validates the UltraConfigModel class, including its casts, relationships,
 *    boot logic (key validation, immutability), soft deletes, and factory integration.
 *
 * 🧪 Test Strategy: Unit tests using RefreshDatabase and SQLite in-memory.
 *    - Tests attribute casting (`EncryptedCast`, `CategoryEnum`).
 *    - Tests relationships (`versions`, `audits`).
 *    - Tests key immutability logic (preventing key change after creation).
 *    - Tests key format validation during creation/setting.
 *    - Tests soft delete and restore functionality.
 *    - Tests model factory functionality.
 *
 * @package Ultra\UltraConfigManager\Tests\Unit
 */
#[CoversClass(UltraConfigModel::class)]
#[UsesClass(UConfigServiceProvider::class)] // Per setup Testbench (casts, enum)
#[UsesClass(UltraConfigVersion::class)]     // Usato nelle relazioni
#[UsesClass(UltraConfigAudit::class)]       // Usato nelle relazioni
#[UsesClass(CategoryEnum::class)]           // Usato nei cast
#[UsesClass(\Ultra\UltraConfigManager\Casts\EncryptedCast::class)] // Usato nei cast
class UltraConfigModelTest extends UltraTestCase
{
    use RefreshDatabase; // Pulisce e riesegue le migrazioni per ogni test

    /**
     * ✅ Tests that the EncryptedCast correctly encrypts the 'value' attribute.
     * ⛓️ Oracular Behavior: Verifies data hiding mechanism at the database level.
     */
    #[Test]
    public function value_attribute_is_encrypted(): void
    {
        $plainValue = 'my-secret-data-' . uniqid();
        /** @var Encrypter $encrypter */
        $encrypter = $this->app->make(Encrypter::class); // Get the actual encrypter instance

        // Create the model using the factory or directly
        $config = UltraConfigModel::factory()->create([
            'value' => $plainValue,
        ]);

        // 1. Verify that the value retrieved via the model is the original (decrypted) one
        $this->assertSame($plainValue, $config->value);

        // 2. Verify that the value in the database is NOT the plain text value
        $rawValue = DB::table('uconfig')->where('id', $config->id)->value('value');
        $this->assertNotNull($rawValue, "Raw value in DB should not be null.");
        $this->assertNotEquals($plainValue, $rawValue, "Raw value in DB should be encrypted.");

        // 3. (Optional but robust) Verify that the raw value from DB can be manually decrypted
        try {
            $decryptedRaw = $encrypter->decryptString($rawValue);
            $this->assertSame($plainValue, $decryptedRaw, "Raw value from DB should decrypt to original value.");
        } catch (\Exception $e) {
            $this->fail("Failed to decrypt raw value from DB: " . $e->getMessage());
        }
    }

    /**
     * ✅ Tests that the CategoryEnum cast works correctly for 'category' attribute.
     * ⛓️ Oracular Behavior: Ensures semantic consistency of the category field.
     */
    #[Test]
    public function category_attribute_is_cast_to_enum(): void
    {
        // Test with a valid category
        $configSystem = UltraConfigModel::factory()->create([
            'category' => CategoryEnum::System->value, // Save using the string value
        ]);
        $this->assertInstanceOf(CategoryEnum::class, $configSystem->category);
        $this->assertSame(CategoryEnum::System, $configSystem->category);

        // Test with null
        $configNull = UltraConfigModel::factory()->create([
            'category' => null,
        ]);
        $this->assertSame(CategoryEnum::None, $configNull->category);

        // Test with empty category (which maps to CategoryEnum::None)
        $configEmpty = UltraConfigModel::factory()->create([
             'category' => '',
        ]);
        // Should return the None instance if None enum has value = ''
         $this->assertInstanceOf(CategoryEnum::class, $configEmpty->category);
         $this->assertSame(CategoryEnum::None, $configEmpty->category);
    }

    /**
     * ✅ Tests the 'versions' relationship.
     * ⛓️ Oracular Behavior: Verifies the link to historical version data.
     */
    #[Test]
    public function model_has_many_versions(): void
    {
        $config = UltraConfigModel::factory()->create();
        // Manually create some associated versions (factory doesn't easily handle complex relations)
        UltraConfigVersion::create([
            'uconfig_id' => $config->id, 'version' => 1, 'key' => $config->key, 'value' => 'v1',
        ]);
        UltraConfigVersion::create([
            'uconfig_id' => $config->id, 'version' => 2, 'key' => $config->key, 'value' => 'v2',
        ]);

        // Eager load the relationship before assertion (good practice)
        $config->load('versions');

        $this->assertCount(2, $config->versions);
        $this->assertInstanceOf(UltraConfigVersion::class, $config->versions->first());
        $this->assertEquals(2, $config->versions->get(1)->version); // Index 1 is the second one created
    }

    /**
     * ✅ Tests the 'audits' relationship.
     * ⛓️ Oracular Behavior: Verifies the link to audit trail data.
     */
    #[Test]
    public function model_has_many_audits(): void
    {
        $config = UltraConfigModel::factory()->create();
        $userId = 0; // GlobalConstants::NO_USER

        UltraConfigAudit::create([
            'uconfig_id' => $config->id, 'action' => 'created', 'new_value' => $config->value, 'user_id' => $userId,
        ]);
        UltraConfigAudit::create([
            'uconfig_id' => $config->id, 'action' => 'updated', 'old_value' => $config->value, 'new_value' => 'new', 'user_id' => $userId,
        ]);

        $this->assertCount(2, $config->audits);
        $this->assertInstanceOf(UltraConfigAudit::class, $config->audits->first());
        $this->assertEquals('updated', $config->audits->get(1)->action);
    }

    /**
     * ✅ Tests that the 'key' attribute cannot be modified after creation.
     * 💥 Oracular Behavior: Enforces immutability rule defined in boot logic.
     */
    #[Test]
    public function key_cannot_be_modified_after_creation(): void
    {
        $config = UltraConfigModel::factory()->create(['key' => 'original.key']);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches("/cannot be modified after creation/");

        $config->key = 'new.key';
        $config->save();
    }

    /**
    * ✅ Tests that creating a model without a key throws an exception.
    * 💥 Oracular Behavior: Enforces key presence requirement during creation (boot logic).
    */
   #[Test]
   public function creating_without_key_throws_exception(): void
   {
       // Expect the InvalidArgumentException thrown by the 'creating' boot listener
       $this->expectException(InvalidArgumentException::class);
       $this->expectExceptionMessage('Configuration key cannot be empty during creation.');

       // Attempt to save the model without setting the key
       $model = new UltraConfigModel();
       $model->value = 'some value';
       // Key is intentionally not set
       $model->save(); // This should trigger the 'creating' listener and throw
   }


    /**
     * ✅ Tests that setting an invalid key format throws an exception.
     * 💥 Oracular Behavior: Enforces key format validation (mutator logic).
     */
    #[Test]
    public function setting_invalid_key_format_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/must be alphanumeric/");

        // Prova a creare tramite factory ma con chiave sovrascritta non valida
        UltraConfigModel::factory()->create(['key' => 'invalid key with spaces']);
    }

    /**
     * ✅ Tests soft delete functionality.
     * ⛓️ Oracular Behavior: Verifies correct soft delete behavior.
     */
    #[Test]
    public function model_can_be_soft_deleted(): void
    {
        $config = UltraConfigModel::factory()->create();
        $configId = $config->id;

        $config->delete();

        $this->assertSoftDeleted('uconfig', ['id' => $configId]);
        $this->assertNull(UltraConfigModel::find($configId), "Model should not be found without trashed().");
        $this->assertNotNull(UltraConfigModel::withTrashed()->find($configId), "Model should be found with trashed().");
    }

    /**
     * ✅ Tests restore functionality after soft delete.
     * ⛓️ Oracular Behavior: Verifies correct restore behavior.
     */
    #[Test]
    public function model_can_be_restored(): void
    {
        $config = UltraConfigModel::factory()->create();
        $configId = $config->id;

        $config->delete();
        $this->assertSoftDeleted('uconfig', ['id' => $configId]);

        $trashedConfig = UltraConfigModel::withTrashed()->find($configId);
        $trashedConfig->restore();

        $this->assertNotSoftDeleted('uconfig', ['id' => $configId]);
        $this->assertNotNull(UltraConfigModel::find($configId), "Model should be found after restore.");
    }

    /**
     * ✅ Tests that the model factory works correctly.
     * ⛓️ Oracular Behavior: Verifies the associated factory creates valid model instances.
     */
    #[Test]
    public function factory_creates_valid_model(): void
    {
        $config = UltraConfigModel::factory()->create();

        $this->assertInstanceOf(UltraConfigModel::class, $config);
        $this->assertDatabaseHas('uconfig', ['id' => $config->id]);
        $this->assertNotNull($config->key);
        $this->assertNotNull($config->value); // La factory dovrebbe generare un valore
    }
}