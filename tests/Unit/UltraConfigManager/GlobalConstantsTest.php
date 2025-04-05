<?php

namespace Tests\Unit\UltraConfigManager;

use Tests\UltraTestCase;
use Ultra\UltraConfigManager\Constants\GlobalConstants;


class GlobalConstantsTest extends UltraTestCase
{
    public function test_get_constant_returns_value_if_exists()
    {
        $result = GlobalConstants::getConstant('NO_USER', 99);
        $this->assertEquals(0, $result);
    }

    public function test_get_constant_returns_default_if_not_exists()
    {
        $result = GlobalConstants::getConstant('UNKNOWN_CONSTANT', 42);
        $this->assertEquals(42, $result);
    }

    public function test_validate_constant_throws_exception_if_invalid()
    {
        $this->expectException(\InvalidArgumentException::class);

        GlobalConstants::validateConstant('NON_EXISTENT_CONSTANT');
    }

    public function test_validate_constant_does_not_throw_if_valid()
    {
        $this->expectNotToPerformAssertions();

        GlobalConstants::validateConstant('NO_USER');
    }
}
