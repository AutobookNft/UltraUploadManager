<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Ultra\UltraConfigManager\Models\UltraConfigModel;

class UCMModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_allows_key_on_creation_but_blocks_update()
    {
        // 🔧 Arrange: crea una nuova config
        $config = UltraConfigModel::create([
            'key' => 'test.key',
            'value' => 'initial value',
            'category' => 'system',
        ]);

        // ✅ Assert: creazione avvenuta con la chiave corretta
        $this->assertEquals('test.key', $config->key);

        // 💥 Act + Assert: proviamo a cambiare la chiave
        $config->key = 'test.key.updated';

        \$this->expectException(\LogicException::class);
        \$this->expectExceptionMessage('Configuration key cannot be modified after creation');

        $config->save(); // deve lanciare l'eccezione
    }
}
