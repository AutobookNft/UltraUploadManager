<?php

namespace Ultra\UltraConfigManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Ultra\UltraConfigManager\Facades\UConfig;
use Ultra\UltraConfigManager\Models\UltraConfigModel;
use Ultra\UltraConfigManager\Tests\TestCase;

class UConfigFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_returns_default_for_missing_key(): void
    {
        $key = 'test.missing';
        $default = 'default-value';

        $result = UConfig::get($key, $default);

        $this->assertEquals($default, $result);
    }

    public function test_get_returns_value_for_existing_key(): void
    {
        $config = UltraConfigModel::factory()->create([
            'key' => 'existing.key',
            'value' => 'stored-value'
        ]);

        $result = UConfig::get('existing.key', 'default-value');

        $this->assertEquals('stored-value', $result);
    }

    public function test_set_creates_new_config(): void
    {
        $key = 'test.new';
        $value = 'test-value';
        $category = 'testing';

        UConfig::set($key, $value, $category);
        $result = UConfig::get($key);

        $this->assertEquals($value, $result);

        $this->assertDatabaseHas('uconfig', [
            'key' => $key,
            'category' => $category,
        ]);

        $this->assertDatabaseHas('uconfig_versions', [
            'key' => $key,
            'version' => 1,
        ]);

        $this->assertDatabaseHas('uconfig_audit', [
            'action' => 'created',
            'new_value' => $value,
        ]);
    }

    public function test_set_overwrites_existing_config_and_creates_version(): void
    {
        $config = UltraConfigModel::factory()->create([
            'key' => 'overwrite.key',
            'value' => 'old-value',
        ]);

        UConfig::set('overwrite.key', 'new-value');

        $this->assertEquals('new-value', UConfig::get('overwrite.key'));

        $this->assertDatabaseHas('uconfig_versions', [
            'key' => 'overwrite.key',
            'version' => 2,
        ]);

        $this->assertDatabaseHas('uconfig_audit', [
            'action' => 'updated',
            'old_value' => 'old-value',
            'new_value' => 'new-value',
        ]);
    }

    public function test_delete_removes_config_and_logs_audit(): void
    {
        $config = UltraConfigModel::factory()->create([
            'key' => 'to.delete',
            'value' => 'delete-me',
        ]);

        UConfig::delete('to.delete');

        $this->assertNull(UConfig::get('to.delete'));

        $this->assertSoftDeleted('uconfig', [
            'key' => 'to.delete'
        ]);

        $this->assertDatabaseHas('uconfig_audit', [
            'action' => 'deleted',
            'old_value' => 'delete-me',
        ]);
    }
}
