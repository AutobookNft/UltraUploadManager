/**
 * Upload UI Preparation Module
 *
 * Handles UI updates related to file upload preparation including
 * status messages, progress bars, image previews, and delete buttons.
 */

import {
    statusMessage,
    statusDiv,
    scanProgressText,
    progressBar,
    scanvirus,
    disableButtons,
    enableButtons,
    resetButtons,
    removeEmoji,
    handleImage,
    updateStatusDiv,
    updateStatusMessage
} from '../index';


/**
 * Prepares the user interface for file upload
 * Creates previews, updates status messages, and configures button states
 *
 * @param {FileList} files - List of files to prepare for upload
 * @returns {Promise<void>}
 */
export async function prepareFilesForUploadUI(files: FileList): Promise<void> {
    // Store filenames for later reference
    const fileNames: string[] = [];

    // Calculate progress increment per file
    const incremento: number = 100 / files.length;

    // Initialize UI elements
    statusMessage.innerText = window.startingUpload + '...';
    statusDiv.innerHTML = '';
    scanProgressText.innerText = '';
    progressBar.style.width = '0%';

    // Disable buttons during preparation
    disableButtons();
    removeEmoji();

    try {
        // Process each file in the FileList
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            fileNames[i] = file.name;

            try {
                // Update status during preparation
                statusMessage.innerText = window.loading + '...';

                // Create image preview using object URL
                const objectUrl = URL.createObjectURL(file);
                handleImage(i, { target: { result: objectUrl } }, files);

                // Make delete buttons visible for each file
                makeDeleteButtonsVisible(files);

            } catch (error) {
                // Handle errors for individual files without failing the entire process
                const userMessage = error instanceof Error ? error.message : 'Unknown error preparing file';
                console.error(`Error preparing ${file.name}:`, error);

                updateStatusDiv(`${file.name}: ${userMessage}`, 'error');
                updateStatusMessage(userMessage, 'error');
            }
        }

        // Enable buttons after preparation is complete
        enableButtons();

        // Update final status message based on virus scan setting
        updateFinalStatusMessage();

    } catch (error) {
        // Handle unexpected errors in the preparation process
        console.error('Error in prepareFilesForUploadUI:', error);
        updateStatusMessage('Error preparing files for upload', 'error');
        resetButtons();
    }
}

/**
 * Makes delete buttons visible for all files
 * @param {FileList} files - List of files to show delete buttons for
 */
function makeDeleteButtonsVisible(files: FileList): void {
    for (let j = 0; j < files.length; j++) {
        const fileName = files[j].name;
        const deleteButton = document.getElementById(`button-${fileName}`) as HTMLElement | null;
        if (deleteButton) {
            deleteButton.classList.remove('hidden');
        }
    }
}

/**
 * Updates the final status message based on virus scan checkbox state
 */
function updateFinalStatusMessage(): void {
    if (scanvirus.checked) {
        statusMessage.innerText = window.uploadAndScanText;
    } else {
        statusMessage.innerText = window.uploadFiniscedText;
    }
}
