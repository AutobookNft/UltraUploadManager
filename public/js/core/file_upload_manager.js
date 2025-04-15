var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { handleUpload, prepareFilesForUploadUI, showEmoji, validateFile, scanProgressText, progressBar, progressText, getFiles, scanvirusLabel, scanvirus, virusAdvise, statusDiv, statusMessage, resetButtons, handleImage, removeFile, setupRealTimeUploadListener, deleteTemporaryFileLocal } from '../index';
/**
 * Self-invoking function that sets up the file upload functionality.
 * Binds global functions, initializes event listeners, and handles file selection, upload, and cancellation.
 */
(function () {
    // Global bindings for external access
    window.showEmoji = showEmoji;
    window.handleImage = handleImage;
    window.removeFile = removeFile;
    // Set up real-time upload listener (assumed to be defined in '../index')
    setupRealTimeUploadListener();
    /**
     * Handles file selection from an input event.
     * Validates the selected files and prepares them for upload if valid.
     * @param event - The file selection event from an input element.
     */
    window.handleFileSelect = function (event) {
        if (typeof window.envMode === 'undefined') {
            console.warn('Config not yet loaded, delaying handleFileSelect...');
            document.addEventListener('configLoaded', () => window.handleFileSelect(event), { once: true });
            return;
        }
        console.log('Handling file select...');
        const files = getFiles();
        if (validation(files))
            prepareFilesForUploadUI(files);
    };
    // Bind the handleUpload function globally
    window.handleUpload = handleUpload;
    /**
     * Handles file drop events from drag-and-drop interactions.
     * Prevents default behavior, validates dropped files, and prepares them for upload.
     * @param event - The drag-and-drop event containing file data.
     */
    window.handleDrop = function (event) {
        var _a;
        if (typeof window.envMode === 'undefined') {
            console.warn('Config not yet loaded, delaying handleDrop...');
            document.addEventListener('configLoaded', () => window.handleDrop(event), { once: true });
            return;
        }
        event.preventDefault();
        const files = (_a = event.dataTransfer) === null || _a === void 0 ? void 0 : _a.files;
        if (files && validation(files))
            prepareFilesForUploadUI(files);
    };
    /**
     * Redirects the user to a collection page.
     * Waits for config to load if not yet available.
     */
    window.redirectToCollection = function () {
        if (typeof window.URLRedirectToCollection === 'undefined') {
            console.warn('Config not yet loaded, delaying redirectToCollection...');
            document.addEventListener('configLoaded', window.redirectToCollection, { once: true });
            return;
        }
        window.location.href = window.URLRedirectToCollection;
    };
    // Bind the resetButtons function globally
    window.resetButtons = resetButtons;
    /**
     * Cancels the current upload process.
     * Confirms with the user, deletes temporary files, and resets the UI.
     */
    window.cancelUpload = function () {
        return __awaiter(this, void 0, void 0, function* () {
            if (typeof window.envMode === 'undefined') {
                console.warn('Config not yet loaded, delaying cancelUpload...');
                document.addEventListener('configLoaded', () => window.cancelUpload(), { once: true });
                return;
            }
            if (confirm(window.cancelConfirmation || 'Do you want to cancel?')) {
                const files = getFiles();
                if (files) {
                    for (const file of files) {
                        try {
                            yield deleteTemporaryFileLocal(file);
                        }
                        catch (error) {
                            console.error(`Error deleting ${file.name}:`, error);
                        }
                    }
                    resetButtons();
                    document.getElementById('collection').innerHTML = '';
                    progressBar.style.width = '0%';
                    progressText.innerText = '';
                    statusMessage.innerText = window.uploadStatusWaiting || 'Upload Status: Waiting...';
                    statusDiv.innerHTML = '';
                }
                else {
                    console.error('No files to delete');
                }
            }
        });
    };
    /**
     * Validates a list of files against allowed extensions and other criteria.
     * @param files - The list of files to validate.
     * @returns Boolean indicating if all files are valid.
     */
    function validation(files) {
        if (typeof window.allowedExtensions === 'undefined') {
            console.warn('allowedExtensions not defined, delaying validation...');
            return false; // Delay until defined
        }
        if (!files)
            return false;
        for (let i = 0; i < files.length; i++) {
            const result = validateFile(files[i]);
            if (!result.isValid) {
                console.error(`File ${files[i].name} failed validation: ${result.message}`);
                return false;
            }
        }
        return true;
    }
    // Initialize the file manager when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', () => {
        var _a;
        console.time('FileManagerInit');
        if (window.envMode === 'local')
            console.log('Inside uploading_files');
        // Reset UI elements
        scanProgressText.innerText = '';
        progressBar.style.width = '0';
        progressText.innerText = '';
        // Log configuration details in local mode
        if (window.envMode === 'local') {
            console.log(window.uploadFiniscedText);
            console.log('allowedExtensionsMessage:', window.allowedExtensionsMessage);
        }
        // Add event listener for virus scan toggle
        scanvirus.addEventListener('click', function () {
            console.log('Scanvirus clicked');
            if (scanvirus.checked) {
                scanvirusLabel.classList.remove('text-red-500');
                scanvirusLabel.classList.add('text-green-500');
                scanvirusLabel.innerText = window.enableVirusScanning || 'Virus scanning enabled';
                virusAdvise.style.display = 'block';
                virusAdvise.classList.add('text-red-500');
                virusAdvise.innerText = window.virusScanAdvise || 'Warning: Scanning may slow down the upload process.';
            }
            else {
                scanvirusLabel.classList.remove('text-green-500');
                scanvirusLabel.classList.add('text-red-500');
                scanvirusLabel.innerText = window.disableVirusScanning || 'Virus scanning disabled';
                virusAdvise.style.display = 'none';
            }
        });
        // Add upload button click listener
        (_a = document.getElementById('uploadBtn')) === null || _a === void 0 ? void 0 : _a.addEventListener('click', handleUpload);
        console.timeEnd('FileManagerInit');
    });
    /**
     * Adds an event listener to clean up temporary files before the page unloads.
     * This ensures that any files selected by the user in the `<input id="files">` element
     * are deleted from temporary storage when the user attempts to leave the page (e.g., closing the tab or refreshing).
     * The `getFiles()` function retrieves the current list of files from the input element with ID 'files'.
     * It returns a `FileList` object containing the selected files if the input exists and has files, or `null` if the input
     * is not found in the DOM. If files are present, the unload is delayed to allow asynchronous deletion of each file.
     */
    window.addEventListener('beforeunload', (e) => __awaiter(this, void 0, void 0, function* () {
        const files = getFiles();
        if (files === null || files === void 0 ? void 0 : files.length) {
            e.preventDefault(); // Prevents the page from unloading immediately, triggering a confirmation dialog
            for (const file of files) {
                try {
                    yield deleteTemporaryFileLocal(file);
                }
                catch (error) {
                    console.error(`Error deleting ${file.name} on beforeunload:`, error);
                }
            }
        }
    }));
})();
//# sourceMappingURL=file_upload_manager.js.map