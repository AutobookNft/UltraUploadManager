<?php

namespace Tests\Unit\UltraConfigManager;

use Tests\TestCase;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Ultra\UltraConfigManager\Services\VersionManager;

class VersionManagerTest extends TestCase
{
    public function test_getNextVersion_returns_incremented_value()
    {
        // 1. Crea una config
        $config = UltraConfigModel::create([
            'key' => 'test.key',
            'value' => 'test.value',
        ]);

        // 2. Crea una prima versione
        UltraConfigVersion::create([
            'uconfig_id' => $config->id,
            'version' => 1,
            'key' => $config->key,
            'value' => $config->value,
        ]);

        $manager = new VersionManager();

        // 3. Deve restituire la versione successiva
        $this->assertEquals(2, $manager->getNextVersion($config->id));
    }

    public function test_getNextVersion_returns_one_if_no_versions_exist()
    {
        $config = UltraConfigModel::create([
            'key' => 'new.key',
            'value' => 'new.value',
        ]);

        $manager = new VersionManager();

        $this->assertEquals(1, $manager->getNextVersion($config->id));
    }

    public function test_getNextVersion_throws_exception_for_invalid_id()
    {
        $manager = new VersionManager();

        $this->expectException(\InvalidArgumentException::class);
        $manager->getNextVersion(-1);
    }
}
