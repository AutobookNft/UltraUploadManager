<?php

namespace Ultra\UploadManager\Traits;

use Ultra\UploadManager\Services\TestingConditionsManager;


trait TestingTrait
{
    /**
     * Metodo per verificare se un determinato test è attivo.
     *
     * @param string $key
     * @return bool
     */
    public function isTesting($key)
    {
        // Verifica lo stato della condizione nel singleton
        return TestingConditionsManager::getInstance()->isTesting($key);
    }

    /**
     * Metodo per attivare o disattivare un test.
     *
     * @param string $key
     * @param bool $value
     * @return void
     */
    public function setTestingCondition($key, $value)
    {
        // Imposta la condizione nel singleton
        TestingConditionsManager::getInstance()->setCondition($key, $value);
    }

    /**
     * Metodo per attivare o disattivare tutti i test contemporaneamente.
     *
     * @param bool $value
     * @return void
     */
    public function setAllTestingConditions($value)
    {
        // Usa il metodo resetConditions per reimpostare tutto a `false`
        if (!$value) {
            TestingConditionsManager::getInstance()->resetConditions();
        } else {
            // Se si desidera impostare tutte le condizioni a true, dovresti farlo manualmente
            // Per esempio, potresti scorrere le chiavi nel singleton e impostarle
            // Usa la tua logica per gestire questa parte
        }
    }
}

