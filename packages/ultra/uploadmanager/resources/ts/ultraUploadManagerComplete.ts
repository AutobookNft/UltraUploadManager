import { csrfToken } from './utils/domElements';

declare const window: any;

/**
 * Deletes a temporary file from Digital Ocean.
 *
 * @param file - The file to be deleted.
 * @returns A promise that resolves to the response from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function deleteTemporaryFileExt(file: string): Promise<Response> {
    if (window.envMode === 'local') {
        console.log('Inside deleteTemporaryFileExt');
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', csrfToken);

    try {
        const response = await fetch('/delete-temporary-file-DO', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });

        if (!response.ok) {
            const result = await response.json();

            if (window.envMode === 'local') {
                console.log('Inside deleteTemporaryFileExt. Result:', result);
            }

            const error = JSON.stringify(result.error);
            if (window.envMode === 'local') {
                console.log('Inside deleteTemporaryFileExt. Error:', error);
            }

            throw new Error(error);
        }

        return response;

    } catch (error: any) {
        if (window.envMode === 'local') {
            console.error('Error in deleteTemporaryFileDO:', error);
        }
        throw error;
    }
}

/**
 * Deletes a temporary local file.
 *
 * @param file - The file to be deleted.
 * @returns A promise that resolves to the response from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function deleteTemporaryFileLocal(file: File): Promise<Response> {
    if (window.envMode === 'local') {
        console.log('Inside deleteTemporaryFileLocal', file);
    }

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', csrfToken);

    try {
        const response = await fetch('/delete-temporary-file-local', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (window.envMode === 'local') {
            console.info('response:', response);
            console.info('response.status:', response.status);
        }

        if (!response.ok) {
            const result = await response.json();

            if (window.envMode === 'local') {
                console.log('Inside deleteTemporaryFileLocal. Result:', result);
            }

            throw result;
        }

        return response;

    } catch (error: any) {

        if (window.envMode === 'local') {
            console.error('Error in deleteTemporaryFileLocal:', error);
        }

        throw error;
    }
}

// domElements.ts

export const statusMessage = document.getElementById('status-message') as HTMLElement;
export const statusDiv = document.getElementById('status') as HTMLElement;
export const scanProgressText = document.getElementById('scan-progress-text') as HTMLElement;
export const progressBar = document.getElementById('progress-bar') as HTMLElement;
export const progressText = document.getElementById('progress-text') as HTMLElement;
export const uploadFilebtn = document.getElementById('file-label') as HTMLElement;
export const returnToCollectionBtn = document.getElementById('returnToCollection') as HTMLElement;
export const scanvirusLabel = document.getElementById('scanvirus_label') as HTMLElement;
export const scanvirus = document.getElementById('scanvirus') as HTMLInputElement;
export const virusAdvise = document.getElementById('virus-advise') as HTMLElement;
export const circleLoader = document.getElementById('circle-loader') as HTMLElement;
export const circleContainer = document.getElementById('circle-container') as HTMLElement;
export const uploadBtn = document.getElementById('uploadBtn') as HTMLButtonElement;
export const cancelUploadBtn = document.getElementById('cancelUpload') as HTMLButtonElement;
export const emojiElements = document.querySelectorAll('.emoji') as NodeListOf<HTMLElement>;
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (!csrfMeta) {
    throw new Error("Meta tag with csrf-token not found");
}
export const csrfToken = csrfMeta.getAttribute('content') as string;
export const collection = document.getElementById('collection') as HTMLElement;


export function getFiles(): FileList | null {
    if (!document.getElementById('files')) {
        return null
    }
    return (document.getElementById('files') as HTMLInputElement).files;
}
import { handleUpload } from './core/uploading';
import { validateFile } from './utils/validation';
import { showEmoji } from './utils/showEmoji';
import { prepareFilesForUploadUI } from './utils/prepareFilesForUploadUI';
import {
    scanProgressText,
    progressBar,
    progressText,
    getFiles,
    scanvirusLabel,
    scanvirus,
    virusAdvise,
    statusDiv,
    statusMessage,
} from './utils/domElements';

import {
    resetButtons,
    handleImage,
    removeFile,
    removeImg
} from './uploadUtils';

import { setupRealTimeUploadListener } from './utils/realTimeUploadListener';

declare const window: any;

/**
 * @param files - Lista di file da validare
 * @returns Restituisce true se tutti i file sono validi, altrimenti false
 */
function validation(files: FileList | null): boolean {
    if (!files) {
        return false;
    }

    for (let i = 0; i < files.length; i++) {
        const validationResult = validateFile(files[i]);
        if (!validationResult.isValid) {
            console.error(`File ${files[i].name} did not pass the validation checks: ${validationResult.message}`);
            return false; // Se il file non è valido, esce dalla funzione
        }
    }
    return true; // Se tutti i file sono validi, restituisce true
}
console.log('Dentro file_upload_manager');
console.time('handleUploadSetup');
document.addEventListener('DOMContentLoaded', function () {

    window.showEmoji = showEmoji;
    window.handleImage = handleImage;
    window.removeFile = removeFile;
    window.handleFileSelect = handleFileSelect;
    window.handleUpload = handleUpload;
    window.handleDrop = handleDrop;
    window.redirectToCollection = redirectToCollection;
    window.resetButtons = resetButtons;
    window.cancelUpload = cancelUpload;

    console.log('allowedExtensions', window.allowedExtensions);
    console.log('allowedMimeTypes', window.allowedMimeTypes);
    console.log('envMode', window.envMode);

    if (window.envMode === 'local') {

        console.log('allowedExtensions', window.allowedExtensions);
        console.log('allowedMimeTypes', window.allowedMimeTypes);
        console.log('maxSize', window.maxSize);

        console.log('Dentro file_upload_manager');
    }

    if (window.envMode === 'local') {
        console.log('Send email:', window.sendEmail);
    }

    scanProgressText.innerText = "";

    // Chiama Ascoltatore per la gestione delle notifiche in tempo reale
    setupRealTimeUploadListener();

    progressBar.style.width = "0";
    progressText.innerText = "";

    if (window.envMode === 'local') {
        console.log(window.uploadFiniscedText);
        console.log('allowedExtensionsMessage:', window.allowedExtensionsMessage);
        console.log('allowedExtensionsListMessage:', window.allowedExtensionsListMessage);
        console.log('allowedMimeTypesMessage:', window.allowedMimeTypesMessage);
    }

    // Gestione dell'attivazione della scansione antivirus
    scanvirus.addEventListener('click', function () {
        if (scanvirus.checked) {
            scanvirusLabel.classList.remove('text-red-500');
            scanvirusLabel.classList.add('text-green-500');
            scanvirusLabel.innerText = window.enableVirusScanning;
            virusAdvise.style.display = 'block';
            virusAdvise.classList.add('text-red-500');
            virusAdvise.innerText = window.virusScanAdvise;
        } else {
            scanvirusLabel.classList.remove('text-green-500');
            scanvirusLabel.classList.add('text-red-500');
            scanvirusLabel.innerText = window.disableVirusScanning;
            virusAdvise.style.display = 'none';
        }
    });

    // Funzione per la redirezione verso la collezione dopo l'upload
    function redirectToCollection(): void {
        // console.log('Redirecting to collection...', window.URLRedirectToCollection);
        // alert(window.redirectToCollection);
        window.location.href = window.URLRedirectToCollection;
    }

    // Funzione che gestisce la selezione dei file
    async function handleFileSelect(event: Event): Promise<void> {

        console.log('Handling file select...');


        const files = getFiles();
        if (validation(files)) {
            await prepareFilesForUploadUI(files!);
        }
    }

    // Funzione che gestisce il drag & drop dei file
    async function handleDrop(event: DragEvent): Promise<void> {
        event.preventDefault();
        const files = event.dataTransfer?.files;
        if (files && validation(files)) {
            await prepareFilesForUploadUI(files);
        }
    }

    // Funzione per annullare l'upload
    async function cancelUpload(): Promise<void> {

        let shouldBlockUnload = false;

        const files = getFiles();
        if (files) {
            for (const file of files) {
                if (file && file.name) {
                    removeImg(file.name);
                }
            }
        }

        try {
            const success = await deleteTemporaryFolder();
            if (!success) {
                shouldBlockUnload = true;
            }
        } catch (error) {
            shouldBlockUnload = true;
        }

        resetButtons();
        document.getElementById('collection')!.innerHTML = '';
        progressBar.style.width = '0%';
        progressText.innerText = '';
        statusMessage.innerText = 'Upload Status: In attesa...';
        statusDiv.innerHTML = '';
    }

    console.log('Adding event listeners...');
    console.timeEnd('handleUploadSetup');

});

window.addEventListener('beforeunload', async function (e: BeforeUnloadEvent) {
    let shouldBlockUnload = false;

    try {
        const success = await deleteTemporaryFolder();
        if (!success) {
            shouldBlockUnload = true;
        }
    } catch (error) {
        shouldBlockUnload = true;
    }

    if (shouldBlockUnload) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Funzione per eliminare la cartella temporanea sul server
async function deleteTemporaryFolder(): Promise<boolean> {
    const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement).getAttribute('content');
    try {
        const response = await fetch('/delete-temporary-folder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify({
                folderName: window.temporaryFolder
            })
        });

        if (!response.ok) {
            throw new Error('Failed to delete temporary folder');
        }

        console.log('Temporary folder deleted successfully');
        return true;
    } catch (error) {
        console.error('Error deleting temporary folder:', error);
        return false;
    }
}


import { handleUpload } from './core/uploading';
import { validateFile } from './utils/validation';
import { showEmoji } from './utils/showEmoji';
import { prepareFilesForUploadUI } from './utils/prepareFilesForUploadUI';
import {
    scanProgressText,
    progressBar,
    progressText,
    getFiles,
    scanvirusLabel,
    scanvirus,
    virusAdvise,
    statusDiv,
    statusMessage,
} from './utils/domElements';

import {
    resetButtons,
    handleImage,
    removeFile,
    removeImg,
} from './uploadUtils';

import { setupRealTimeUploadListener } from './utils/realTimeUploadListener';
import { deleteTemporaryFileLocal } from './deleteTemporaryFiles';

declare const window: any;

// Binding globali
window.showEmoji = showEmoji;
window.handleImage = handleImage;
window.removeFile = removeFile;
window.handleFileSelect = function (event: Event) {
    if (typeof window.envMode === 'undefined') {
        console.warn('Config non ancora caricata, ritardando handleFileSelect...');
        document.addEventListener('configLoaded', () => window.handleFileSelect(event), { once: true });
        return;
    }
    console.log('Handling file select...');
    const files = getFiles();
    if (validation(files)) prepareFilesForUploadUI(files!);
};
window.handleUpload = handleUpload;
window.handleDrop = function (event: DragEvent) {
    if (typeof window.envMode === 'undefined') {
        console.warn('Config non ancora caricata, ritardando handleDrop...');
        document.addEventListener('configLoaded', () => window.handleDrop(event), { once: true });
        return;
    }
    event.preventDefault();
    const files = event.dataTransfer?.files;
    if (files && validation(files)) prepareFilesForUploadUI(files);
};
window.redirectToCollection = function () {
    if (typeof window.URLRedirectToCollection === 'undefined') {
        console.warn('Config non ancora caricata, ritardando redirectToCollection...');
        document.addEventListener('configLoaded', window.redirectToCollection, { once: true });
        return;
    }
    window.location.href = window.URLRedirectToCollection;
};
window.resetButtons = resetButtons;
window.cancelUpload = async function () {
    if (typeof window.envMode === 'undefined') {
        console.warn('Config non ancora caricata, ritardando cancelUpload...');
        document.addEventListener('configLoaded', () => window.cancelUpload(), { once: true });
        return;
    }
    if (confirm('Vuoi cancellare?')) {
        const files = getFiles();
        if (files) {
            for (const file of files) {
                try {
                    await deleteTemporaryFileLocal(file);
                } catch (error) {
                    console.error(`Errore nella cancellazione di ${file.name}:`, error);
                }
            }
            resetButtons();
            document.getElementById('collection')!.innerHTML = '';
            progressBar.style.width = '0%';
            progressText.innerText = '';
            statusMessage.innerText = 'Upload Status: In attesa...';
            statusDiv.innerHTML = '';
        } else {
            console.error('Nessun file da cancellare');
        }
    }
};

function validation(files: FileList | null): boolean {
    if (typeof window.allowedExtensions === 'undefined') {
        console.warn('allowedExtensions non definito, ritardando validazione...');
        return false; // Ritarda finché non è definito
    }
    if (!files) return false;
    for (let i = 0; i < files.length; i++) {
        const result = validateFile(files[i]);
        if (!result.isValid) {
            console.error(`File ${files[i].name} failed validation: ${result.message}`);
            return false;
        }
    }
    return true;
}

document.addEventListener('configLoaded', () => {
    console.time('FileManagerInit');
    if (window.envMode === 'local') console.log('Dentro uploading_files');

    scanProgressText.innerText = '';
    progressBar.style.width = '0';
    progressText.innerText = '';

    if (window.envMode === 'local') {
        console.log(window.uploadFiniscedText);
        console.log('allowedExtensionsMessage:', window.allowedExtensionsMessage);
    }

    scanvirus.addEventListener('click', function () {
        if (scanvirus.checked) {
            scanvirusLabel.classList.remove('text-red-500');
            scanvirusLabel.classList.add('text-green-500');
            scanvirusLabel.innerText = window.enableVirusScanning;
            virusAdvise.style.display = 'block';
            virusAdvise.classList.add('text-red-500');
            virusAdvise.innerText = window.virusScanAdvise;
        } else {
            scanvirusLabel.classList.remove('text-green-500');
            scanvirusLabel.classList.add('text-red-500');
            scanvirusLabel.innerText = window.disableVirusScanning;
            virusAdvise.style.display = 'none';
        }
    });

    setupRealTimeUploadListener();
    document.getElementById('uploadBtn')?.addEventListener('click', handleUpload);
    console.timeEnd('FileManagerInit');
});

window.addEventListener('beforeunload', async (e: BeforeUnloadEvent) => {
    const files = getFiles();
    if (files?.length) {
        e.preventDefault();
        e.returnValue = '';
        for (const file of files) {
            try {
                await deleteTemporaryFileLocal(file);
            } catch (error) {
                console.error(`Errore nella cancellazione di ${file.name} al beforeunload:`, error);
            }
        }
    }
});
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
        console.log('in fileForUpload: formData:', formData.get('file')?.name); // Log del Content-Type per verificare il tipo di risposta
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
// resources/ts/global.d.ts

// declare let files: FileList;

interface Window {

    errorDelTempLocalFileCode: string;
    titleInvalidFileNameMessage: string;
    maxSizeMessage: string;
    allowedExtensions: string[];
    allowedExtensionsMessage: string;
    allowedMimeTypes: string[];
    allowedMimeTypesMessage: string;
    allowedMimeTypesListMessage: string;
    allowedExtensionsListMessage: string;
    uploadFiniscedText: string;
    maxSize: number;
    invalidFileNameMessage: string;
    titleExtensionNotAllowedMessage: string;
    titleFileTypeNotAllowedMessage: string;
    titleFileSizeExceedsMessage: string;
    envMode: string;
    virusScanAdvise: string;
    enableVirusScanning: string;
    disableVirusScanning: string;
    btnDel: string;
    deleteFileError: string;
    emogyHappy: string;
    emogySad: string;
    emogyAngry: string;
    scanInterval: number;
    someInfectedFiles: string;
    someError: string;
    completeFailure: string;
    success: string;
    startingUpload: string;
    translations: any;
    criticalErrors: any;
    nonBlockingErrors: any;
    errorCodes: any;
    defaultHostingService: string;
    uploadAndScanText: string;
    loading: string;
    redirectToCollection: string;
    currentView: string
    temporaryFolder: string;

    onerror: OnErrorEventHandler;
    customOnError: (devMessage: string, codeError?: number, stack?: string | null) => Promise<boolean>;
    extractErrorDetails: (errorStack: string | undefined, codeError: number) => any;
    checkErrorStatus: (endpoint: string, jsonKey: string, codeError: number) => Promise<boolean | null>;

}

interface UploadError {
    message: string;
    userMessage?: string;
    details?: string;
    state?: string;
    errorCode?: string;
    blocking?: "blocking" | "not";
}

interface ErrorWithCode extends Error {
    code?: number;
}

// Dichiarazioni per le funzioni esterne utilizzate in onerror
declare function sendErrorToServer(errorDetails: any): Promise<void>;
declare function logNonBlockingError(errorDetails: any): void;

declare module 'sweetalert2';
import { EGIUploadHandler } from "./handlers/EGIUploadHandler";
import { EPPUploadHandler } from "./handlers/EPPUploadHandler";
import { UtilityUploadHandler } from "./handlers/UtilityUploadHandler";
import { BaseUploadHandler } from "./handlers/BaseUploadHandler";

export class HubFileController {
    private handlers: { [key: string]: BaseUploadHandler };
    private endpoints: { [key: string]: string };

    constructor() {
        this.handlers = {
            'egi': new EGIUploadHandler(),
            'epp': new EPPUploadHandler(),
            'utility': new UtilityUploadHandler(),
            'default': new BaseUploadHandler(),
        };
        // Mappa i tipi di upload agli endpoint
        this.endpoints = {
            'egi': '/uploading/egi',
            'epp': '/uploading/epp',
            'utility': '/uploading/utility',
            'default': '/uploading/default', // Fallback
        };
    }

    /**
     * Gestisce l'upload di un file, indirizzandolo all'handler corretto.
     * @param file - Il file da caricare
     * @param uploadType - Il tipo di upload (egi, epp, utility, default)
     * @returns Un oggetto con `error`, `response` e `success`
     */
    async handleFileUpload(file: File, uploadType: string): Promise<{ error: UploadError | null; response: Response | boolean; success: boolean }> {
        const handler = this.handlers[uploadType] || this.handlers['default'];
        const endpoint = this.endpoints[uploadType] || this.endpoints['default'];

        console.log(`Handling file upload with handler: ${handler.constructor.name} to endpoint: ${endpoint}`);

        try {
            const { error, response, success } = await handler.handleUpload(file, endpoint);
            return { error, response, success };
        } catch (error) {
            const errorData: UploadError = {
                message: "Errore durante l'elaborazione dell'upload",
                details: error instanceof Error ? error.message : String(error),
                state: "handler",
                errorCode: "handler_error",
                blocking: "blocking",
            };
            return { error: errorData, response: false, success: false };
        }
    }
}
/**
 * Function to handle the UI updates related to file upload preparation.
 * This function handles tasks such as displaying status messages, updating progress bars, creating image previews,
 * and managing the visibility of delete buttons for each file. It does not perform the actual file upload.
 *
 * @param {FileList} files - List of files to be prepared for upload.
 * @returns {Promise<void>} - An asynchronous function that performs the UI updates for the file preparation.
 */

interface Window {
    startingUpload: string;
    loading: string;
    uploadFiniscedText: string;
    uploadAndScanText: string;
    scanvirus: { checked: boolean };
    envMode: string;
}

import {
    statusMessage,
    statusDiv,
    scanProgressText,
    progressBar,
    uploadBtn,
    uploadFilebtn,
    returnToCollectionBtn,
    cancelUploadBtn,
    scanvirusLabel,
    scanvirus,
    virusAdvise,
    circleLoader,
    circleContainer
} from './utils/domElements';

import {
    disableButtons,
    enableButtons,
    resetButtons,
    removeEmojy,
    handleImage,
    updateStatusDiv,
    updateStatusMessage
} from './uploadUtils';

const fileNames: string[] = [];

export async function prepareFilesForUploadUI(files: FileList): Promise<void> {

    let incremento: number = 0;

    // Update status messages and initialize UI components
    statusMessage.innerText = window.startingUpload + '...';
    statusDiv.innerHTML = '';
    scanProgressText.innerText = '';

    // Calculate the progress increment per file
    incremento = 100 / files.length;
    disableButtons();
    removeEmojy();

    // Iterate over each file in the FileList and handle its preparation process
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        fileNames[i] = file.name;
        progressBar.style.width = '0%';

        try {
            // Update the status message during the preparation process
            statusMessage.innerText = window.loading + '...';

            // Create an image preview
            handleImage(i, { target: { result: URL.createObjectURL(file) } }, files);

            // Make delete buttons visible for each file
            for (let j = 0; j < files.length; j++) {
                const fileName = files[j].name;
                const deleteButton = document.getElementById(`button-${fileName}`) as HTMLElement | null;
                if (deleteButton) {
                    deleteButton.classList.remove('hidden');
                }
            }

            // Enable buttons after the preparation process
            enableButtons();
            console.log('Preparation completed', window.uploadFiniscedText);

            // Update the status message based on whether the virus scan is checked
            if (scanvirus.checked) {
                statusMessage.innerText = window.uploadAndScanText;
            } else {
                statusMessage.innerText = window.uploadFiniscedText;
            }

        } catch (result: any) {
            // Handle errors during the preparation process
            let userMessage: string = result.userMessage;

            if (window.envMode === 'local') {
                console.log('getPresignedUrl error catch');
                console.log('userMessage', userMessage);
            }
            updateStatusDiv(`${file.name}: ${userMessage}`, 'error');
            updateStatusMessage(userMessage, 'error');
        }
    }
}
// Real-time listener for handling upload notifications via Laravel Echo.

// import Echo from 'laravel-echo';
import { updateStatusMessage } from './uploadUtils';

declare const window: any;

type UploadEvent = {
    state: string;
    message: string;
    user_id: number;
    progress: number;
};

/**
 * Sets up a real-time listener using Laravel Echo to handle different states of the file upload and processing.
 * Updates UI elements based on the type of event received.
 */
export function setupRealTimeUploadListener(): void {
    window.Echo.private('upload')
        .listen('FileProcessingUpload', (e: UploadEvent) => {
            switch (e.state) {
                case 'processSingleFileCompleted':
                    logEvent(e, 'success');
                    updateStatusMessage(e.message, 'success');
                    clearInterval(window.scanInterval);
                    break;
                case 'allFileSaved':
                    logEvent(e, 'success');
                    updateStatusMessage(e.message, 'success');
                    clearInterval(window.scanInterval);
                    break;
                case 'uploadFailed':
                    logEvent(e, 'error');
                    updateStatusMessage(e.message, 'error');
                    clearInterval(window.scanInterval);
                    break;
                case 'finishedWithSameError':
                    logEvent(e, 'warning');
                    updateStatusMessage(e.message, 'warning');
                    clearInterval(window.scanInterval);
                    document.getElementById('circle-container')!.style.display = 'none';
                    break;
                case 'allFileScannedNotInfected':
                    logEvent(e, 'success');
                    updateStatusMessage(e.message, 'success');
                    document.getElementById('circle-container')!.style.display = 'none';
                    break;
                case 'allFileScannedSomeInfected':
                    logEvent(e, 'warning');
                    updateStatusMessage(e.message, 'warning');
                    document.getElementById('circle-container')!.style.display = 'none';
                    break;
                case 'scanndeSameError':
                    logEvent(e, 'error');
                    updateStatusMessage(e.message, 'error');
                    document.getElementById('circle-container')!.style.display = 'none';
                    break;
                case 'loadingProceedWithSaving':
                    logEvent(e, 'error');
                    updateStatusMessage(e.message, 'error');
                    break;
                case 'virusScan':
                    logEvent(e, 'info');
                    document.getElementById('circle-container')!.style.display = 'block';
                    document.getElementById('status-message')!.innerText = e.message;
                    break;
                case 'endVirusScan':
                    logEvent(e, 'info');
                    document.getElementById('circle-container')!.style.display = 'none';
                    document.getElementById('status-message')!.innerText = e.message;
                    break;
                case 'validation':
                    logEvent(e, 'info');
                    updateStatusMessage(e.message, 'info');
                    break;
                case 'tempFileDeleted':
                    logEvent(e, 'info');
                    break;
                case 'error':
                    logEvent(e, 'error');
                    break;
                case 'infected':
                    logEvent(e, 'error');
                    updateStatusMessage(e.message, 'error');
                    clearInterval(window.scanInterval);
                    document.getElementById('circle-loader')!.style.background = `conic-gradient(#ff0000 100%, #ddd 0%)`;
                    document.getElementById('scan-progress-text')!.innerText = window.someInfectedFiles;
                    document.getElementById('circle-container')!.style.display = 'none';
                    break;
                case 'info':
                    logEvent(e, 'info');
                    updateStatusMessage(e.message, 'info');
                    break;
                default:
                    logEvent(e, 'info');
                    break;
            }
        });
}

/**
 * Logs events to the console if running in local environment mode.
 *
 * @param e The event object received from Laravel Echo.
 * @param type The type of the log (e.g., success, error, info).
 */
function logEvent(e: UploadEvent, type: string): void {
    if (window.envMode === 'local') {
        console.log(`Event Type: ${type}`, e);
        console.log(`Message: ${e.message}`);
    }
}
import {
    csrfToken
} from './utils/domElements';

declare const window: any;

type FetchResponse = Response;

/**
 * Saves a temporary file locally by making a POST request to the server.
 *
 * @param formData - The form data containing the file to upload.
 * @returns A promise that resolves to the response from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function saveLocalTempFile(formData: FormData): Promise<FetchResponse> {
    if (window.envMode === 'local') {
        console.log('Inside saveLocalTempFile');
    }

    try {
        const response = await fetch('/upload-temp', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (window.envMode === 'local') {
            console.log('Inside saveLocalTempFile. Response:', response);
        }

        if (!response.ok) {
            const result = await response.json();

            if (window.envMode === 'local') {
                console.log('Inside saveLocalTempFile. Result:', result);
            }

            const error = JSON.stringify(result.error);
            if (window.envMode === 'local') {
                console.log('Inside saveLocalTempFile. Error:', error);
            }

            throw new Error(error);
        }

        return response;

    } catch (error: any) {

        if (window.envMode === 'local') {
            console.error('Error in saveLocalTempFile:', error);
        }
        throw error;

    }
}
import { csrfToken, progressBar, progressText } from './utils/domElements';
import { saveLocalTempFile } from './saveLocalTempFile';
import { deleteTemporaryFileExt, deleteTemporaryFileLocal } from './deleteTemporaryFiles';
import { updateStatusDiv } from './uploadUtils';

declare const window: any;

type ScanFileResponse = {
    response: Response;
    data: any;
};

/**
 * Performs a virus scan on a file with a progress feedback simulation.
 *
 * @param formData - The form data containing the file to be scanned.
 * @returns A promise that resolves to the response and data from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function scanFileWithProgress(formData: FormData): Promise<ScanFileResponse> {
    if (window.envMode === 'local') {
        console.log('Inside scanFileWithProgress');
    }

    let progress = 0;
    const realScanDuration = 35000; // Simulated real scan duration of 35 seconds
    const startTime = Date.now();

    // Function to simulate progress up to 95%
    function simulateProgress(): void {
        const elapsedTime = Date.now() - startTime;
        progress = Math.min(95, (elapsedTime / realScanDuration) * 100);
        progressBar.style.width = `${progress}%`;
        progressText.innerText = `${Math.round(progress)}%`;

        if (progress >= 95) {
            clearInterval(interval);
        }
    }

    const interval = setInterval(simulateProgress, 100);

    if (window.envMode === 'local') {
        console.log('In scanFileWithProgress: formData:', formData); // Log the Content-Type to verify the type of response
    }

    try {
        const response = await fetch('/scan-virus', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        clearInterval(interval);

        if (response.ok) {
            const finalProgressInterval = setInterval(() => {
                progress += 0.5;
                progressBar.style.width = `${progress}%`;
                progressText.innerText = `${Math.round(progress)}%`;

                if (progress >= 100) {
                    clearInterval(finalProgressInterval);
                }
            }, 50);

            const data = await response.json();

            if (data) {
                return { response, data };
            } else {
                throw new Error('The JSON response is incorrect');
            }
        } else {
            const data = await response.json();
            return { response, data };
        }
    } catch (error: any) {
        clearInterval(interval);
        // throw new Error(`Error during file scan: ${error.message}`);
        throw error;
    }
}

/**
 * Handles the antivirus scan for the given file, saving it temporarily before scanning.
 *
 * @param formData - The form data containing the file to be scanned.
 * @returns A promise that resolves to a boolean indicating if the file is infected.
 * @throws An error if the request fails or the response is not ok.
 */
export async function handleVirusScan(formData: FormData): Promise<boolean> {
    await saveLocalTempFile(formData);

    // Scan the file and manage the progress bar
    const { response, data } = await scanFileWithProgress(formData);

    await deleteTemporaryFileLocal(formData.get('file') as File);

    if (window.envMode === 'local') {
        console.log(`response: ${response}`);
        console.log(`data: ${JSON.stringify(data)}`);
    }

    if (!response.ok) {
        // The file is infected
        if (response.status === 422) {
            updateStatusDiv(data.userMessage, 'error');
            return true; // Virus found
        }

        // Throw an error if the response is not OK, with all the data from the response
        throw data;
    }

    // File is not infected
    return false;
}
/**
 * Function to display an emoji based on the type provided.
 * This function dynamically creates a div element containing an image that represents a specific state.
 * The image source is first attempted to load from a CDN; if it fails, a fallback URL is used.
 * If the image takes longer than 5 seconds to load, a text "OK" is displayed instead of the image.
 * The function handles three types of states: success, someError, and completeFailure.
 *
 * @param {string} type - The type of emoji to display. It can be "success", "someError", or "completeFailure".
 * @throws Will throw an error if the type provided is not valid.
 */
export async function showEmoji(type: string): Promise<void> {
    const div: HTMLDivElement = document.createElement('div');
    const statusDiv: HTMLElement = document.getElementById('status') as HTMLElement;
    div.classList.add('relative', 'group');

    let emojyPng: string = '';
    let fallbackUrl: string = '';
    let altType: string = '';
    let result: string = '';

    if (type === "success") {
        altType = (window as any).emogyHappy;
        result = "OK";
        emojyPng = "https://cdn.nftflorence.com/assets/images/icons/GirlHappy.png";
        fallbackUrl = "https://frangettediskspace.fra1.digitaloceanspaces.com/assets/images/icons/GirlHappy.png";
    } else if (type === "someError") {
        altType = (window as any).emogySad;
        result = window.someError;
        emojyPng = "https://cdn.nftflorence.com/assets/images/icons/GirlDisp.png";
        fallbackUrl = "https://frangettediskspace.fra1.digitaloceanspaces.com/assets/images/icons/GirlDisp.png";
    } else if (type === "completeFailure") {
        altType = (window as any).emogyAngry;
        result = window.completeFailure;
        emojyPng = "https://cdn.nftflorence.com/assets/images/icons/GirlSad.png";
        fallbackUrl = "https://frangettediskspace.fra1.digitaloceanspaces.com/assets/images/icons/GirlSad.png";
    } else {
        throw new Error('Tipo non valido');
    }

    const timeoutId: number = window.setTimeout(() => {
        div.innerHTML = `
            <div class="flex items-center justify-center mt-4">
                <span class="font-bold text-4xl">${result}</span>
            </div>
        `;
        statusDiv.appendChild(div);
    }, 3000);

    try {
        const response: Response = await fetch(emojyPng, { method: 'HEAD' });
        if (!response.ok) {
            emojyPng = fallbackUrl;
        }
    } catch (error) {
        emojyPng = fallbackUrl;
    }

    div.innerHTML = `
        <div class="flex items-center justify-center mt-4">
            <img src="${emojyPng}"
            alt="${altType}"
            id="emojy"
            title="${altType}"
            class="w-40 h-40 object-cover rounded-full shadow-md transition-all duration-300 group-hover:scale-105 z-0">
        </div>
    `;

    statusDiv.appendChild(div);
    clearTimeout(timeoutId);
}
import { BaseUploadHandler } from './handlers/BaseUploadHandler';

export class UltraUploadManager {
    private handler: BaseUploadHandler;

    constructor() {
        this.handler = new BaseUploadHandler(); // Usa l'handler di default
    }

    async upload(file: File): Promise<void> {
        await this.handler.handleUpload(file);
    }
}
import { csrfToken, progressBar, progressText, scanProgressText, statusMessage, statusDiv, scanvirus, getFiles } from './utils/domElements';
import { handleVirusScan } from './utils/scanFile';
import { showEmoji } from './utils/showEmoji';
import { HubFileController } from './core/hubFileController';

import {
    disableButtons,
    resetButtons,
    removeEmojy,
    handleImage,
    enableButtons,
    updateStatusDiv,
    updateStatusMessage,
    highlightInfectedImages,
    removeFile,
    removeImg
} from './uploadUtils';

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

/**
 * Helper functions for handling UI operations related to the upload process.
 * These functions include disabling/enabling buttons, removing emojis, updating status messages,
 * and handling image previews.
 */

import {
    statusMessage,
    statusDiv,
    getFiles,
    uploadBtn,
    uploadFilebtn,
    returnToCollectionBtn,
    cancelUploadBtn,
    emojiElements,
    collection

} from './utils/domElements';

const files = getFiles() || [];


export function disableButtons(): void {

    for (let i = 0; i < files.length; i++) {
        const delFileBtn = document.getElementById(`button-${files[i].name}`);
        if (delFileBtn) {
            delFileBtn.style.display = 'none';
        }
    }

    uploadFilebtn.style.display = 'none';
    uploadBtn.style.display = 'none';
    returnToCollectionBtn.style.display = 'none';
    cancelUploadBtn.style.display = 'none';
}

export function enableButtons(): void {

    for (let i = 0; i < files.length; i++) {
        const delFileBtn = document.getElementById(`button-${files[i].name}`);
        if (delFileBtn) {
            delFileBtn.style.display = 'inline-block';
        }
    }

    cancelUploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    uploadBtn.style.display = 'inline-block';
    returnToCollectionBtn.style.display = 'inline-block';
    cancelUploadBtn.style.display = 'inline-block';
    uploadBtn.disabled = false;
    uploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    cancelUploadBtn.disabled = false;
}

export function resetButtons(): void {

    for (let i = 0; i < files.length; i++) {
        const delFileBtn = document.getElementById(`button-${files[i].name}`);
        if (delFileBtn) {
            delFileBtn.style.display = 'inline-block';
        }
    }

    uploadFilebtn.style.display = 'inline-block';
    uploadBtn.style.display = 'inline-block';
    uploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
    uploadBtn.disabled = true;
    cancelUploadBtn.style.display = 'inline-block';
    cancelUploadBtn.disabled = true;
    cancelUploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
    returnToCollectionBtn.style.display = 'inline-block';
    removeEmojy();
}

export function removeEmojy(): void {
    emojiElements.forEach((emoji) => {
        emoji.remove();
    });
}

export function handleImage(index: number, event: { target: { result: string } }, files: FileList): void {
    const div = document.createElement('div');
    div.classList.add('relative', 'group');
    (div as any).index = index;

    div.innerHTML = `
        <div class="relative group" id="file-${files[index].name}">
        <img src="${event.target.result}" alt="File Image" class="w-full h-40 object-cover rounded-lg shadow-md transition-all duration-300 group-hover:scale-105 z-0">
        <button type="button" id="button-${files[index].name}" onclick="removeFile('${files[index].name}')" class="bg-red-500 text-white absolute bottom-4 px-4 rounded-full text-sm hover:bg-red-700 z-10 hidden">
            ${window.btnDel}
        </button>
        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg">
            <p class="text-white text-sm">File ${files[index].name}</p>
        </div>
    </div>`;
    collection.appendChild(div);

    if (window.envMode === 'local') {
        console.log('File added:', files[index].name);
    }
}
export function updateStatusDiv(message: string, type: string = 'info'): void {
    let colorClass = '';
    let backgroundClass = '';

    switch (type) {
        case 'error':
            colorClass = 'text-red-700';
            backgroundClass = 'bg-red-200';
            break;
        case 'success':
            colorClass = 'text-green-700';
            backgroundClass = 'bg-green-200';
            break;
        case 'info':
            colorClass = 'text-blue-700';
            backgroundClass = 'bg-blue-200';
            break;
        case 'warning':
            colorClass = 'text-yellow-700';
            backgroundClass = 'bg-yellow-200';
            break;
        default:
            colorClass = 'text-blue-700';
            backgroundClass = 'bg-blue-200';
    }

    if (!message.includes("nn")) {
        statusDiv.innerHTML += `
            <p class="font-bold ${colorClass} ${backgroundClass} px-4 py-2 rounded-lg shadow-md">
                ${message}
            </p>`;
    }
}

export function updateStatusMessage(message: string, type: string = 'info'): void {
    let colorClass;
    switch (type) {
        case 'error':
            colorClass = 'text-red-700';
            break;
        case 'success':
            colorClass = 'text-white';
            break;
        case 'warning':
            colorClass = 'text-yellow-700';
            break;
        case 'info':
            colorClass = 'text-white';
            break;
        default:
            colorClass = 'text-blue-700';
    }

    if (!message.includes("nn")) {
        statusMessage.innerText = message;
        statusMessage.className = `font-bold ${colorClass}`;
    }
}

/**
 * Evidenzia le immagini infette modificandone il bordo.
 *
 * @param fileNameInfected - Il nome del file infetto.
 */
export function highlightInfectedImages(fileNameInfected: string): void {
    // Verifica che fileNameInfected sia una stringa
    if (typeof fileNameInfected !== 'string') {
        console.error('fileNameInfected deve essere una stringa');
        return;
    }

    const infectedImage = document.getElementById(`file-${fileNameInfected}`);

    if (infectedImage) {
        const imgElement = infectedImage.querySelector('img');

        // Verifica che imgElement sia un elemento immagine
        if (imgElement instanceof HTMLImageElement) {
            imgElement.style.border = '3px solid red';
        } else {
            console.error('Elemento immagine non trovato');
        }
    } else {
        console.error(`Immagine non trovata per il file: ${fileNameInfected}`);
    }
}

/**
 * Funzione per rimuovere un file specifico.
 *
 * @param fileName - Il nome del file da eliminare
 */
export async function removeFile(fileName: string): Promise<void> {
    if (fileName) {
        try {
            // deleteTemporaryFileDO() è una funzione che elimina il file temporaneo dal disco esterno
            // Questa funzione è commentata perché il file temporaneo sul disco esterno viene creato solo se è gestita la presignedURL
            // in questa versione dell'applicazione non è gestita la presignedURL, quindi non viene creato il file temporaneo sul disco esterno
            // await deleteTemporaryFileDO(fileName);

            const fileIndex = Array.from(files).findIndex((file: File) => file.name === fileName);

            if (fileIndex !== -1) {
                const filesArray = Array.from(files);
                filesArray.splice(fileIndex, 1);
                if (window.envMode === 'local') {
                    console.log('file rimanenti:', files);
                }
            }

            removeImg(fileName);

            if (window.envMode === 'local') {
                console.log('file presenti DOPO della rimozione:', files);
            }

        } catch (error) {
            if (window.envMode === 'local') {
                console.error('Error deleting temporary file:', error);
            }

            throw new Error(window.deleteFileError);
        }
    } else {
        if (window.envMode === 'local') {
            console.log('File:', fileName);
            console.log('File index not removed:', fileName);
            console.log('File name not removed:', fileName);
        }
    }
}

/**
 * Funzione per rimuovere l'immagine dal DOM.
 * @param fileName - Il nome del file da eliminare
 */
export function removeImg(fileName: string): void {
    const remove = document.getElementById(`file-${fileName}`);
    if (window.envMode === 'local') {
        console.log('file rimosso:', `file-${fileName}`);
    }
    if (remove && remove.parentNode) {
        remove.parentNode.removeChild(remove);
        if (files.length === 0) {
            resetButtons();
        }
    }
}

// resources/ts/validation.ts
if (window.envMode === 'local') {
    console.log('Dentro resources/ts/validation.ts');
}

export interface ValidationResult {
    isValid: boolean;
    message?: string;
}

import Swal from 'sweetalert2';

export function validateFile(file: File): ValidationResult {
    const extension = file.name.split('.').pop()?.toLowerCase();

    if (!window.allowedExtensions.includes(extension!)) {
        const allowedExtensionsList = window.allowedExtensions.join(', ');
        const errorMessage = window.allowedExtensionsMessage
            .replace(':extension', extension!)
            .replace(':extensions', allowedExtensionsList);

        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleExtensionNotAllowedMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });

        return { isValid: false, message: errorMessage };
    }

    if (!window.allowedMimeTypes.includes(file.type)) {
        const errorMessage = window.allowedMimeTypesMessage
            .replace(':type', file.type)
            .replace(':mimetypes', window.allowedMimeTypesListMessage);

        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleFileTypeNotAllowedMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });

        return { isValid: false, message: errorMessage };
    }

    if (file.size > window.maxSize) {

        const errorMessage = window.maxSizeMessage.replace(':size', (window.maxSize / 1024).toString());

        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleFileSizeExceedsMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });

        return { isValid: false, message: errorMessage };
    }

    if (!validateFileName(file.name)) {
        const errorMessage = window.invalidFileNameMessage.replace(':filename', file.name);

        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleInvalidFileNameMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });

        return { isValid: false, message: errorMessage };
    }

    return { isValid: true };
}


export function validateFileName(fileName: string): boolean {
    const regex = /^[\w\-. ]+$/;
    return regex.test(fileName);
}
