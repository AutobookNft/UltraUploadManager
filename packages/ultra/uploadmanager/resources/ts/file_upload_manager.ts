import { handleUpload } from './uploading';
import { validateFile } from './validation';
import { showEmoji } from './showEmoji';
import { prepareFilesForUploadUI } from './prepareFilesForUploadUI';
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
} from './domElements';

import {
    resetButtons,
    handleImage,
    removeFile,
    removeImg
} from './uploadUtils';

import { setupRealTimeUploadListener } from './realTimeUploadListener';

// Now you can use handleUpload
handleUpload().then(() => {
    console.log('Upload handled successfully');
}).catch((error) => {
    console.error('Error handling upload:', error);
});

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

    if (window.envMode === 'local') {
        console.log('Dentro uploading_files');
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
    scanvirus.addEventListener('click', function() {
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


