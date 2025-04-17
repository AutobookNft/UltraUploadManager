<?php

/**
 * 📜 Oracode Unit Test: UltraConfigAuditTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         1.0.1 // Version bump for nullable property fix
 * @author          Padmin D. Curtis (Generated for Fabio Cherici)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Ultra\UltraConfigManager\Models\UltraConfigAudit; // Classe da testare
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\User; // Modello User per relazione
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider; // Necessario per Testbench setup
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Support\Facades\DB; // Usato per verificare il valore raw nel DB

/**
 * 🎯 Purpose: Validates the UltraConfigAudit model, including casts, relationships,
 *    boot logic (action validation), and timestamp handling.
 *
 * 🧪 Test Strategy: Unit tests using RefreshDatabase and SQLite in-memory.
 *    - Tests attribute casting (`EncryptedCast`, integers).
 *    - Tests relationships (`uconfig`, `user` including default).
 *    - Tests validation logic in the boot method (valid 'action' values).
 *    - Tests timestamp functionality.
 *
 * @package Ultra\UltraConfigManager\Tests\Unit
 */
#[CoversClass(UltraConfigAudit::class)]
#[UsesClass(UConfigServiceProvider::class)]
#[UsesClass(UltraConfigModel::class)]
#[UsesClass(User::class)]
#[UsesClass(\Ultra\UltraConfigManager\Casts\EncryptedCast::class)]
class UltraConfigAuditTest extends UltraTestCase
{
    use RefreshDatabase;

    private UltraConfigModel $parentConfig;
    private ?User $testUser; // <-- CORREZIONE: Aggiunto '?' per renderla nullable

    /**
     * ⚙️ Set up parent models before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->parentConfig = UltraConfigModel::factory()->create();

        // Try to create user, assign null if table doesn't exist
        try {
             // Assumiamo che Testbench fornisca una migrazione users base
             $this->testUser = User::create([
                 'name' => 'Test User',
                 'email' => 'test@example.com',
                 'password' => bcrypt('password') // Usa bcrypt o Hash::make()
             ]);
        } catch(QueryException $e) {
             // Fallback: assegna null (ora permesso dalla proprietà nullable)
             $this->testUser = null;
             echo "\nWarning: 'users' table not found or User model issue during setup. User relationship tests might be affected.\n";
        }
    }

    /**
     * ✅ Tests that EncryptedCast works for 'old_value' and 'new_value'.
     * ⛓️ Oracular Behavior: Verifies data hiding for historical values in audit logs.
     */
    #[Test]
    public function value_attributes_are_encrypted(): void
    {
        $oldPlain = 'old-value-' . uniqid();
        $newPlain = 'new-value-' . uniqid();
        /** @var Encrypter $encrypter */
        $encrypter = $this->app->make(Encrypter::class);

        $audit = UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => 'updated',
            'old_value' => $oldPlain,
            'new_value' => $newPlain,
            'user_id' => $this->testUser?->id, // Usa safe navigation '?'
        ]);

        // 1. Verify decrypted values from model
        $this->assertSame($oldPlain, $audit->old_value);
        $this->assertSame($newPlain, $audit->new_value);

        // 2. Verify raw values in DB are different
        $rawValues = DB::table('uconfig_audit')->where('id', $audit->id)->first(['old_value', 'new_value']);
        $this->assertNotNull($rawValues);
        $this->assertNotEquals($oldPlain, $rawValues->old_value);
        $this->assertNotEquals($newPlain, $rawValues->new_value);

        // 3. Verify manual decryption
        try {
            $decryptedOldRaw = $encrypter->decryptString($rawValues->old_value);
            $decryptedNewRaw = $encrypter->decryptString($rawValues->new_value);
            $this->assertSame($oldPlain, $decryptedOldRaw);
            $this->assertSame($newPlain, $decryptedNewRaw);
        } catch (\Exception $e) {
            $this->fail("Failed to decrypt raw audit values from DB: " . $e->getMessage());
        }
    }

    /**
     * ✅ Tests integer casting for ID fields.
     * ⛓️ Oracular Behavior: Verifies correct data types for identifiers.
     */
    #[Test]
    public function id_fields_are_cast_to_integer(): void
    {
        $audit = UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => 'created',
            'new_value' => 'created value',
            'user_id' => $this->testUser?->id, // Usa safe navigation '?'
        ]);

        $retrievedAudit = UltraConfigAudit::find($audit->id);

        $this->assertIsInt($retrievedAudit->uconfig_id);
        $this->assertSame($this->parentConfig->id, $retrievedAudit->uconfig_id);

        if ($this->testUser) {
            $this->assertIsInt($retrievedAudit->user_id);
            $this->assertSame($this->testUser->id, $retrievedAudit->user_id);
        } else {
             $this->assertNull($retrievedAudit->user_id);
        }
    }


    /**
     * ✅ Tests the 'uconfig' relationship.
     * ⛓️ Oracular Behavior: Verifies the link back to the parent configuration model.
     */
    #[Test]
    public function model_belongs_to_uconfig(): void
    {
        $audit = UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => 'created', 'new_value' => 'v1', 'user_id' => $this->testUser?->id, // Safe nav
        ]);

        $audit->load('uconfig'); // Eager load

        $this->assertInstanceOf(UltraConfigModel::class, $audit->uconfig);
        $this->assertEquals($this->parentConfig->id, $audit->uconfig->id);
    }

    /**
     * ✅ Tests the 'user' relationship with a valid user.
     * ⛓️ Oracular Behavior: Verifies the link to the user who performed the action.
     */
    #[Test]
    public function model_belongs_to_user(): void
    {
        if (!$this->testUser) {
             $this->markTestSkipped("User model/table not available for testing relationship.");
        }

        $audit = UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => 'updated', 'old_value' => 'v1', 'new_value' => 'v2',
            'user_id' => $this->testUser->id, // Qui sappiamo che non è null
        ]);

        $audit->load('user'); // Eager load

        $this->assertInstanceOf(User::class, $audit->user);
        $this->assertEquals($this->testUser->id, $audit->user->id);
        $this->assertEquals('Test User', $audit->user->name);
    }

    /**
     * ✅ Tests the 'user' relationship when user_id is null.
     * ⛓️ Oracular Behavior: Verifies the withDefault behavior for unknown/system users.
     */
    #[Test]
    public function user_relationship_returns_default_when_user_id_is_null(): void
    {
        // Questo test funziona indipendentemente dal fatto che $this->testUser esista
        $audit = UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => 'deleted', 'old_value' => 'v2',
            'user_id' => null, // Explicitly null
        ]);

        $audit->load('user');

        $this->assertInstanceOf(User::class, $audit->user);
        $this->assertNull($audit->user->getKey(), "Default user should not have a primary key.");
        $this->assertEquals('Unknown/System', $audit->user->name);
    }

    /**
     * ✅ Tests validation: creating with an invalid action fails.
     * 💥 Oracular Behavior: Enforces the defined set of valid actions (boot logic).
     */
    #[Test]
    public function creating_with_invalid_action_fails(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Audit action must be one of:/');

        UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => 'modified', // Invalid action
            'old_value' => 'v1', 'new_value' => 'v2',
            'user_id' => $this->testUser?->id, // Safe nav
        ]);
    }

    /**
     * ✅ Tests creating with all valid actions succeeds.
     * ⛓️ Oracular Behavior: Verifies all expected actions are accepted.
     * @dataProvider provideValidActions // Lasciamo il DocBlock per retrocompatibilità/chiarezza
     */
    #[Test]
    #[DataProvider('provideValidActions')] // Data provider for valid actions
    public function creating_with_valid_action_succeeds(string $action): void
    {
        $audit = UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => $action, // Valid action from provider
            'old_value' => ($action === 'created' ? null : 'old'),
            'new_value' => ($action === 'deleted' ? null : 'new'),
            'user_id' => $this->testUser?->id, // Safe nav
        ]);

        $this->assertDatabaseHas('uconfig_audit', ['id' => $audit->id, 'action' => $action]);
        $this->assertSame($action, $audit->action);
    }

    /**
     * 🏭 Data Provider for valid actions.
     */
    public static function provideValidActions(): array
    {
        return [
            'action: created' => ['created'],  // Aggiungere chiavi rende l'output più leggibile
            'action: updated' => ['updated'],
            'action: deleted' => ['deleted'],
            'action: restored' => ['restored'],
        ];
    }
  

    /**
     * ✅ Tests that timestamps are handled correctly.
     * ⛓️ Oracular Behavior: Verifies standard Eloquent timestamp functionality.
     */
    #[Test]
    public function timestamps_are_handled(): void
    {
        $audit = UltraConfigAudit::create([
            'uconfig_id' => $this->parentConfig->id,
            'action' => 'created', 'new_value' => 'v1', 'user_id' => $this->testUser?->id, // Safe nav
        ]);

        $this->assertNotNull($audit->created_at);
        $this->assertNotNull($audit->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $audit->created_at);
    }
}