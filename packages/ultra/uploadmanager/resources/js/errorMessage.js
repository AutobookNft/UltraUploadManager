

// @ts-ignore
if (import.meta.env.MODE === 'development') {
    console.log('Dentro errorMessage');
}

/*
* Questa fetch viene eseguita una volta al caricamento dell'app per recuperare tutti i codici di errore necessari
* e salvarli globalmente nella variabile window.errorCodes. La fetch è esterna alla funzione errorMessage
* perché è più efficiente eseguire una singola richiesta al server piuttosto che richiedere i dati ogni volta
* che viene chiamata la funzione errorMessage.
*/
fetch('/error_codes')
    .then(response => response.json())
    .then(data => {
        window.errorCodes = data;
        // if (import.meta.env.MODE === 'development') {
        //    console.log('Error codes loaded:', window.errorCodes);
        // }
    })
    .catch(error => console.error('Error loading error codes:', error));


/*
 * Questa fetch viene eseguita una volta al caricamento dell'app per recuperare tutte le traduzioni necessarie
 * e salvarle globalmente nella variabile window.translations. La fetch è esterna alla funzione errorMessage
 * perché è più efficiente eseguire una singola richiesta al server piuttosto che richiedere i dati ogni volta
 * che viene chiamata la funzione errorMessage.
 *
 * La funzione errorMessage viene definita solo dopo che la fetch ha completato il caricamento delle traduzioni.
 * In questo modo, ogni volta che si chiama errorMessage, si hanno già le traduzioni caricate in memoria,
 * evitando così richieste aggiuntive e migliorando le prestazioni dell'app.
 *
 * Questo approccio consente di avere i dati pronti globalmente e garantisce che la funzione errorMessage
 * possa accedere immediatamente alle traduzioni senza ritardi causati da ulteriori fetch.
 */
fetch('/translations')
    .then(response => response.json())
    .then(data => {
        window.translations = data;

        // if (import.meta.env.MODE === 'development') {
        //     console.log('window.translations', window.translations.label);
        // }

        // Definisci la funzione errorMessage solo dopo che le traduzioni sono state caricate
        window.errorMessage = function(codeError, params = {}) {
            // const SEND_MAIL = window.sendEmail;
            let userMessage = '';
            let devMessage = '';
            // @ts-ignore
            if (import.meta.env.MODE === 'development') {
                console.log('File: errorMessage. ACTION: codeError', codeError);
            }

            switch (codeError) {
                case 400:
                    userMessage =  'Richiesta non valida';
                    devMessage = 'Richiesta non valida';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError: 400', codeError);
                    }

                    return { userMessage, devMessage };
                case 401:
                    userMessage =  'Non autorizzato';
                    devMessage = 'Non autorizzato';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 401', codeError);
                    }

                    return { userMessage, devMessage };

                case 403:
                    userMessage =  'Vietato';
                    devMessage = 'Vietato';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 403', codeError);
                    }

                    return { userMessage, devMessage };

                case 404:
                    userMessage =  'Non trovato';
                    devMessage = 'Non trovato';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 404', codeError);
                    }

                    return { userMessage, devMessage };

                case 405:
                    userMessage =  'Metodo non consentito';
                    devMessage = 'Metodo non consentito';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 405', codeError);
                    }

                    return { userMessage, devMessage };
                case 500:
                    userMessage =  'Errore interno del server';
                    devMessage = 'Errore interno del server';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 500'), codeError;
                    }

                    return { userMessage, devMessage };
                case 502:
                    userMessage = 'Gateway non valido';
                    devMessage = 'Gateway non valido';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 502', codeError);
                    }

                    return { userMessage, devMessage };

                case 503:

                    userMessage = 'Servizio non disponibile';
                    devMessage = 'Servizio non disponibile';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 503', codeError);
                    }

                    return { userMessage, devMessage };
                case 504:

                    userMessage = 'Gateway Timeout';
                    devMessage = 'Gateway Timeout';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError 504', codeError);
                    }

                    return { userMessage, devMessage };
                case window.errorCodes['MAX_FILE_SIZE']:
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('window.maxSize:', window.maxSize);
                        console.log('window.maxSizeMessage:', window.maxSizeMessage);
                    }

                    window.maxSize = window.maxSize / 1024; // Converte in MB
                    userMessage = replacePlaceholder(window.maxSizeMessage, 'size', window.maxSize);
                    devMessage = 'File troppo grande';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError upload_fallito', codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['INVALID_FILE_EXTENSION']:

                    userMessage = window.translations.errors['file_extension_not_valid'];
                    devMessage = 'Estensione file non valida';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage. Action: " + devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['MIME_TYPE_NOT_ALLOWED']:

                    userMessage = window.translations.errors['mime_type_not_allowed'];
                    devMessage = 'Il tipo MIME del file non è valido';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage. Action: " + devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['INVALID_FILE_NAME']:

                    userMessage = window.translations.errors['invalid_file_name'];
                    devMessage = 'Nome del file non valido';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage. Action: " + devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['ERROR_GETTING_PRESIGNED_URL']:

                    userMessage = window.translations.errors['error_getting_presigned_URL_for_user'];
                    devMessage = `Errore durante il recupero dell\'URL prefirmato`;
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage. Action: " + devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['INVALID_IMAGE_STRUCTURE']:

                    userMessage = window.translations.errors['invalid_image_structure'];
                    devMessage = 'Struttura del file immagine corrotta';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage. Action: " + devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['INVALID_FILE_PDF']:

                    userMessage = window.translations.errors['invalid_pdf_file'];
                    devMessage = 'File PDF non valido';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage. Action: " + devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['ERROR_DELETING_LOCAL_TEMP_FILE']:
                    // Questo errore si verifica quando si tenta di eliminare un file temporaneo locale, ma il file non esiste.

                    // Per questo errore occorre inviare una mail al devTeam.
                    // L'errore si è prodotto nel metodo deleteTempFile() del file UploadController.php
                    devMessage = `Errore durante l\'eliminazione del file temporaneo locale`;
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage.js Action:", devMessage, codeError);
                    }

                    return devMessage;

                case window.errorCodes['ERROR_DELETING_EXT_TEMP_FILE']:
                    // Questo errore si verifica quando si tenta di eliminare un file temporaneo su un hosting esterno, ma il file non esiste.

                    // Per questo errore occorre inviare una mail al devTeam.
                    // L'errore si è prodotto nel metodo deleteTempFile() del file UploadController.php

                    devMessage = `Errore durante l\'eliminazione del file temporaneo su ${window.defaultHostingService}`;
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log("File: errorMessage.js Action:", devMessage, codeError);
                    }

                    return { devMessage };

                case window.errorCodes['SCAN_ERROR']:

                    userMessage =  window.translations.label['scan_error'];
                    // Invio una mail al devteam per informarli che c'è stato un errore durante la scansione di un file
                    devMessage = `Errore durante la scansione di un file`;
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError scan_failed', codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['VIRUS_FOUND']:

                    userMessage =  window.translations.errors['virus_found'];
                    devMessage = `File infetto`;
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError virus_found', codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['ERROR_DURING_FILE_UPLOAD']:

                    userMessage =  window.translations.errors['error_during_file_upload'];
                    devMessage = 'Errore durante l\'upload';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError error_during_file_upload', codeError);
                    }

                    return { userMessage, devMessage };
                case window.errorCodes['UNABLE_TO_SAVE_BOT_FILE']:

                    // Per lo user invio nu messaggio generico
                    userMessage =  window.translations.errors['error_during_file_upload'];
                    devMessage = 'Non è stato possibile salvare entrambe i file nel metodo: saveFileToSpaces(), Class: UploadingFiles';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log( devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['UNABLE_TO_CREATE_DIRECTORY']:

                    /*
                    * Spiegazionbe: se non è possibile creare la cartella, il file locale non viene creato, viene comunque salvato sul disco esterno.
                    * Tipo di errore: Critico
                    * Comunicazione user: nessuna comunicazione.
                    * Comunicazione devTeam: Non è stato possibile creare la cartella durante il salvataggio del file su localhost. Metodo: ensureDirectoryPermissions(), Trait: HasUtility.
                    */
                    userMessage =  'nn';
                    devMessage = 'Non è stato possibile creare la cartella durante il salvataggio del file su localhost. Metodo: ensureDirectoryPermissions(), Trait: HasUtility';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log(devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['UNABLE_TO_CHANGE_PERMISSIONS']:

                    /*
                    * Spiegazione: se non è possibile riassegnare i permessi, il file locale non viene creato, viene comunque salvato sul disco esterno.
                    * Tipo di errore: Critico
                    * Comunicazione user: nessuna comunicazione.
                    * Comunicazione devTeam: Non è stato possibile assegnare i permessi alla cartella durante il salvataggtio del file su localhost. Metodo: ensureDirectoryPermissions(), Trait: HasUtility'.
                    */
                    userMessage =  'nn';
                    devMessage = 'Non è stato possibile assegnare i permessi alla cartella durante il salvataggtio del file su localhost. Metodo: ensureDirectoryPermissions(), Trait: HasUtility';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log(devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                case window.errorCodes['ERROR_DURING_CREATE_EGI_RECORD']:


                    // Per lo user invio un messaggio generico
                    // Per qualche ragione le informazioni non sono state salòvate sul record
                    // Occorre avvisare il devTeam
                    userMessage =  window.translations.errors['error_during_file_upload'];;
                    devMessage = 'Non è stato possibile salvare i dati dell\'EGI. Metodo: createEGIRecord(), Class: UploadingFiles';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log(devMessage, codeError);
                    }

                    return { userMessage, devMessage };

                    case window.errorCodes['ERROR_DURING_FILE_NAME_ENCRYPTION']:

                    // Per lo user invio un messaggio generico
                    // C'è stato un errore nella fase di criptazione del nome del file, occorre verioficare tempestivamente la causa,
                    // Per questo il devTeam deve essere avvisato, infatti questo è un errore critico
                    userMessage =  window.translations.errors['error_during_file_upload'];;
                    devMessage = 'Non è stato possibile salvare i dati dell\'EGI. Metodo: createEGIRecord(), Class: UploadingFiles';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log(devMessage, codeError);
                    }

                    return { userMessage, devMessage };
                case 'upload_file_non_valido':
                    userMessage =  window.translations.label['scan_error'];
                    devMessage = 'Impossibile trovare il file da scansionare';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError upload_file_non_valido', codeError);
                    }

                    return { userMessage, devMessage };

                case 'upload_file_troppo_grande':
                    userMessage =  window.translations.label['scan_error'];
                    devMessage = 'Impossibile trovare il file da scansionare';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError upload_file_troppo_grande', codeError);
                    }

                    return { userMessage, devMessage };

                case 'upload_file_non_caricato':
                    userMessage =  window.translations.label['scan_error'];
                    devMessage = 'Impossibile trovare il file da scansionare';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError upload_file_non_caricato', codeError);
                    }
                    return { userMessage, devMessage };

                default:
                    userMessage =  '';
                    devMessage = 'Errore sconosciuto';
                    // @ts-ignore
                    if (import.meta.env.MODE === 'development') {
                        console.log('File: errorMessage. Action: codeError default', codeError);
                    }

                    return { userMessage, devMessage };
            }
        };
    })
    .catch(error => console.error('Error loading translations:', error));

    /**
     *
     * @param {string} text
     * @param {*} placeholder
     * @param {*} value
     * @returns
     */
    function replacePlaceholder(text, placeholder, value) {
        return text.replace(new RegExp(`:${placeholder}`, 'g'), value);
    }
