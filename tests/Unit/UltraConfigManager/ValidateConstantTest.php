<?php

namespace Tests\Unit\UltraConfigManager;

use Tests\UltraTestCase;
use Ultra\UltraConfigManager\Facades\UConfig;

class ValidateConstantTest extends UltraTestCase
{
    public function test_validate_constant_does_not_throw_if_valid()
    {
        $this->expectNotToPerformAssertions();

        UConfig::validateConstant('DEFAULT_CATEGORY');
    }

    public function test_validate_constant_throws_exception_if_invalid()
    {
        $this->expectException(\InvalidArgumentException::class);

        UConfig::validateConstant('NON_EXISTENT_CONSTANT');
    }
}
