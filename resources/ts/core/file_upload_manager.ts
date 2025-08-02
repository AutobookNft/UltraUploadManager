// file_upload_manager.ts
import {
    getFiles,
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
    if (fileList && validateFiles(fileList)) {
        files = Array.from(fileList);
        prepareFilesForUploadUI(fileList);
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
    if (fileList && validateFiles(fileList)) {
        window.droppedFiles = fileList;
        files = Array.from(fileList);
        prepareFilesForUploadUI(fileList);
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

/**
 * Validates a list of files against allowed extensions, size limits, and other criteria.
 * Displays error messages via SweetAlert if validation fails.
 *
 * @param files - The list of files to validate.
 * @returns Boolean indicating if all files are valid.
 */
function validateFiles(files: FileList | null): boolean {
    if (typeof window.envMode === 'undefined' || !window.allowedExtensions || !window.allowedMimeTypes) {
        console.warn('Config not yet loaded, delaying validation...');
        document.addEventListener('configLoaded', () => validateFiles(files), { once: true });
        return false;
    }

    if (!files) {
        console.warn('No files selected for validation');
        return false;
    }

    // First validation: upload limits (number of files, size, etc.)
    const limitsValidation = validateFilesAgainstLimits(files);
    if (!limitsValidation.valid) {
        Swal.fire({
            title: 'Upload Limits Exceeded',
            html: limitsValidation.message,
            icon: 'warning',
            confirmButtonText: 'OK',
        });
        return false;
    }

    // Second validation: extensions, MIME types, etc. - Let validateFile handle its own messages
    let allFilesValid = true;
    const invalidFiles: string[] = [];

    for (let i = 0; i < files.length; i++) {
        const result = validateFile(files[i]);
        if (!result.isValid) {
            console.error(`File ${files[i].name} failed validation: ${result.message}`);
            // Don't collect invalid files - validateFile already showed its message
            allFilesValid = false;
            break; // Stop at first invalid file since validateFile already showed message
        }
    }

    return allFilesValid;
}
