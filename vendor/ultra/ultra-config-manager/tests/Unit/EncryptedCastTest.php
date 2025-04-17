<?php

/**
 * 📜 Oracode Unit Test: EncryptedCastTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         1.0.2 // Version bump for UsesClass fix
 * @author          Padmin D. Curtis (Generated for Fabio Cherici)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Mockery\MockInterface;
use Psr\Log\LoggerInterface;
use Ultra\UltraConfigManager\Casts\EncryptedCast; // Classe da testare
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider; // Classe usata dal test (via Testbench)
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass; // <-- CORREZIONE: Importa UsesClass

/**
 * 🎯 Purpose: Validates the EncryptedCast class, ensuring it correctly interacts
 *    with Laravel's encryption service for attribute encryption and decryption.
 *
 * 🧪 Test Strategy: Unit tests using Mockery to mock the Encrypter contract.
 *    Verifies that:
 *    - `set()` calls `Encrypter::encryptString()` with the correct value and returns the encrypted result.
 *    - `get()` calls `Encrypter::decryptString()` with the correct value and returns the decrypted result.
 *    - Null values are handled correctly in both `get()` and `set()`.
 *    - `DecryptException` during `get()` is caught, logged (mocked), and the original encrypted value is returned.
 *
 * @package Ultra\UltraConfigManager\Tests\Unit
 */
#[CoversClass(EncryptedCast::class)]
#[UsesClass(UConfigServiceProvider::class)] // <-- CORREZIONE: Dichiara la classe USATA indirettamente dal setup
// Non aggiungiamo UsesClass per Encrypter/LoggerInterface perché sono interfacce vendor
class EncryptedCastTest extends UltraTestCase
{
    /** @var EncryptedCast Instance of the class under test. */
    private EncryptedCast $cast;

    /** @var MockInterface&Encrypter Mock object for Laravel's Encrypter. */
    private $encrypterMock;

    /** @var MockInterface&LoggerInterface Mock object for PSR-3 Logger. */
    private $loggerMock;

    /**
     * ⚙️ Set up the test environment before each test.
     * Creates the EncryptedCast instance and mocks dependencies.
     */
    protected function setUp(): void
    {
        parent::setUp(); // Important when extending TestCase

        $this->cast = new EncryptedCast();

        // Mock Encrypter using Mockery integrated with Laravel container
        $this->encrypterMock = Mockery::mock(Encrypter::class);
        $this->app->instance(Encrypter::class, $this->encrypterMock); // Bind mock instance to container

        // Mock LoggerInterface
        $this->loggerMock = Mockery::mock(LoggerInterface::class);
        $this->app->instance(LoggerInterface::class, $this->loggerMock); // Bind mock instance
    }

    /**
     * 🧹 Clean up the test environment after each test.
     * Closes Mockery expectations.
     */
    protected function tearDown(): void
    {
        Mockery::close(); // Necessary for Mockery verification
        parent::tearDown();
    }

    /**
     * ✅ Tests the `set` method for encrypting a non-null value.
     * ⛓️ Oracular Behavior: Verifies interaction with the encryption service for data hiding.
     */
    #[Test]
    public function set_encrypts_value_using_encrypter(): void
    {
        $model = new class extends Model {}; // Anonymous model instance
        $key = 'sensitive_data';
        $originalValue = 'my-secret-value';
        $encryptedValue = 'encrypted::' . $originalValue; // Simple simulation

        // Expect the encrypter's encryptString method to be called once with the original value
        $this->encrypterMock
            ->shouldReceive('encryptString')
            ->once()
            ->with($originalValue)
            ->andReturn($encryptedValue); // Return a simulated encrypted value

        // Call the set method on the cast
        $result = $this->cast->set($model, $key, $originalValue, []);

        // Assert that the result returned by set() is the simulated encrypted value
        $this->assertSame($encryptedValue, $result);
    }

    /**
     * ✅ Tests the `set` method handles null correctly.
     * ⛓️ Oracular Behavior: Verifies null propagation without encryption attempts.
     */
    #[Test]
    public function set_returns_null_when_value_is_null(): void
    {
        $model = new class extends Model {};
        $key = 'optional_data';

        // Expect the encrypter's encryptString method NOT to be called
        $this->encrypterMock->shouldNotReceive('encryptString');

        // Call the set method with null
        $result = $this->cast->set($model, $key, null, []);

        // Assert that the result is null
        $this->assertNull($result);
    }

    /**
     * ✅ Tests the `get` method for decrypting a non-null value.
     * ⛓️ Oracular Behavior: Verifies interaction with the encryption service for data reveal.
     */
    #[Test]
    public function get_decrypts_value_using_encrypter(): void
    {
        $model = new class extends Model { public function getKey() { return 123; } }; // Mock getKey for logging context
        $key = 'sensitive_data';
        $decryptedValue = 'my-secret-value';
        $encryptedValue = 'encrypted::' . $decryptedValue; // Simple simulation

        // Expect the encrypter's decryptString method to be called once with the encrypted value
        $this->encrypterMock
            ->shouldReceive('decryptString')
            ->once()
            ->with($encryptedValue)
            ->andReturn($decryptedValue); // Return the simulated decrypted value

        // Call the get method on the cast
        $result = $this->cast->get($model, $key, $encryptedValue, []);

        // Assert that the result returned by get() is the simulated decrypted value
        $this->assertSame($decryptedValue, $result);
    }

    /**
     * ✅ Tests the `get` method handles null correctly.
     * ⛓️ Oracular Behavior: Verifies null propagation without decryption attempts.
     */
    #[Test]
    public function get_returns_null_when_value_is_null(): void
    {
        $model = new class extends Model {};
        $key = 'optional_data';

        // Expect the encrypter's decryptString method NOT to be called
        $this->encrypterMock->shouldNotReceive('decryptString');

        // Call the get method with null
        $result = $this->cast->get($model, $key, null, []);

        // Assert that the result is null
        $this->assertNull($result);
    }

    /**
     * ✅ Tests the `get` method's handling of DecryptException.
     * 💥 Oracular Behavior: Verifies graceful failure on decryption error, including logging and returning original value.
     */
    #[Test]
    public function get_handles_decrypt_exception_and_logs_error(): void
    {
        $model = new class extends Model { public function getKey() { return 456; } }; // Mock getKey
        $key = 'corrupted_data';
        $corruptedEncryptedValue = 'invalid-encrypted-string';
        $decryptException = new DecryptException("Decryption failed.");

        // Expect decryptString to be called and throw DecryptException
        $this->encrypterMock
            ->shouldReceive('decryptString')
            ->once()
            ->with($corruptedEncryptedValue)
            ->andThrow($decryptException);

        // Expect the logger's error method to be called once
        $this->loggerMock
            ->shouldReceive('error')
            ->once()
            ->with(
                'EncryptedCast: Failed to decrypt attribute.', // Expected message
                Mockery::on(function ($context) use ($key, $model, $decryptException) { // Assert context array
                    return is_array($context) &&
                           $context['key'] === $key &&
                           $context['model'] === get_class($model) &&
                           $context['model_id'] === $model->getKey() &&
                           $context['error'] === $decryptException->getMessage();
                })
            );

        // Call the get method
        $result = $this->cast->get($model, $key, $corruptedEncryptedValue, []);

        // Assert that the result is the original corrupted value (as per current implementation)
        $this->assertSame($corruptedEncryptedValue, $result);
    }
}