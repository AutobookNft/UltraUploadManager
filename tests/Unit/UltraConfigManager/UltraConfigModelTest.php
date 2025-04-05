<?php

namespace Tests\Unit\UltraConfigManager;

use Tests\UltraTestCase;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Models\UltraConfigVersion;
use Illuminate\Support\Facades\DB;


class UltraConfigModelTest extends UltraTestCase
{
    public function test_it_can_create_a_config_record()
    {
        $config = UltraConfigModel::create([
            'key' => 'app.locale',
            'value' => 'it',
        ]);

        $this->assertDatabaseHas('uconfig', [
            'key' => 'app.locale',
        ]);
        $this->assertEquals('it', $config->value);
    }

    public function test_value_is_encrypted_in_database()
    {
        $config = UltraConfigModel::create([
            'key' => 'secret.api_key',
            'value' => 'super-secret',
        ]);

        // Raw DB read
        $dbValue = DB::table('uconfig')->where('id', $config->id)->value('value');

        $this->assertNotEquals('super-secret', $dbValue, 'Value should be encrypted in DB');
        $this->assertEquals('super-secret', $config->value, 'Value should be decrypted via accessor');
    }

    public function test_versions_relationship_works()
    {
        $config = UltraConfigModel::create([
            'key' => 'system.debug',
            'value' => true,
        ]);

        UltraConfigVersion::create([
            'uconfig_id' => $config->id,
            'version' => 1,
            'key' => $config->key,
            'value' => $config->value,
        ]);

        $this->assertCount(1, $config->versions);
        $this->assertEquals(1, $config->versions->first()->version);
    }
}
