// file_upload_manager.ts
import {
    getFiles,
    fileInput,
    collection,
    scanProgressText,
    progressBar,
    progressText,
    statusDiv,
    dropZone,
    validateFile,
    validateFilesAgainstLimits,
    prepareFilesForUploadUI,
    setupRealTimeUploadListener,
    updateUploadLimitsDisplay,
    deleteTemporaryFileLocal,
    updateStatusMessage,
    setupDomEventListeners,
    resetButtons,
   
    
} from '../index';

import Swal from 'sweetalert2';

// Dynamic state for files, shared across the module
export let files: File[] = [];

// Flag to prevent duplicate initialization
let isInitialized = false;

/**
 * Initializes the file upload manager application.
 * Sets up event listeners for file selection and real-time upload functionality.
 * Relies on external modal management for opening the upload interface.
 *
 * @oracode.semantically_coherent Clear initialization for upload functionality.
 * @oracode.testable Upload flow is deterministic and mockable.
 * @oracode.neutral No DOM manipulation or authorization logic.
 * @gdpr No personal data is stored beyond temporary upload type.
 */
export function initializeApp() {
    let files = Array.from(getFiles() || []);

    // Wait for global configuration to load
    document.addEventListener('configLoaded', () => {
        if (!isInitialized) {
            files = Array.from(getFiles() || []);
            setupDomEventListeners();
            initializeUI();
            isInitialized = true;
        }
    }, { once: true });

    // Proceed immediately if config is already loaded
    if (window.allowedExtensions && !isInitialized) {
        files = Array.from(getFiles() || []);
        setupDomEventListeners();
        initializeUI();
        isInitialized = true;
    }

    // Set up real-time upload listener
    setupRealTimeUploadListener();

    // Cleanup temporary files before page unload
    window.addEventListener('beforeunload', async (e: BeforeUnloadEvent) => {
        const fileList = getFiles();
        if (fileList?.length) {
            e.preventDefault();
            for (const file of Array.from(fileList)) {
                try {
                    await deleteTemporaryFileLocal(file);
                } catch (error) {
                    console.error(`Error deleting ${file.name} on beforeunload:`, error);
                }
            }
        }
    });
}


/**
 * Initializes UI elements when the DOM is fully loaded.
 * Fetches upload limits, resets UI elements, and logs configuration details in local mode.
 */
function initializeUI() {
    console.time('FileManagerInit');
    if (window.envMode === 'local') console.log('Inside uploading_files');

    fetch('/api/system/upload-limits')
        .then(response => response.json())
        .then(limits => {
            window.uploadLimits = limits;
            updateUploadLimitsDisplay(limits);
        })
        .catch(error => {
            console.error('Failed to fetch upload limits:', error);
            updateUploadLimitsDisplay({
                max_files: 20,
                max_file_size: 10485760,
                max_total_size: 52428800,
                max_file_size_formatted: '10 MB',
                max_total_size_formatted: '50 MB',
            });
        });

    scanProgressText.innerText = '';
    progressBar.style.width = '0';
    progressText.innerText = '';

    if (window.envMode === 'local') {
        console.log('Upload finished successfully!');
        console.log('allowedExtensionsMessage:', window.allowedExtensionsMessage);
    }

    console.timeEnd('FileManagerInit');
}

/**
 * Handles file selection from an input event.
 * Validates the selected files and prepares them for upload if valid.
 * Delays execution if the global configuration is not yet loaded.
 *
 * @param event - The file selection event from an input element.
 */
export function handleFileSelect(event: Event) {
    if (typeof window.envMode === 'undefined') {
        console.warn('Config not yet loaded, delaying handleFileSelect...');
        document.addEventListener('configLoaded', () => handleFileSelect(event), { once: true });
        return;
    }
    console.log('Handling file select...');
    const fileList = getFiles();
    const validi = fileList ? validateFiles(fileList) : null;
    if (validi && validi.length > 0) {
        // I file scartati devono sparire DALL'INPUT, non solo dalla nostra lista.
        //
        // Il caricamento vero (handleUpload) non legge questa lista: legge getFiles(), che
        // restituisce fileInput.files, cioè la lista grezza del documento. Senza questa riga
        // l'utente leggeva «questi sono esclusi, gli altri restano pronti», premeva Carica, e
        // partivano tutti — scartati compresi, respinti poi dal server uno per uno.
        // Rilevato dall'audit di chiusura M-EGI-430: il collaudo guardava la giuntura sbagliata.
        sincronizzaInput(validi);
        files = validi;
        prepareFilesForUploadUI(comeListaDiFile(validi));
    }
}

/**
 * Riscrive l'elenco dell'input con i soli file che possono proseguire.
 *
 * Nel browser si usa `DataTransfer`, l'unico modo previsto per costruire una FileList vera.
 * Dove non esiste — jsdom, e i browser che non lo implementano — si ripiega sulla definizione
 * diretta della proprietà: la forma è la stessa che il resto del codice legge.
 */
function sincronizzaInput(validi: File[]): void {
    if (!fileInput) return;

    try {
        if (typeof DataTransfer !== 'undefined') {
            const dt = new DataTransfer();
            validi.forEach((f) => dt.items.add(f));
            fileInput.files = dt.files;
            return;
        }
    } catch {
        // Alcuni browser rifiutano l'assegnazione: si ripiega qui sotto.
    }

    try {
        Object.defineProperty(fileInput, 'files', {
            value: comeListaDiFile(validi),
            configurable: true,
        });
    } catch {
        // Se nemmeno questo riesce, la lista nostra resta corretta: la barriera vera è il
        // server, che rifiuta i file non validi uno per uno.
        console.warn('Impossibile sincronizzare l\'elenco dei file scelti con i soli validi.');
    }
}

/**
 * Handles file drop events from drag-and-drop interactions.
 * Prevents default behavior, validates dropped files, and prepares them for upload.
 * Delays execution if the global configuration is not yet loaded.
 *
 * @param event - The drag-and-drop event containing file data.
 */
export function handleDrop(event: DragEvent) {
    if (typeof window.envMode === 'undefined') {
        console.warn('Config not yet loaded, delaying handleDrop...');
        document.addEventListener('configLoaded', () => handleDrop(event), { once: true });
        return;
    }
    event.preventDefault();
    const fileList = event.dataTransfer?.files;
    const validi = fileList ? validateFiles(fileList) : null;
    if (validi && validi.length > 0) {
        window.droppedFiles = comeListaDiFile(validi);
        files = validi;
        prepareFilesForUploadUI(comeListaDiFile(validi));
    }
    dropZone.classList.remove('border-blue-400', 'bg-purple-800/40');
}

/**
 * Cancels the current upload process.
 * Confirms with the user, deletes temporary files asynchronously, and resets the UI.
 * Delays execution if the global configuration is not yet loaded.
 */
export async function cancelUpload() {
    if (typeof window.envMode === 'undefined') {
        console.warn('Config not yet loaded, delaying cancelUpload...');
        document.addEventListener('configLoaded', () => cancelUpload(), { once: true });
        return;
    }
    if (confirm('Are you sure you want to cancel the upload?')) {
        const fileList = getFiles();
        if (fileList) {
            for (const file of Array.from(fileList)) {
                try {
                    await deleteTemporaryFileLocal(file);
                } catch (error) {
                    console.error(`Error deleting ${file.name}:`, error);
                }
            }
            resetButtons();
            collection.innerHTML = '';
            progressBar.style.width = '0%';
            progressText.innerText = '';
            updateStatusMessage('Upload Status: Waiting...', 'info');
            statusDiv.innerHTML = '';
        } else {
            console.error('No files to delete');
        }
    }
}

/** Un elenco di File nella forma che il resto del codice si aspetta (length, indici, item). */
function comeListaDiFile(elenco: File[]): FileList {
    const lista: Record<string | number | symbol, unknown> = { ...elenco };
    lista.length = elenco.length;
    lista.item = (i: number) => elenco[i] ?? null;
    lista[Symbol.iterator] = function* () { yield* elenco; };
    return lista as unknown as FileList;
}

/**
 * Sceglie quali file possono proseguire, e DICE quali no.
 *
 * M-EGI-430 — prima questa funzione rispondeva sì/no e taceva. Al primo file non valido
 * faceva `break` e restituiva false: chi chiamava lo leggeva come «nessun file», quindi un
 * solo nome sgradito faceva sparire l'INTERA selezione. E il motivo — che `validateFile`
 * restituisce — finiva soltanto nella console: accanto c'era scritto «validateFile ha già
 * mostrato il suo messaggio», cosa non vera (il popup esce solo per i file HEIC). Chi
 * caricava vedeva i propri file svanire senza una parola.
 *
 * Ora: si guardano TUTTI i file, i validi proseguono, e i rifiutati vengono detti — uno per
 * uno, con il proprio motivo.
 *
 * I limiti della selezione (quanti file, quanto pesano in tutto) restano tutto-o-niente:
 * riguardano l'insieme, non il singolo file, e scartarne alcuni non li rispetterebbe comunque.
 *
 * @param files - I file scelti da chi carica.
 * @returns I file che possono proseguire; `null` quando non è stato possibile valutarli.
 */
function validateFiles(files: FileList | null): File[] | null {
    if (typeof window.envMode === 'undefined' || !window.allowedExtensions || !window.allowedMimeTypes) {
        // DEBITO NOTO (M-EGI-430): anche questo è un silenzio — la configurazione non è
        // ancora arrivata e la selezione va perduta senza che nessuno lo dica. Diverso dal
        // rifiuto, che ora parla: qui i file non sono stati valutati affatto.
        console.warn('Config not yet loaded, delaying validation...');
        document.addEventListener('configLoaded', () => validateFiles(files), { once: true });
        return null;
    }

    if (!files || files.length === 0) {
        console.warn('No files selected for validation');
        return null;
    }

    // Primo controllo: i limiti dell'INSIEME (quanti file, quanto pesano). Tutto-o-niente.
    const limitsValidation = validateFilesAgainstLimits(files);
    if (!limitsValidation.valid) {
        Swal.fire({
            title: 'Upload Limits Exceeded',
            html: limitsValidation.message,
            icon: 'warning',
            confirmButtonText: 'OK',
        });
        return null;
    }

    // Secondo controllo: file per file. Nessun `break`: si guardano tutti.
    const validi: File[] = [];
    const rifiutati: Array<{ nome: string; motivo: string }> = [];

    for (let i = 0; i < files.length; i++) {
        const result = validateFile(files[i]);
        if (result.isValid) {
            validi.push(files[i]);
        } else {
            console.error(`File ${files[i].name} failed validation: ${result.message}`);
            rifiutati.push({ nome: files[i].name, motivo: result.message ?? '' });
        }
    }

    if (rifiutati.length > 0) {
        mostraFileRifiutati(rifiutati, validi.length);
    }

    return validi;
}

/**
 * Dice a chi carica quali file sono stati esclusi e perché, uno per uno.
 *
 * Le parole vengono dalle chiavi già esistenti dell'organo, iniettate in pagina come le altre
 * etichette del form: qui non si scrive nessun testo nuovo. Il nome del file passa da
 * `textContent`, mai concatenato nel markup: un nome è dato di chi carica, e finirebbe
 * altrimenti dentro l'HTML del messaggio (CWE-79).
 */
function mostraFileRifiutati(
    rifiutati: Array<{ nome: string; motivo: string }>,
    quantiRestano: number,
): void {
    const elenco = document.createElement('ul');
    elenco.style.textAlign = 'left';
    elenco.style.margin = '0';
    elenco.style.paddingInlineStart = '1.2em';

    rifiutati.forEach(({ nome, motivo }) => {
        const riga = document.createElement('li');
        const titolo = document.createElement('strong');
        titolo.textContent = nome;
        riga.appendChild(titolo);
        if (motivo) {
            riga.appendChild(document.createTextNode(` — ${motivo}`));
        }
        elenco.appendChild(riga);
    });

    const corpo = document.createElement('div');
    corpo.appendChild(elenco);

    // Quanti file proseguono: se ne restano, chi carica deve sapere che non ha perso tutto.
    if (quantiRestano > 0 && window.filesRemainingMessage) {
        const coda = document.createElement('p');
        coda.style.marginTop = '0.75em';
        coda.textContent = window.filesRemainingMessage.replace(':count', String(quantiRestano));
        corpo.appendChild(coda);
    }

    Swal.fire({
        title: window.errorsInTheFilesTitle || 'Errors in the files',
        html: corpo,
        icon: 'warning',
        confirmButtonText: 'OK',
    });
}
