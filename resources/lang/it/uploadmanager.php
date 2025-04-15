<?php

return [

    'dev' => [
        'invalid_file' => 'File non valido o mancante: :fileName',
        'invalid_file_validation' => 'Validazione fallita per il file :fileName: :error',
        'error_saving_file_metadata' => 'Impossibile salvare i metadati del file :fileName',
        'server_limits_restrictive' => 'I limiti di upload del server sono più restrittivi rispetto alle impostazioni dell\'applicazione',
        // ... altri messaggi
    ],
    'user' => [
        'invalid_file' => 'Il file caricato non è valido. Riprova con un altro file.',
        'invalid_file_validation' => 'Il file non soddisfa i requisiti. Verifica formato e dimensione.',
        'error_saving_file_metadata' => 'Si è verificato un errore durante il salvataggio delle informazioni sul file.',
        'server_limits_restrictive' => '',
        // ... altri messaggi
    ],

    'upload' => [
        'max_files' => 'Massimo :count file',
        'max_file_size' => 'Massimo :size per file',
        'max_total_size' => 'Massimo :size totale',
        'max_files_error' => 'Puoi caricare un massimo di :count file alla volta.',
        'max_file_size_error' => 'Il file ":name" supera la dimensione massima consentita (:size).',
        'max_total_size_error' => 'La dimensione totale dei file (:size) supera il limite consentito (:limit).',
    ],

    // Badge funzionalità enterprise (Punto 4)
    'storage_space_unit' => 'GB',
    'secure_storage' => 'Archiviazione Sicura',
    'secure_storage_tooltip' => 'I tuoi file vengono salvati con ridondanza per proteggere i tuoi asset',
    'virus_scan_feature' => 'Scansione Virus',
    'virus_scan_tooltip' => 'Ogni file viene scansionato per rilevare potenziali minacce prima dell\'archiviazione',
    'advanced_validation' => 'Validazione Avanzata',
    'advanced_validation_tooltip' => 'Validazione del formato e integrità dei file',
    'storage_space' => 'Spazio: :used/:total GB',
    'storage_space_tooltip' => 'Spazio di archiviazione disponibile per i tuoi EGI',
    'toggle_virus_scan' => 'Attiva/disattiva la scansione virus',

    // Metadata EGI (Punto 3)
    'quick_egi_metadata' => 'Metadata EGI rapidi',
    'egi_title' => 'Titolo EGI',
    'egi_title_placeholder' => 'Es. Pixel Dragon #123',
    'egi_collection' => 'Collezione',
    'select_collection' => 'Seleziona collezione',
    'existing_collections' => 'Collezioni esistenti',
    'create_new_collection' => 'Crea nuova collezione',
    'egi_description' => 'Descrizione',
    'egi_description_placeholder' => 'Breve descrizione dell\'opera...',
    'metadata_notice' => 'Questi metadata saranno associati al tuo EGI, ma potrai modificarli in seguito.',

    // Accessibilità (Punto 5)
    'select_files_aria' => 'Seleziona file per l\'upload',
    'select_files_tooltip' => 'Seleziona uno o più file dal tuo dispositivo',
    'save_aria' => 'Salva i file selezionati',
    'save_tooltip' => 'Carica i file selezionati sul server',
    'cancel_aria' => 'Annulla l\'upload corrente',
    'cancel_tooltip' => 'Annulla l\'operazione e rimuovi i file selezionati',
    'return_aria' => 'Torna alla collezione',
    'return_tooltip' => 'Torna alla vista della collezione senza salvare',

    // Generale
    'file_saved_successfully' => 'File :fileCaricato salvato con successo',
    'file_deleted_successfully' => 'File eliminato con successo',
    'first_template_title' => 'Ultra Upload Manager by Fabio Cherici',
    'file_upload' => 'Caricamento File',
    'max_file_size_reminder' => 'Dimensione massima del file: 10MB',
    'upload_your_files' => 'Carica i tuoi file',
    'save_the_files' => 'Salva file',
    'cancel' => 'Annulla',
    'return_to_collection' => 'Torna alla collezione',
    'mint_your_masterpiece' => 'Crea il Tuo Capolavoro',
    'preparing_to_mint' => 'Sto aspettando i tuoi file, caro...',
    'cancel_confirmation' => 'Vuoi cancellare?',
    'waiting_for_upload' => 'Stato Upload: In attesa...',
    'server_unexpected_response' => 'Il server ha restituito una risposta non valida o inaspettata.',
    'unable_to_save_after_recreate' => 'Impossibile salvare il file dopo aver ricreato la directory.',
    'config_not_loaded' => 'Configurazione globale non caricata. Assicurati che i dati siano stati recuperati.',
    'drag_files_here' => 'Trascina i file qui',
    'select_files' => 'Seleziona i file',
    'or' => 'o',

    // Validation messages
    'allowedExtensionsMessage' => 'Estensione del file non consentita. Le estensioni consentite sono: :allowedExtensions',
    'allowedMimeTypesMessage' => 'Tipo di file non consentito. I tipi di file consentiti sono: :allowedMimeTypes',
    'maxFileSizeMessage' => 'Dimensione del file troppo grande. La dimensione massima consentita è :maxFileSize',
    'minFileSizeMessage' => 'Dimensione del file troppo piccola. La dimensione minima consentita è :minFileSize',
    'maxNumberOfFilesMessage' => 'Numero massimo di file superato. Il numero massimo consentito è :maxNumberOfFiles',
    'acceptFileTypesMessage' => 'Tipo di file non consentito. I tipi di file consentiti sono: :acceptFileTypes',
    'invalidFileNameMessage' => 'Nome del file non valido. Il nome del file non può contenere i seguenti caratteri: / \ ? % * : | " < >',


    // Scansione virus
    'virus_scan_disabled' => 'Scansione virus disabilitata',
    'virus_scan_enabled' => 'Scansione virus abilitata',
    'antivirus_scan_in_progress' => 'Scansione antivirus in corso',
    'scan_skipped_but_upload_continues' => 'Scansione saltata, ma il caricamento continua',
    'scanning_stopped' => 'Scansione interrotta',
    'file_scanned_successfully' => 'File :fileCaricato scansionato con successo',
    'one_or_more_files_were_found_infected' => 'Uno o più file sono stati rilevati come infetti',
    'all_files_were_scanned_no_infected_files' => 'Tutti i file sono stati scansionati e nessun file infetto è stato trovato',
    'the_uploaded_file_was_detected_as_infected' => 'Il file caricato è stato rilevato come infetto',
    'possible_scanning_issues' => 'Avviso: possibili problemi durante la scansione virus',
    'unable_to_complete_scan_continuing' => 'Avviso: Impossibile completare la scansione virus, ma procediamo comunque',

    // Messaggi di stato
    'im_checking_the_validity_of_the_file' => 'Controllo la validità del file',
    'im_recording_the_information_in_the_database' => 'Registrazione delle informazioni nel database',
    'all_files_are_saved' => 'Tutti i file sono stati salvati',
    'upload_failed' => 'Caricamento fallito',
    'some_errors' => 'Si sono verificati alcuni errori',
    'file_saved_successfully' => 'File :fileCaricato salvato con successo',
    'no_file_uploaded' => 'Nessun file caricato',
    'file_deleted_successfully' => 'File eliminato con successo',

    // Traduzioni JavaScript
    'js' => [
        'upload_processing_error' => 'Errore durante l\'elaborazione dell\'upload',
        'invalid_server_response' => 'Il server ha restituito una risposta non valida o inaspettata.',
        'unexpected_upload_error' => 'Errore imprevisto durante il caricamento.',
        'critical_upload_error' => 'Errore critico durante l\'upload',
        'file_not_found_for_scan' => 'File non trovato per la scansione antivirus',
        'scan_error' => 'Errore durante la scansione antivirus',
        'no_file_specified' => 'Nessun file specificato',
        'confirm_cancel' => 'Vuoi cancellare?',
        'upload_waiting' => 'Stato Upload: In attesa...',
        'server_error' => 'Il server ha restituito una risposta non valida o inaspettata.',
        'save_error' => 'Impossibile salvare il file dopo aver ricreato la directory.',
        'config_error' => 'Configurazione globale non caricata. Assicurati che i dati siano stati recuperati.',
        'starting_upload' => 'Inizio caricamento',
        'loading' => 'Caricamento in corso',
        'upload_finished' => 'Caricamento completato',
        'upload_and_scan' => 'Caricamento e scansione completati',
        'virus_scan_advice' => 'La scansione virus potrebbe rallentare il processo di caricamento',
        'enable_virus_scanning' => 'Scansione virus abilitata',
        'disable_virus_scanning' => 'Scansione virus disabilitata',
        'delete_button' => 'Elimina',
        'of' => 'di',
        'delete_file_error' => 'Errore durante l\'eliminazione del file',
        'some_error' => 'Si sono verificati alcuni errori',
        'complete_failure' => 'Caricamento completamente fallito',

        // Traduzioni aggiunte per feedback emoji
        'emoji_happy' => 'Caricamento completato con successo',
        'emoji_sad' => 'Alcuni file hanno avuto errori durante il caricamento',
        'emoji_angry' => 'Caricamento completamente fallito',

        // Traduzioni aggiunte per il processo di caricamento
        'starting_saving' => 'Inizio salvataggio file',
        'starting_scan' => 'Inizio scansione virus',
        'scanning_complete' => 'Scansione completata',
        'Scanning_stopped' => 'Scansione interrotta',
        'scanning_success' => 'Scansione riuscita :fileCaricato',

        // Traduzioni aggiunte per la gestione degli errori
        'error_during_upload' => 'Errore durante l\'elaborazione del caricamento',
        'error_delete_temp_local' => 'Errore durante l\'eliminazione del file temporaneo locale',
        'error_delete_temp_ext' => 'Errore durante l\'eliminazione del file temporaneo esterno',
        'error_during_upload_request' => 'Errore durante la richiesta di caricamento',
        'unknownError' => 'Errore sconosciuto',
        'unspecifiedError' => 'Errore non specificato',

        // Validation messages
        'invalidFilesTitle' => 'File non validi',
        'invalidFilesMessage' => 'I seguenti file non possono essere caricati',
        'checkFilesGuide' => 'Controlla i tipi di file, le dimensioni e i nomi.',
        'okButton' => 'OK',

        // File type labels
        'file_type_image' => 'Immagine',
        'file_type_document' => 'Documento',
        'file_type_audio' => 'Audio',
        'file_type_video' => 'Video',
        'file_type_archive' => 'Archivio',
        'file_type_3d_model' => 'Modello 3D',

        // Archive file extensions
        'ext_zip' => 'Archivio ZIP',
        'ext_rar' => 'Archivio RAR',
        'ext_7z' => 'Archivio 7-Zip',
        'ext_tar' => 'Archivio TAR',
        'ext_gz' => 'Archivio GZip',

        // 3D Model file extensions
        'ext_obj' => 'Modello 3D OBJ',
        'ext_fbx' => 'Modello 3D FBX',
        'ext_stl' => 'Modello 3D STL',
        'ext_glb' => 'Modello 3D GLB',
        'ext_gltf' => 'Modello 3D glTF',

        // Additional audio formats
        'ext_aac' => 'Audio AAC',
        'ext_ogg' => 'Audio OGG',
        'ext_wma' => 'Audio WMA',

        // Additional video formats
        'ext_wmv' => 'Video WMV',
        'ext_flv' => 'Video Flash',
        'ext_webm' => 'Video WebM',

        // Validation error messages
        'error_unsupported_type' => 'Tipo di file non supportato',
        'error_archive_too_large' => 'Il file di archivio è troppo grande (dimensione massima: {size}MB)',
        'error_3d_model_too_large' => 'Il file del modello 3D è troppo grande (dimensione massima: {size}MB)',
        'error_security_blocked' => 'Questo tipo di file è stato bloccato per motivi di sicurezza',


        ],
];
