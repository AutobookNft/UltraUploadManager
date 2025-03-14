
import {
    handleUpload,
    prepareFilesForUploadUI,
    showEmoji,
    validateFile,
    scanProgressText,
    progressBar,
    progressText,
    getFiles,
    scanvirusLabel,
    scanvirus,
    virusAdvise,
    statusDiv,
    statusMessage,
    resetButtons,
    handleImage,
    removeFile,
    removeImg,
    setupRealTimeUploadListener,
    deleteTemporaryFileLocal
} from '../index';

declare const window: any;

(function () {
    // Binding globali
    window.showEmoji = showEmoji;
    window.handleImage = handleImage;
    window.removeFile = removeFile;

    setupRealTimeUploadListener();

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

    document.addEventListener('DOMContentLoaded', () => {
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
            console.log('Scanvirus clicked');
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

})();
