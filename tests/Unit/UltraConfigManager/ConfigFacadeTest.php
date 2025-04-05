<?php

namespace Tests\Unit\UltraConfigManager;

use Tests\UltraTestCase;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Facades\UConfig;

class ConfigFacadeTest extends UltraTestCase
{
    public function test_get_returns_value_if_key_exists()
    {
        UltraConfigModel::create([
            'key' => 'site.title',
            'value' => 'Florence EGI',
        ]);

        UConfig::reload();

        $value = UConfig::get('site.title');

        $this->assertEquals('Florence EGI', $value);
    }

    public function test_get_returns_default_if_key_not_found()
    {
        $value = UConfig::get('non.existent.key', 'default');

        $this->assertEquals('default', $value);
    }

    public function test_has_returns_true_if_key_exists()
    {
        UltraConfigModel::create([
            'key' => 'ui.enabled',
            'value' => 'true',
        ]);

        UConfig::reload();

        $this->assertTrue(UConfig::has('ui.enabled'));
    }

    public function test_has_returns_false_if_key_missing()
    {
        $this->assertFalse(UConfig::has('missing.key'));
    }

    public function test_all_returns_all_config_entries()
    {
        UltraConfigModel::create(['key' => 'site.name', 'value' => 'Florence']);
        UltraConfigModel::create(['key' => 'ui.mode', 'value' => 'dark']);

        UConfig::reload();

        $all = UConfig::all();

        $this->assertIsArray($all);
        $this->assertCount(2, $all);
        $this->assertEquals('Florence', $all['site.name']);
        $this->assertEquals('dark', $all['ui.mode']);
    }

    public function test_reload_refetches_updated_values_from_database()
    {
        UltraConfigModel::create(['key' => 'app.theme', 'value' => 'light']);
        UConfig::reload();
        $this->assertEquals('light', UConfig::get('app.theme'));

        // Modifica diretta a DB
        UltraConfigModel::where('key', 'app.theme')->update(['value' => 'dark']);

        UConfig::reload();

        $this->assertEquals('dark', UConfig::get('app.theme'));
    }

}
