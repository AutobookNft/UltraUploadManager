<?php

namespace Ultra\UltraConfigManager\Tests\Unit;


use Illuminate\Foundation\Testing\RefreshDatabase; // <-- Aggiungere
use Ultra\UltraConfigManager\Models\UltraConfigModel; // <-- Aggiungere (per creare il parent)
use Ultra\UltraConfigManager\Models\UltraConfigVersion; // <-- Aggiungere (per creare la versione)
use Ultra\UltraConfigManager\Services\VersionManager;
use Ultra\UltraConfigManager\Tests\UltraTestCase;
use PHPUnit\Framework\Attributes\Test; // <-- Aggiungere (buona pratica)
use PHPUnit\Framework\Attributes\CoversClass;
use Ultra\UltraConfigManager\Casts\EncryptedCast;
use Ultra\UltraConfigManager\Providers\UConfigServiceProvider;
use PHPUnit\Framework\Attributes\UsesClass; 


#[CoversClass(VersionManager::class)]
#[UsesClass(UltraConfigModel::class)]        
#[UsesClass(UltraConfigVersion::class)]      
#[UsesClass(EncryptedCast::class)]           
#[UsesClass(UConfigServiceProvider::class)]  
class VersionManagerTest extends UltraTestCase
{
    use RefreshDatabase; // <-- Aggiungere trait per pulire DB

    private VersionManager $manager;


    protected function setUp(): void
    {
        parent::setUp(); // Necessario per Testbench/RefreshDatabase
        $this->manager = new VersionManager();
    }

    #[Test] // <-- Aggiungere attributo
    public function getNextVersion_returns_1_when_no_previous_versions_exist(): void
    {
        // Arrange: Crea un config padre MA nessuna versione per esso
        $config = UltraConfigModel::factory()->create();

        // Act: Chiama getNextVersion per questo ID
        $nextVersion = $this->manager->getNextVersion($config->id);

        // Assert: Deve restituire 1
        $this->assertEquals(1, $nextVersion);
    }

    #[Test] // <-- Aggiungere attributo
    public function getNextVersion_returns_incremented_value_when_previous_versions_exist(): void
    {
        // Arrange: Crea un config padre
        $config = UltraConfigModel::factory()->create();

        // Crea una versione precedente (es. versione 5)
        UltraConfigVersion::factory()->create([
            'uconfig_id' => $config->id,
            'version' => 5,
            'key' => $config->key, // Assicurati che i campi obbligatori siano presenti
            'value' => 'v5',      // Assicurati che i campi obbligatori siano presenti
        ]);
        // Crea un'altra versione precedente (es. versione 2) per assicurarsi che prenda il max
        UltraConfigVersion::factory()->create([
            'uconfig_id' => $config->id,
            'version' => 2,
            'key' => $config->key,
            'value' => 'v2',
        ]);


        // Act: Chiama getNextVersion per questo ID
        $nextVersion = $this->manager->getNextVersion($config->id);

        // Assert: Deve restituire 6 (max(5, 2) + 1)
        $this->assertEquals(6, $nextVersion);
    }

    #[Test] // <-- Aggiungere attributo
    public function getNextVersion_throws_exception_for_invalid_id(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->getNextVersion(0); // ID non valido
    }

    // Test per PersistenceException (più difficile da simulare senza mocking profondo)
    // #[Test]
    // public function getNextVersion_throws_persistence_exception_on_db_error(): void
    // {
    //     // Arrange: Mock UltraConfigVersion::where... to throw QueryException
    //     // ...
    //     $this->expectException(PersistenceException::class);
    //     $this->manager->getNextVersion(1);
    // }
}