<?php

/**
 * 📜 Oracode Unit Test: GlobalConstantsTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         1.1.3 // Version bump for UsesClass attribute fix
 * @author          Padmin D. Curtis (Revision)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit;

use InvalidArgumentException;
use Ultra\UltraConfigManager\Constants\GlobalConstants; // Classe da testare
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider; // Classe usata dal test (via Testbench)
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;   // <-- CORREZIONE: Importa UsesClass

/**
 * 🎯 Purpose: Validates the GlobalConstants class, ensuring constant values are accessible
 *    and helper methods (`getConstant`, `validateConstant`) work correctly.
 *
 * 🧪 Test Strategy: Unit tests covering:
 *    - Correct values for defined constants (`NO_USER`, `DEFAULT_CATEGORY`).
 *    - `getConstant` retrieval for existing and non-existing constants.
 *    - `validateConstant` correctly identifies valid constants and throws for invalid ones.
 *
 * @package Ultra\UltraConfigManager\Tests\Unit
 */
#[CoversClass(GlobalConstants::class)]
#[UsesClass(UConfigServiceProvider::class)] // <-- CORREZIONE: Dichiara la classe USATA indirettamente
class GlobalConstantsTest extends UltraTestCase
{
    /**
     * ✅ Verifies the value of the NO_USER constant.
     * ⛓️ Oracular Behavior: Checks the defined value for a core constant.
     */
    #[Test]
    public function no_user_constant_has_correct_value(): void
    {
        $this->assertSame(0, GlobalConstants::NO_USER);
    }

    /**
     * ✅ Verifies the value of the DEFAULT_CATEGORY constant.
     * ⛓️ Oracular Behavior: Checks the defined value for another core constant.
     */
    #[Test]
    public function default_category_constant_has_correct_value(): void
    {
        $this->assertSame('general', GlobalConstants::DEFAULT_CATEGORY);
    }

    /**
     * ✅ Verifies `getConstant()` when the constant exists.
     * ⛓️ Oracular Behavior: Tests safe retrieval of an existing constant value.
     */
    #[Test]
    public function getConstant_returns_value_if_exists(): void
    {
        $result = GlobalConstants::getConstant('NO_USER', 99);
        $this->assertSame(GlobalConstants::NO_USER, $result);

        $resultCat = GlobalConstants::getConstant('DEFAULT_CATEGORY', 'fallback');
        $this->assertSame(GlobalConstants::DEFAULT_CATEGORY, $resultCat);
    }

    /**
     * ✅ Verifies `getConstant()` when the constant does not exist.
     * ⛓️ Oracular Behavior: Tests fallback mechanism for non-existent constants.
     */
    #[Test]
    public function getConstant_returns_default_if_not_exists(): void
    {
        $defaultValue = 'default-value-' . uniqid();
        $result = GlobalConstants::getConstant('NON_EXISTENT_CONSTANT_XYZ', $defaultValue);
        $this->assertSame($defaultValue, $result);
    }

    /**
     * ✅ Verifies `validateConstant()` when the constant does not exist.
     * 💥 Oracular Behavior: Ensures invalid input correctly triggers an exception.
     */
    #[Test]
    public function validateConstant_throws_exception_if_invalid(): void
    {
        $invalidName = 'INVALID_CONSTANT_NAME_123';
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches("/Constant '{$invalidName}' does not exist/");

        GlobalConstants::validateConstant($invalidName);
    }

    /**
     * ✅ Verifies `validateConstant()` when the constant exists.
     * ⛓️ Oracular Behavior: Ensures valid input does not trigger an exception.
     */
    #[Test]
    public function validateConstant_does_not_throw_if_valid(): void
    {
        try {
            GlobalConstants::validateConstant('NO_USER');
            GlobalConstants::validateConstant('DEFAULT_CATEGORY');
            $this->assertTrue(true, 'validateConstant should not throw an exception for valid constants.');
        } catch (InvalidArgumentException $e) {
            $this->fail("validateConstant threw an unexpected exception for a valid constant: " . $e->getMessage());
        }
    }
}