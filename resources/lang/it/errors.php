<?php

/*
|--------------------------------------------------------------------------
| Traduzione in italiano di tutti i messaggi di errore
|--------------------------------------------------------------------------
|
 */

return [

    'AUTHENTICATION_ERROR' => 'Accesso non autorizzato',
    'SCAN_ERROR' => 'Errore di scansione',
    'VIRUS_FOUND' => 'Virus trovato',
    'INVALID_FILE_EXTENSION' => 'Estensione del file non valida',
    'MAX_FILE_SIZE' => 'Il file non può superare :max byte.',
    'INVALID_FILE_PDF' => 'File PDF non valido',
    'MIME_TYPE_NOT_ALLOWED' => 'Il tipo di file non è consentito.',
    'INVALID_IMAGE_STRUCTURE' => 'Struttura dell\'immagine non valida',
    'INVALID_FILE_NAME' => 'Nome del file non valido',
    'ERROR_GETTING_PRESIGNED_URL' => 'Errore durante il caricamento del file',
    'ERROR_DURING_FILE_UPLOAD' => 'Errore durante il caricamento del file',
    'UNABLE_TO_SAVE_BOT_FILE' => 'Impossibile salvare il file.',
    'UNABLE_TO_CREATE_DIRECTORY' => 'Impossibile creare la cartella',
    'UNABLE_TO_CHANGE_PERMISSIONS' => 'Impossibile cambiare i permessi della cartella',
    'IMPOSSIBLE_SAVE_FILE' => 'Impossibile salvare il file',
    'ERROR_DURING_CREATE_EGI_RECORD' => 'Problema interno, l\'assistenza è già allertata',
    'ERROR_DURING_FILE_NAME_ENCRYPTION' => 'Errore durante la crittografia del nome del file',
    'IMAGICK_NOT_AVAILABLE' => 'Problema interno, l\'assistenza è già allertata',
    'JSON_ERROR_IN_DISPATCHER' => 'Errore JSON nel dispatcher',
    'GENERIC_SERVER_ERROR' => 'Errore generico del server, il team tecnico è stato informato',
    'FILE_NOT_FOUND' => 'File non trovato',
    'UNEXPECTED_ERROR' => 'Problema interno, l\'assistenza è già allertata',
    'ERROR_DELETING_LOCAL_TEMP_FILE' => 'Errore durante l\'eliminazione del file temporaneo locale',

    'required' => 'Il campo è obbligatorio.',
    'file' => 'Si è verificato un errore durante il caricamento del file.',
    'mimes' => 'Il file deve essere di tipo: :values.',
    'error_getting_presigned_URL' => 'Errore durante il recupero dell\'URL prefirmato',
    'error_deleting_file' => 'Errore durante l\'eliminazione del file',
    'upload_finished' => 'Caricamento completato',
    'some_errors' => 'alcuni errori',
    'upload_failed' => 'caricamento fallito',
    'error_creating_folder' => 'Errore durante la creazione della cartella',
    'error_changing_folder_permissions' => 'Errore durante la modifica dei permessi della cartella',
    'local_save_failed_file_saved_to_external_disk_only' => 'Salvataggio locale fallito, file salvato solo sul disco esterno',
    'external_save_failed_file_saved_to_local_disk_only' => 'Salvataggio esterno fallito, file salvato solo sul disco locale',
    'file_scanning_may_take_a_long_time_for_each_file' => 'La scansione dei file potrebbe richiedere molto tempo per ciascun file',
    'all_files_are_saved' => 'Tutti i file sono salvati',
    'loading_finished_you_can_proceed_with_saving' => 'Caricamento completato, puoi procedere con il salvataggio',
    'loading_finished_you_can_proceed_with_saving_and_scan' => 'Caricamento completato, puoi procedere con il salvataggio e la scansione',
    'im_uploading_the_file' => 'Sto caricando il file',

    'exception' => [
        'NotAllowedTermException' => 'Termine non consentito',
        'MissingCategory' => 'E\' necessario inserire una categoria.',
        'DatabaseException' => 'Si è verificato un errore del database',
        'ValidationException' => 'Si è verificato un errore di validazione',
        'HttpException' => 'Si è verificato un errore HTTP',
        'ModelNotFoundException' => 'Modello non trovato',
        'QueryException' => 'Errore di query',
        'MintingException' => 'Errore durante il minting',
        'FileNotFoundException' => 'File non trovato',
        'InvalidArgumentException' => 'Argomento non valido',
        'UnexpectedValueException' => 'Valore inaspettato',
        'ItemNotFoundException' => 'Elemento non trovato',
        'MultipleItemsFoundException' => 'Trovati più elementi',
        'LogicException' => 'Eccezione logica',
        'EntryNotFoundException' => 'Voce non trovata',
        'RuntimeException' => 'Errore di runtime',
        'BadMethodCallException' => 'Chiamata a metodo non valida',
        'LockTimeoutException' => 'Timeout del blocco',
        'InvalidIntervalException' => 'Intervallo non valido',
        'InvalidPeriodParameterException' => 'Parametro di periodo non valido',
        'EndLessPeriodException' => 'Periodo senza fine',
        'UnreachableException' => 'Eccezione irraggiungibile',
        'InvalidTimeZoneException' => 'Fuso orario non valido',
        'ImmutableException' => 'Eccezione immutabile',
        'InvalidFormatException' => 'Formato non valido',

    ],
    'the_input_must_be_a_string' => 'Il valore deve essere una stringa.',
    'forbidden_term_warning' =>"
        <div style=\"text-align: left;\">
            <p>Gentile Utente,</p>
            </br>
            <p>Il testo che hai inserito viola le nostre norme e linee guida sulla comunità. Ti invitiamo a modificare il contenuto e riprovare.</p>
            </br>
            <p>Se non ti è chiaro il motivo per cui questo termine è vietato, ti preghiamo di fare riferimento alle clausole dell'accordo che hai accettato al momento della registrazione.
            <p>Grazie per la tua comprensione e collaborazione.</p>
            </br>
            <p>Cordiali saluti,
            <br>
            Il Team di Frangette</p>
        </div>",

    'letter_of_the_rules_of_conduct' =>
        '<a href=\":link\" style=\"color: blue; text-decoration: underline;\">
            Consulta la pagina delle norme della comunità.
        </a>.',

    'forbiddenTermChecker_was_not_initialized_correctly' => 'ForbiddenTermChecker non è stato inizializzato correttamente',
    'table_not_exist' => 'La tabella non esiste',
    'unique' => 'Questo valore è già presente nella tua libreira dei traits',
    'the_category_name_cannot_be_empty' => 'Il nome della categoria non può essere vuoto',
    'nathing_to_save' => 'Niente da salvare',
    'an_error_occurred' => 'Urgh! ci dispiace, si è verificato un errore!',
    'error_number' => 'Numero di errore:',
    'reason' => [
        'reason' => 'motivo',
        'wallet_not_valid' => 'Wallet non valido',
        'something_went_wrong' => 'Qualcosa è andato storto',

    ],
    'solution' => [
        'solution' => 'soluzione',
        'create_a_new_wallet_and_try_again' => 'Crea un nuovo wallet e prova di nuovo',
        'we_are_already_working_on_solving_the_problem' => 'Stiamo già lavorando per risolvere il problema',

    ],

    'min' => [
        'string' => 'Il campo deve essere di almeno :min caratteri.',
    ],
    'max' => [
        'string' => 'Il campo deve essere di al massimo :max caratteri.',
    ],

    'id_epp_not_found' => 'Id EPP non trovato',

    'minting' => [
        'error_generating_token' => 'Errore durante la generazione del token',
        'insufficient_wallet_balance' => 'Non hai fondi sufficenti per acquistare questo EcoNFT',
        'error_during_save_the_metadataFile' => 'Errore durante il salvataggio dei metadata nel file',
        'error_during_save_the_metadata_on_database' => 'Errore durante il salvataggio dei metadata nel database',
        'error_during_create_metadata_file' => 'Errore durante la creazione del file metadata',
        'error_during_save_the_buyer' => 'Errore durante il salvataggio del buyer',
        'buyer_not_exist' => 'Il buyer non esiste',
        'this_wallet_does_not_belong_to_any_buyer' => 'Questo wallet non appartiene a nessun buyer',
        'seller_not_exist' => 'Il seller non esiste',
        'seller_owner_not_found' => 'Il seller owner non esiste',
        'seller_wallet_address_not_found' => 'L\'indirizzo del wallet del seller non esiste',
        'error_during_save_the_seller' => 'Errore durante il salvataggio del seller',
        'error_during_save_the_buyer_transaction' => 'Errore durante il salvataggio della transazione per il buyer',
        'error_during_the_saving_of_the_payment' => 'Errore durante il salvataggio del pagamento',
        'error_during_save_the_natan' => 'Errore durante il salvataggio dei dati', // non voglio specificare che si tratta di un errore durante il salvataggio delle royalty per Natan,
        'error_during_save_the_transaction' => 'Errore durante il salvataggio della transazione',
        'seller_not_found' => 'Seller non trovato',
        'error_during_the_minting' => 'Errore durante il minting',
        'error_uploading_file' => 'Errore durante il caricamento del file',
        'insufficient_balance' => 'Saldo insufficiente',
        'eco_nft_not_found' => 'EcoNFT non trovato',
        'no_traits_found' => 'Nessun trait trovato',
    ],
];
