<?php

namespace Ultra\UploadManager\Services;

use Illuminate\Support\Facades\Log;

/**
 * Class TestingConditionsManager
 *
 * Singleton per la gestione delle condizioni di test. Questa classe fornisce
 * un modo centralizzato per attivare o disattivare determinate simulazioni di errore
 * durante i test. Viene utilizzata in modo condiviso tra diversi test e componenti
 * dell'applicazione, garantendo che le condizioni di test siano consistenti
 * e accessibili in modo globale.
 *
 * Funzionalità principali:
 * - Attivazione/disattivazione di specifiche condizioni di test
 * - Controllo dello stato di simulazione di errori per test mirati
 * - Implementazione del pattern Singleton per garantire un'unica istanza condivisa
 *
 * Utilizzo:
 *
 * In un test:
 * ```php
 * TestingConditionsManager::getInstance()->setCondition('unable_to_create_directory', true);
 * ```
 *
 * In un controller o servizio:
 * ```php
 * if (TestingConditionsManager::getInstance()->isConditionActive('unable_to_create_directory')) {
 *     // Gestisci il caso in cui la directory non può essere creata
 * }
 * ```
 *
 * Questo approccio garantisce che le condizioni di test siano coerenti e centralizzate,
 * migliorando la manutenibilità e la leggibilità del codice.
 *
 * @package App\Services
 */

 class TestingConditionsManager

 {
     private static $instance = null;
     private $testingConditions = [];

     private function __construct()
     {
        $this->resetConditions(); // Inizializza le condizioni di test
     }

     // Metodo per ottenere l'istanza singleton
     public static function getInstance(): self
     {
         if (self::$instance === null) {
             self::$instance = new self();
         }
         return self::$instance;
     }

     // Metodo per impostare una condizione specifica
     public function setCondition(string $key, bool $value): void
     {
         $this->testingConditions[$key] = $value;

     }

     // Metodo per verificare se un determinato test è attivo
     public function isTesting(string $key): bool
     {

        if ($this->testingConditions[$key]){
            Log::channel('upload')->error('', [
                'Class' => 'TestingConditionsManager',
                'Method' => 'isTesting',
                'Value' => $key . ' = ' . $this->testingConditions[$key],
            ]);
        }

        return $this->testingConditions[$key] ?? false;
     }

     // Metodo per reimpostare tutte le condizioni di test
     public function resetConditions(): void
     {
         // Reimposta tutte le condizioni al loro stato predefinito (false)
         $this->testingConditions = [
             'INVALID_FILE_EXTENSION' => false,
             'MIME_TYPE_NOT_ALLOWED' => false,
             'INVALID_FILE_NAME' => false,
             'MAX_FILE_SIZE' => false,
             'FILE_NOT_FOUND' => false,
             'TEMP_FILE_NOT_FOUND' => false,
             'INVALID_IMAGE_STRUCTURE' => false,
             'INVALID_FILE_PDF' => false,
             'SCAN_ERROR' => false,
             'VIRUS_FOUND' => false,
             'ERROR_DELETING_LOCAL_TEMP_FILE' => false,
             'ERROR_DELETING_EXT_TEMP_FILE' => false,
             'ACL_SETTING_ERROR' => false,
             'ERROR_GETTING_PRESIGNED_URL' => false,
             'UNABLE_TO_SAVE_BOT_FILE' => false,
             'UNABLE_TO_CREATE_DIRECTORY' => false,
             'UNABLE_TO_CHANGE_PERMISSIONS' => false,
             'ERROR_DURING_CREATE_EGI_RECORD' => false,
             'IMAGICK_NOT_AVAILABLE' => false,
             'JSON_ERROR' => false,
             'GENERIC_SERVER_ERROR' => false,
         ];
     }
 }
