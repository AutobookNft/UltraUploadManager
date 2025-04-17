<?php

/**
 * 📜 Oracode Unit Test: CategoryEnumTest
 *
 * @package         Ultra\UltraConfigManager\Tests\Unit
 * @version         1.0.3
 * @author          Padmin D. Curtis (Generated for Fabio Cherici)
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Tests\Unit;

use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider;
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * 🎯 Purpose: Validates the core functionality and semantic correctness of the CategoryEnum.
 *    Ensures cases, values, translation keys, and helper methods behave as expected.
 *
 * 🧪 Test Strategy: Unit tests covering:
 *    - Correct enum cases and their string values.
 *    - `::tryFrom()` behavior for valid and invalid inputs.
 *    - `translationKey()` output format.
 *    - `translatedName()` output (requires translations loaded via Testbench setup by ServiceProvider).
 *    - `translatedOptions()` output (requires translations loaded via Testbench setup by ServiceProvider).
 *    - `validValues()` output (array of valid string values, excluding 'None').
 *
 * @package Ultra\UltraConfigManager\Tests\Unit
 */
#[CoversClass(CategoryEnum::class)]
#[UsesClass(UConfigServiceProvider::class)] // Declares intentional usage for setup
class CategoryEnumTest extends UltraTestCase
{
    /**
     * ✅ Verifies that all defined cases exist and have the correct string values.
     * ⛓️ Oracular Behavior: Checks the fundamental structure and backing values of the enum.
     */
    #[Test]
    public function all_cases_have_correct_values(): void
    {
        $expectedValues = [
            'System' => 'system',
            'Application' => 'application',
            'Security' => 'security',
            'Performance' => 'performance',
            'None' => '',
        ];

        $cases = CategoryEnum::cases();
        $this->assertCount(count($expectedValues), $cases, 'Incorrect number of enum cases.');

        foreach ($cases as $case) {
            $this->assertArrayHasKey($case->name, $expectedValues, "Unexpected enum case '{$case->name}'.");
            $this->assertSame($expectedValues[$case->name], $case->value, "Incorrect string value for case '{$case->name}'.");
        }
    }

    /**
     * ✅ Verifies the behavior of `::tryFrom()`.
     * ⛓️ Oracular Behavior: Ensures parsing from string values works correctly for valid and invalid inputs.
     */
    #[Test]
    #[DataProvider('provideCategoryStrings')]
    public function tryFrom_works_correctly(string $input, ?CategoryEnum $expected): void
    {
        $this->assertSame($expected, CategoryEnum::tryFrom($input));
    }

    /**
     * 🏭 Data Provider for `tryFrom_works_correctly`.
     */
    public static function provideCategoryStrings(): array
    {
        return [
            'system lower' => ['system', CategoryEnum::System],
            'application lower' => ['application', CategoryEnum::Application],
            'security lower' => ['security', CategoryEnum::Security],
            'performance lower' => ['performance', CategoryEnum::Performance],
            'none empty string' => ['', CategoryEnum::None],
            'invalid string' => ['invalid_category', null],
            'case sensitive wrong' => ['System', null],
        ];
    }

    /**
     * ✅ Verifies that `translationKey()` generates the correct key format.
     * ⛓️ Oracular Behavior: Ensures the link to the translation system is correctly formatted.
     */
    #[Test]
    public function translationKey_returns_correct_format(): void
    {
        $this->assertSame('uconfig::uconfig.categories.system', CategoryEnum::System->translationKey());
        $this->assertSame('uconfig::uconfig.categories.application', CategoryEnum::Application->translationKey());
        $this->assertSame('uconfig::uconfig.categories.security', CategoryEnum::Security->translationKey());
        $this->assertSame('uconfig::uconfig.categories.performance', CategoryEnum::Performance->translationKey());
        $this->assertSame('uconfig::uconfig.categories.none', CategoryEnum::None->translationKey());
    }

    /**
     * ✅ Verifies that `translatedName()` returns a string (presumably translated).
     * ⛓️ Oracular Behavior: Checks interaction with the translation system. Requires translations to be loaded.
     */
    #[Test]
    public function translatedName_returns_string(): void
    {
        $this->assertIsString(CategoryEnum::System->translatedName());
        $this->assertNotEmpty(CategoryEnum::System->translatedName());
        $this->assertEquals('None', CategoryEnum::None->translatedName()); // Based on en/uconfig.php
        $this->assertEquals('System', CategoryEnum::System->translatedName());
        $this->assertEquals('Application', CategoryEnum::Application->translatedName());
        $this->assertEquals('Security', CategoryEnum::Security->translatedName());
        $this->assertEquals('Performance', CategoryEnum::Performance->translatedName());
    }

    /**
     * ✅ Verifies that `translatedOptions()` returns the correct associative array.
     * ⛓️ Oracular Behavior: Checks the helper for generating UI options, excluding 'None'. Requires translations.
     */
    #[Test]
    public function translatedOptions_returns_correct_array(): void
    {
        $expectedOptions = [
            'system' => 'System',
            'application' => 'Application',
            'security' => 'Security',
            'performance' => 'Performance',
        ];

        $options = CategoryEnum::translatedOptions();

        $this->assertIsArray($options);
        $this->assertEquals($expectedOptions, $options);
        $this->assertArrayNotHasKey('', $options, "'None' should not be in translatable options.");
    }

     /**
     * ✅ Verifies that `validValues()` returns the correct array of string values.
     * ⛓️ Oracular Behavior: Ensures the helper for validation rules provides the correct set of values.
     */
    #[Test]
    public function validValues_returns_correct_string_values(): void
    {
        $expectedValues = [
            'system',
            'application',
            'security',
            'performance',
        ];

        $validValues = CategoryEnum::validValues();

        $this->assertIsArray($validValues);
        $this->assertCount(count($expectedValues), $validValues);
        foreach ($expectedValues as $expectedValue) {
            $this->assertContains($expectedValue, $validValues);
        }
        $this->assertNotContains('', $validValues, "'None' value should not be in validValues().");
    }
}