<?php

namespace Tests\Unit\UltraConfigManager;

use Tests\UltraTestCase;
use Ultra\UltraConfigManager\Dao\EloquentConfigDao;
use Ultra\ErrorManager\Facades\TestingConditions;
use Ultra\UltraConfigManager\Models\UltraConfigModel;

class ConfigDaoSimulationTest extends UltraTestCase
{
    public function test_getConfigByKey_returns_error_when_simulated_as_not_found()
    {
        // Arrange: abilitiamo la simulazione del fallimento
        TestingConditions::set('UCM_NOT_FOUND');

        // Eseguiamo il metodo da testare
        $dao = new EloquentConfigDao();
        $result = $dao->getConfigByKey('missing.key');

        // Assert: l'oggetto restituito è un errore gestito
        $this->assertTrue(method_exists($result, 'isUltraError'));
        $this->assertTrue($result->isUltraError());
        $this->assertEquals('UCM_NOT_FOUND', $result->getErrorCode());

        // Clean up: disattiva la condizione
        TestingConditions::clear('UCM_NOT_FOUND');
    }
}
