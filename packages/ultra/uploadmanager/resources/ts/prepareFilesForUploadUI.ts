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
} from './domElements';

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
