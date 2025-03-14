import {
    csrfToken,
    progressBar,
    progressText,
    scanProgressText,
    statusMessage,
    statusDiv,
    scanvirus,
    getFiles,
    handleVirusScan,
    showEmoji,
    disableButtons,
    resetButtons,
    removeEmojy,
    handleImage,
    enableButtons,
    updateStatusDiv,
    updateStatusMessage,
    highlightInfectedImages,
    removeFile,
    removeImg,
    HubFileController
} from '../index';

declare const window: any;

interface FileUploadResult {
    error: any; // Puoi creare un tipo più specifico se conosci la struttura degli errori
    response: Response | false; // Il tipo `Response` per fetch API o `false` in caso di errore
    success: boolean;
}

/**
 * Funzione per caricare un file lato server.
 * @param formData - I dati del form contenenti il file da caricare.
 * @returns Un oggetto contenente l'esito dell'upload, la risposta e gli eventuali errori.
 */
export async function fileForUpload(formData: FormData): Promise<FileUploadResult> {
    let errorData: any = null;
    let success: boolean = true;

    if ((window as any).envMode === 'local') {
        console.log('dentro fileForUpload');
    }

    if ((window as any).envMode === 'local') {
        const file = formData.get('file') as File;
        console.log('in fileForUpload: formData:', file?.name); // Log del Content-Type per verificare il tipo di risposta
    }

    try {
        const response: Response = await fetch('/uploading-files', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (window as any).csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        });

        const contentType = response.headers.get('content-type');
        if ((window as any).envMode === 'local') {
            console.log('Content-Type:', contentType); // Log del Content-Type per verificare il tipo di risposta
        }

        if (!response.ok) {
            if (contentType && contentType.includes('application/json')) {
                errorData = await response.json(); // Ottieni la response dal server in formato JSON
                success = false;
            } else {
                const rawErrorData = await response.text(); // Se non è JSON, ottieni il testo (potrebbe essere HTML)
                errorData = {
                    message: 'Il server ha restituito una risposta non valida o inaspettata.',
                    details: rawErrorData, // Mantiene il contenuto HTML o testo come dettaglio
                    state: 'unknown',
                    errorCode: 'unexpected_response',
                    blocking: 'blocking', // Considera questo un errore bloccante di default
                };
                success = false;
            }

            return { error: errorData, response, success };
        }

        return { error: false, response, success };

    } catch (error) {
        if ((window as any).envMode === 'local') {
            console.error('Error in fileForUpload:', error);
        }

        return { error, response: false, success: false }; // Restituiamo l'errore come parte dell'oggetto
    }
}


/**
 * Funzione che tenta l'upload di un file fino a un massimo di tentativi specificato.
 * Viene utilizzata per gestire il caricamento di un file verso il server,
 * con la possibilità di riprovare l'upload in caso di fallimento fino a
 * maxAttempts volte.
 *
 * @param formData - I dati del file da caricare, inclusi eventuali token CSRF e altri metadati.
 * @param maxAttempts - Il numero massimo di tentativi di upload. Default: 3.
 *
 * @returns Un oggetto contenente:
 *  - success: Indica se l'upload è stato completato con successo.
 *  - response: Contiene il risultato finale dell'upload o l'errore se tutti i tentativi falliscono.
 *
 * Note:
 * - Se l'upload fallisce, viene ripetuto fino a maxAttempts tentativi.
 * - La variabile 'result' è dichiarata all'esterno del ciclo per mantenere
 *   il suo valore finale al termine del ciclo, ed essere restituita correttamente alla funzione chiamante.
 */
export async function attemptFileUpload(formData: FormData, maxAttempts: number = 3): Promise<{ error: any; response: Response | boolean; }> {
    let attempt = 0;
    let success = false;
    let error: any = null;
    let response: Response | boolean = false;

    while (attempt < maxAttempts && !success) {
        attempt++;

        ({ error, response, success } = await fileForUpload(formData));

        if (success) {
            if (window.envMode === 'local') {
                console.log(`Tentativo ${attempt} riuscito.`);
            }
            return { error, response };
        } else {
            if (window.envMode === 'local') {
                console.warn(`Tentativo ${attempt} fallito: ${error.message}`);
            }
        }

        if (!success && attempt < maxAttempts) {
            if (window.envMode === 'local') {
                console.log(`Riprovo il tentativo ${attempt + 1}...`);
            }
        }
    }

    return { error, response };
}


/**
 * Funzione che gestisce l'upload e la scansione dei file.
 */
export async function handleUpload(): Promise<void> {
    console.log('handleUpload called'); // Reintegrato
    console.time('UploadProcess');
    const files = getFiles() || [];
    if (window.envMode === 'local') {
        console.log('📤 Uploading files:', files.length); // Reintegrato
    }

    if (files.length === 0) {
        console.warn('⚠️ Nessun file selezionato.');
        console.timeEnd('UploadProcess');
        return;
    }

    disableButtons();
    statusMessage.innerText = window.startingSaving + '...';

    let incremento = 100 / files.length;
    let flagUploadOk = true;
    let iterFailed = 0;
    let someInfectedFiles = 0;
    let index = 0;
    let userMessage = "";

    for (const file of files) {

        if (window.envMode === 'local') {
            console.log(`📂 Uploading file: ${file.name}`);
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', csrfToken);
        formData.append('index', index.toString());

        const isLastFile = index === files.length - 1;
        formData.append('finished', isLastFile ? 'true' : 'false');

        if (isLastFile) {
            formData.append('iterFailed', iterFailed.toString());
        }

        try {
            if (window.envMode === 'local') {
                console.log(`📂 Uploading file: ${file.name}`);
            }

            scanProgressText.innerText = '';

            if (scanvirus.checked) {
                updateStatusMessage(window.startingScan + '...', 'info');
                formData.append('someInfectedFiles', someInfectedFiles.toString());
                formData.append('fileName', file.name);

                if (await handleVirusScan(formData)) {
                    flagUploadOk = false;
                    iterFailed++;
                    someInfectedFiles++;
                    highlightInfectedImages(file.name);
                    resetProgressBar();
                    continue;
                }
            }

            const uploadType = determineUploadType(getUploadContext());
            console.log(`📌 Smistamento su: ${uploadType}`);

            const hubFileController = new HubFileController();
            const { error, response, success } = await hubFileController.handleFileUpload(file, uploadType);

            if (!success) {
                if (error?.details) {
                    console.error('🚨 Errore non JSON:', error.details);
                    userMessage = error.userMessage || error.message || "Errore sconosciuto";
                    flagUploadOk = false;
                    iterFailed++;
                    updateStatusDiv(userMessage, 'error');
                    if (error.blocking === 'blocking') break;
                } else {
                    console.error('🚨 Errore durante l\'upload:', error);
                    userMessage = error?.userMessage || error?.message || "Errore non specificato";
                    flagUploadOk = false;
                    iterFailed++;
                    updateStatusDiv(userMessage, 'error');
                }
            } else {
                if (window.envMode === 'local') {
                    console.log(`✅ Upload riuscito: ${file.name}`);
                }

                removeImg(file.name);

                if (response instanceof Response) {
                    const resultResponse = await response.json();
                    updateStatusDiv(resultResponse.userMessage || updateFileSavedMessage(file.name), 'success');
                    updateProgressBar(index, incremento);
                }
            }
        } catch (error) {
            flagUploadOk = false;
            if (window.envMode === 'local') {
                console.error(`❌ Catch in handleUpload: ${error}`);
            }

            const uploadError: UploadError = error instanceof Error ? {
                message: error.message,
                userMessage: "Errore imprevisto durante il caricamento.",
                details: error.stack,
                state: "unknown",
                errorCode: "unexpected_error",
                blocking: "blocking"
            } : error;

            if (uploadError.blocking === 'blocking') {
                updateStatusMessage(uploadError.userMessage || "Errore critico durante l'upload", 'error');
                iterFailed = files.length;
                break;
            } else {
                userMessage = uploadError.userMessage || uploadError.message || "Errore durante l'upload";
                updateStatusDiv(`${userMessage} ${window.of} ${file.name}`, 'error');
                updateStatusMessage(userMessage, 'error');
                iterFailed++;
                resetProgressBar();
            }
        }
        index++;
    }

    finalizeUpload(flagUploadOk, iterFailed);
    console.timeEnd('UploadProcess');
}

/**
 * Funzione per determinare il tipo di upload in base al contesto.
 * @param contextType - Il tipo di upload determinato dal contesto (es. endpoint o parametro).
 * @returns Il tipo di upload ('egi', 'epp', 'utility', ecc.).
 */
function determineUploadType(contextType: string): string {
    const validTypes = ['egi', 'epp', 'utility'];
    return validTypes.includes(contextType) ? contextType : 'default';
}

/**
 * Funzione per determinare il contesto di upload dall'URL corrente.
 * @returns Il tipo di contesto ('egi', 'epp', 'utility', ecc.).
 */
function getUploadContext(): string {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/uploading/egi')) return 'egi';
    if (currentPath.includes('/uploading/epp')) return 'epp';
    if (currentPath.includes('/uploading/utility')) return 'utility';
    return 'default'; // Fallback
}

/**
 * Aggiorna la barra di progresso.
 * @param index - Indice del file attualmente in upload
 * @param incremento - Valore da aggiungere alla barra di progresso
 */
function updateProgressBar(index: number, incremento: number): void {
    progressBar.style.width = `${(index + 1) * incremento}%`;
    progressText.innerText = `${Math.round((index + 1) * incremento)}%`;
}

/**
 * Resetta la barra di progresso in caso di errore.
 */
function resetProgressBar(): void {
    progressBar.style.width = "0";
    progressText.innerText = "";
}

/**
 * Funzione per finalizzare l'upload e mostrare i risultati.
 *
 * @param flagUploadOk - Booleano che indica se l'upload complessivo è riuscito.
 * @param iterFailed - Numero di tentativi di upload falliti.
 */
export function finalizeUpload(flagUploadOk: boolean, iterFailed: number): void {
    resetButtons(); // Riabilita i pulsanti alla fine dell'upload

    const files = getFiles() || [];

    if (flagUploadOk && iterFailed === 0) {
        showEmoji('success');
    } else if (!flagUploadOk && iterFailed > 0 && iterFailed < files.length) {
        showEmoji('someError');
    } else if (!flagUploadOk && iterFailed === files.length) {
        showEmoji('completeFailure');
        updateStatusMessage(window.completeFailure, 'error');
    }
}


/**
 * Funzione per aggiornare il messaggio di salvataggio del file.
 *
 * @param nomeFile - Il nome del file salvato correttamente.
 * @returns Il messaggio aggiornato con il nome del file salvato.
 */
export function updateFileSavedMessage(nomeFile: string): string {
    const messageTemplate = window.fileSavedSuccessfullyTemplate;
    return messageTemplate.replace(':fileCaricato', nomeFile);
}

