<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurazioni di UConfig
    |--------------------------------------------------------------------------
    */
    
    // Configurazione del database
    'database' => [
        'table' => 'uconfig',  // nome della tabella per le configurazioni
    ],
    
    // Altre impostazioni
    'cache' => [
        'enabled' => true,
        'ttl' => 3600  // tempo in secondi
    ],

    /*
    |--------------------------------------------------------------------------
    | Utilizzo di Spatie Laravel Permission
    |--------------------------------------------------------------------------
    |
    | Se impostato a true, il pacchetto utilizzerà Spatie Laravel Permission
    | per gestire ruoli e permessi. Assicurati che il pacchetto sia installato
    | e configurato correttamente nella tua applicazione.
    |
    | Se impostato a false, verrà utilizzato il middleware personalizzato che
    | verifica il ruolo dell'utente in base a una logica personalizzata.
    |
    */

    'use_spatie_permissions' => true,
];  
