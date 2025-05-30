// domElements.ts
import {
    handleFileSelect,
    handleDrop,
    handleUpload,
    cancelUpload,
    redirectToCollection,
    redirectToURL,
    redirectToURLAfterLogin
} from '../index';

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
export const dropZone = document.getElementById('upload-drop-zone') as HTMLElement;
export const collection = document.getElementById('collection') as HTMLElement;
export const fileInput = document.getElementById('files') as HTMLInputElement; 
export const uploadType = document.getElementById('upload-container') as HTMLElement;

const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (!csrfMeta) {
    throw new Error("Meta tag with csrf-token not found");
}
export const csrfToken = csrfMeta.getAttribute('content') as string;

console.log('[Dom Element] upload type:', document.getElementById('upload-container')?.getAttribute('data-upload-type'));

/**
 * Retrieves files selected by the user, either through file input or drag-and-drop.
 * Checks the input element first, falling back to window.droppedFiles if no input files are present.
 *
 * @returns {FileList | null} The list of selected files or null if no files were selected.
 */
export function getFiles(): FileList | null {
    const inputFiles = fileInput?.files || null;
    if (inputFiles && inputFiles.length > 0) {
        return inputFiles;
    } else if (window.droppedFiles && window.droppedFiles.length > 0) {
        return window.droppedFiles;
    }
    return null;
}

/**
 * Sets up event listeners for all DOM elements involved in the upload interface.
 * Attaches handlers for file input, drag-and-drop, button clicks, and virus scan toggle events.
 * Ensures all DOM interactions are centralized in this module.
 */
export function setupDomEventListeners() {
    // File input change event
    fileInput?.addEventListener('change', handleFileSelect);

    // Drag-and-drop events
    dropZone.addEventListener('dragover', (event: DragEvent) => {
        event.preventDefault();
        dropZone.classList.add('border-blue-400', 'bg-purple-800/40');
    });
    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-blue-400', 'bg-purple-800/40');
    });
    dropZone.addEventListener('drop', handleDrop);

    // Button click events
    uploadBtn?.addEventListener('click', handleUpload);
    cancelUploadBtn?.addEventListener('click', cancelUpload);
    returnToCollectionBtn?.addEventListener('click', redirectToURL);

    // Virus scan toggle event
    scanvirus.addEventListener('click', () => {
        console.log('Scanvirus clicked');
        if (scanvirus.checked) {
            scanvirusLabel.classList.remove('text-red-500');
            scanvirusLabel.classList.add('text-green-500');
            scanvirusLabel.innerText = window.enableVirusScanning || 'Virus scanning enabled';
            virusAdvise.style.display = 'block';
            virusAdvise.classList.add('text-red-500');
            virusAdvise.innerText = window.virusScanAdvise || 'Warning: Scanning may slow down the upload process.';
        } else {
            scanvirusLabel.classList.remove('text-green-500');
            scanvirusLabel.classList.add('text-red-500');
            scanvirusLabel.innerText = window.disableVirusScanning || 'Virus scanning disabled';
            virusAdvise.style.display = 'none';
        }
    });
}
