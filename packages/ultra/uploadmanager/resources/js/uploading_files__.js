// Funzione per gestire l'upload dei file
import { handleUpload } from '@ts/uploading';
import { validateFile } from '@ts/validation';
import { showEmoji } from '@ts/showEmoji';
import { prepareFilesForUploadUI } from '@ts/prepareFilesForUploadUI';
import {
    scanProgressText,
    progressBar,
    progressText,
    getFiles,
    scanvirusLabel,
    scanvirus,
    virusAdvise,
} from '@ts/domElements';

import {
    resetButtons,
    handleImage,
    removeFile,
    removeImg
} from '@ts/uploadUtils';

import { setupRealTimeUploadListener } from '@ts/realTimeUploadListener';

/**
 * @param {FileList} files - Lista di file da validare
 * @returns {boolean} - Restituisce true se tutti i file sono validi, altrimenti false
 */
function validation(files) {
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

    if (window.envMode === 'local') {
        console.log('Dentro uploading_files');
    }

    if (window.envMode === 'local') {
        // @ts-ignore
        console.log('Send email:', window.sendEmail);
    }

    scanProgressText.innerText = "";

    // Chiama Ascoltatore per la gestione delle notifiche in tempo reale
    setupRealTimeUploadListener();


    /**
     * @type {any[] | FileList}
     */
    let files = [];
    let fileNames = [];

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
        // @ts-ignore
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
    function redirectToCollection() {
        window.location.href = window.redirectToCollection;
    }

    // Funzione che gestisce la selezione dei file
    // @ts-ignore
    async function handleFileSelect(event) {
        // let files = Array.from(event.target.files);

        const files = getFiles();
        // @ts-ignore
        if (validation(files)) {
            // @ts-ignore
            await prepareFilesForUploadUI(files);
        }
    }

    // Funzione che gestisce il drag & drop dei file
    // @ts-ignore
    async function handleDrop(event) {
        event.preventDefault();
        let url_files = Array.from(event.dataTransfer.files);
        // @ts-ignore
        if (validation(files)) {
            // @ts-ignore
            await prepareFilesForUploadUI(files);
        }
    }

    // Funzione per annullare l'upload
    async function cancelUpload() {
        const fileInput = document.getElementById('files');
        // @ts-ignore
        fileInput.value = '';

        // @ts-ignore
        if (window.files) {
            // @ts-ignore
            for (const file of window.files) {
                if (file && file.name) {
                    removeImg(file.name);
                }
            }
            // @ts-ignore
            window.files = [];
        }

        try {
            const success = await deleteTemporaryFolder();
            if (!success) {
                // @ts-ignore
                shouldBlockUnload = true;
            }
        } catch (error) {
            // @ts-ignore
            shouldBlockUnload = true;
        }

        resetButtons();
        document.getElementById('collection').innerHTML = '';
        document.getElementById('progress-bar').style.width = '0%';
        document.getElementById('progress-text').innerText = '';
        document.getElementById('status-message').innerText = 'Upload Status: In attesa...';
        document.getElementById('status').innerHTML = '';
    }

    // @ts-ignore
    window.showEmoji = showEmoji;
    // @ts-ignore
    window.handleImage = handleImage;
    // @ts-ignore
    window.removeFile = removeFile;
    // @ts-ignore
    window.handleFileSelect = handleFileSelect;
    // @ts-ignore
    window.startUpload = handleUpload;
    // @ts-ignore
    window.handleDrop = handleDrop;
    // @ts-ignore
    window.redirectToCollection = redirectToCollection;
    // @ts-ignore
    window.resetButtons = resetButtons;
    // @ts-ignore
    window.cancelUpload = cancelUpload;

});

window.addEventListener('beforeunload', async function (e) {
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
async function deleteTemporaryFolder() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    try {
        const response = await fetch('/delete-temporary-folder', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                // @ts-ignore
                folderName: window.temporaryFolder // Specifica il nome della cartella temporanea
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
