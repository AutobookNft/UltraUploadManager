<?php

/*
|--------------------------------------------------------------------------
| Traduzione in tedesco di tutti i messaggi di errore
|--------------------------------------------------------------------------
|
 */

return [
    'AUTHENTICATION_ERROR' => 'Zugriff nicht autorisiert',
    'SCAN_ERROR' => 'Scan-Fehler',
    'VIRUS_FOUND' => 'Virus gefunden',
    'INVALID_FILE_EXTENSION' => 'Ungültige Dateierweiterung',
    'MAX_FILE_SIZE' => 'Die Datei darf :max Byte nicht überschreiten.',
    'INVALID_FILE_PDF' => 'Ungültige PDF-Datei',
    'MIME_TYPE_NOT_ALLOWED' => 'Dateityp nicht erlaubt.',
    'INVALID_IMAGE_STRUCTURE' => 'Ungültige Bildstruktur',
    'INVALID_FILE_NAME' => 'Ungültiger Dateiname',
    'ERROR_GETTING_PRESIGNED_URL' => 'Fehler beim Abrufen der vorab signierten URL',
    'ERROR_DURING_FILE_UPLOAD' => 'Fehler beim Hochladen der Datei',
    'UNABLE_TO_SAVE_BOT_FILE' => 'Datei konnte nicht gespeichert werden',
    'UNABLE_TO_CREATE_DIRECTORY' => 'Ordner konnte nicht erstellt werden',
    'UNABLE_TO_CHANGE_PERMISSIONS' => 'Ordnerberechtigungen konnten nicht geändert werden',
    'IMPOSSIBLE_SAVE_FILE' => 'Datei konnte nicht gespeichert werden',
    'ERROR_DURING_CREATE_EGI_RECORD' => 'Internes Problem, der Support wurde bereits benachrichtigt',
    'ERROR_DURING_FILE_NAME_ENCRYPTION' => 'Fehler bei der Verschlüsselung des Dateinamens',
    'IMAGICK_NOT_AVAILABLE' => 'Internes Problem, der Support wurde bereits benachrichtigt',
    'JSON_ERROR_IN_DISPATCHER' => 'JSON-Fehler im Dispatcher',
    'GENERIC_SERVER_ERROR' => 'Generischer Serverfehler, das technische Team wurde informiert',
    'FILE_NOT_FOUND' => 'Datei nicht gefunden',
    'UNEXPECTED_ERROR' => 'Generischer Serverfehler, das technische Team wurde informiert',
    'ERROR_DELETING_LOCAL_TEMP_FILE' => 'Fehler beim Löschen der lokalen temporären Datei',

    'unexpected_error' => 'Unerwarteter Fehler',
    'scan_error' => 'Fehler beim Scannen',
    'virus_found' => 'Virus gefunden',
    'required' => 'Das Feld ist erforderlich.',
    'file' => 'Beim Hochladen der Datei ist ein Fehler aufgetreten.',
    'file_extension_not_valid' => 'Dateierweiterung ungültig',
    'mimes' => 'Die Datei muss vom Typ sein: :values.',
    'max_file_size' => 'Die Datei darf :max Byte nicht überschreiten.',
    'invalid_pdf_file' => 'Ungültige PDF-Datei',
    'mime_type_not_allowed' => 'Dateityp nicht erlaubt.',
    'invalid_image_structure' => 'Ungültige Bildstruktur',
    'invalid_file_name' => 'Ungültiger Dateiname',
    'error_getting_presigned_URL' => 'Fehler beim Abrufen der vorab signierten URL',
    'error_getting_presigned_URL_for_user' => 'Fehler beim Abrufen der vorab signierten URL für Benutzer',
    'error_during_file_upload' => 'Fehler beim Hochladen der Datei',
    'error_deleting_file' => 'Fehler beim Löschen der Datei',
    'upload_finished' => 'Hochladen',
    'some_errors' => 'einige Fehler',
    'upload_failed' => 'Hochladen fehlgeschlagen',
    'error_creating_folder' => 'Fehler beim Erstellen des Ordners',
    'error_changing_folder_permissions' => 'Fehler beim Ändern der Ordnerberechtigungen',
    'local_save_failed_file_saved_to_external_disk_only' => 'Lokales Speichern fehlgeschlagen, Datei nur auf externer Festplatte gespeichert',
    'external_save_failed_file_saved_to_local_disk_only' => 'Externe Speicherung fehlgeschlagen, Datei nur auf lokaler Festplatte gespeichert',
    'file_scanning_may_take_a_long_time_for_each_file' => 'Das Scannen von Dateien kann für jede Datei viel Zeit in Anspruch nehmen',
    'all_files_are_saved' => 'Alle Dateien sind gespeichert',
    'loading_finished_you_can_proceed_with_saving' => 'Das Laden ist abgeschlossen, Sie können mit dem Speichern fortfahren',
    'loading_finished_you_can_proceed_with_saving_and_scan' => 'Das Laden ist abgeschlossen, Sie können mit dem Speichern und Scannen fortfahren',
    'im_uploading_the_file' => 'Ich lade die Datei hoch',


    'exception' => [
        'NotAllowedTermException' => 'Nicht erlaubter Begriff',
        'MissingCategory' => 'Sie müssen eine Kategorie eingeben.',
        'DatabaseException' => 'Ein Datenbankfehler ist aufgetreten',
        'ValidationException' => 'Ein Validierungsfehler ist aufgetreten',
        'HttpException' => 'Ein HTTP-Fehler ist aufgetreten',
        'ModelNotFoundException' => 'Modell nicht gefunden',
        'QueryException' => 'Abfragefehler',
        'MintingException' => 'Fehler beim Prägen',
        'FileNotFoundException' => 'Datei nicht gefunden',
        'InvalidArgumentException' => 'Ungültiges Argument',
        'UnexpectedValueException' => 'Unerwarteter Wert',
        'ItemNotFoundException' => 'Element nicht gefunden',
        'MultipleItemsFoundException' => 'Mehrere Elemente gefunden',
        'LogicException' => 'Logikfehler',
        'EntryNotFoundException' => 'Eintrag nicht gefunden',
        'RuntimeException' => 'Laufzeitfehler',
        'BadMethodCallException' => 'Ungültiger Methodenaufruf',
        'LockTimeoutException' => 'Sperrzeitüberschreitung',
        'InvalidIntervalException' => 'Ungültiges Intervall',
        'InvalidPeriodParameterException' => 'Ungültiger Periodenparameter',
        'EndLessPeriodException' => 'Endloses Zeitintervall',
        'UnreachableException' => 'Nicht erreichbare Ausnahme',
        'InvalidTimeZoneException' => 'Ungültige Zeitzone',
        'ImmutableException' => 'Unveränderliche Ausnahme',
        'InvalidFormatException' => 'Ungültiges Format',
    ],
    'forbidden_term_warning' => "
        <div style=\"text-align: left;\">
            <p>Lieber Benutzer,</p>
            </br>
            <p>Der von Ihnen eingegebene Text verstößt gegen unsere Gemeinschaftsrichtlinien und -normen. Bitte ändern Sie den Inhalt und versuchen Sie es erneut.</p>
            </br>
            <p>Wenn Sie nicht verstehen, warum dieser Begriff verboten ist, lesen Sie bitte die Klauseln der Vereinbarung, die Sie zum Zeitpunkt der Registrierung akzeptiert haben.
            <p>Wir danken Ihnen für Ihr Verständnis und Ihre Mitarbeit.</p>
            </br>
            <p>Mit freundlichen Grüßen,
            <br>
            Das Frangette-Team</p>
        </div>",

    'letter_of_the_rules_of_conduct' =>
        '<a href=\":link\" style=\"color: blue; text-decoration: underline;\">
            Siehe die Seite mit den Gemeinschaftsregeln.
        </a>.',

    'forbiddenTermChecker_was_not_initialized_correctly' => 'Der ForbiddenTermChecker wurde nicht korrekt initialisiert',
    'table_not_exist' => 'Die Tabelle existiert nicht',
    'unique' => 'Dieser Wert ist bereits in Ihrer Traits-Bibliothek vorhanden.',
    'the_category_name_cannot_be_empty' => 'Der Kategoriename darf nicht leer sein',
    'nathing_to_save' => 'Nichts zu speichern',
    'an_error_occurred' => 'Hoppla! Entschuldigung, ein Fehler ist aufgetreten!',
    'error_number' => 'Fehlernummer:',
    'reason' => [
        'reason' => 'Grund',
        'wallet_not_valid' => 'Ungültige Brieftasche',
        'something_went_wrong' => 'Etwas ist schief gelaufen',
    ],
    'solution' => [
        'solution' => 'Lösung',
        'create_a_new_wallet_and_try_again' => 'Erstellen Sie eine neue Brieftasche und versuchen Sie es erneut',
        'we_are_already_working_on_solving_the_problem' => 'Wir arbeiten bereits daran, das Problem zu lösen',
    ],
    'min' => [
        'string' => 'Das Feld muss mindestens :min Zeichen lang sein.',
    ],
    'max' => [
        'string' => 'Das Feld darf maximal :max Zeichen lang sein.',
    ],
    'id_epp_not_found' => 'ID EPP nicht gefunden',
    'minting' => [
        'error_generating_token' => 'Fehler beim Generieren des Tokens',
        'insufficient_wallet_balance' => 'Unzureichendes Guthaben in der Brieftasche, um dieses EcoNFT zu kaufen',
        'error_during_save_the_metadataFile' => 'Fehler beim Speichern der Metadaten in der Datei',
        'error_during_save_the_metadata_on_database' => 'Fehler beim Speichern der Metadaten in der Datenbank',
        'error_during_create_metadata_file' => 'Fehler beim Erstellen der Metadatendatei',
        'error_during_save_the_buyer' => 'Fehler beim Speichern des Käufers',
        'buyer_not_exist' => 'Käufer existiert nicht',
        'this_wallet_does_not_belong_to_any_buyer' => 'Diese Brieftasche gehört zu keinem Käufer',
        'seller_not_exist' => 'Verkäufer existiert nicht',
        'seller_owner_not_found' => 'Verkäuferbesitzer nicht gefunden',
        'seller_wallet_address_not_found' => 'Adresse der Verkäuferbrieftasche nicht gefunden',
        'error_during_save_the_seller' => 'Fehler beim Speichern des Verkäufers',
        'error_during_save_the_buyer_transaction' => 'Fehler beim Speichern der Käufertransaktion',
        'error_during_the_saving_of_the_payment' => 'Fehler beim Speichern der Zahlung',
        'error_during_save_the_natan' => 'Fehler beim Speichern der Daten', // non voglio specificare che si tratta di un errore durante il salvataggio delle royalty per Natan,
        'error_during_save_the_transaction' => 'Fehler beim Speichern der Transaktion',
        'seller_not_found' => 'Verkäufer nicht gefunden',
        'error_during_the_minting' => 'Fehler beim Prägen',
        'error_uploading_file' => 'Fehler beim Hochladen der Datei',
        'insufficient_balance' => 'Unzureichendes Guthaben',
        'eco_nft_not_found' => 'EcoNFT nicht gefunden',
        'no_traits_found' => 'Keine Merkmale gefunden',
    ],
];
