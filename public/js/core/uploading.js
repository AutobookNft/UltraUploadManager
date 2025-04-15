var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { csrfToken, progressBar, progressText, scanProgressText, statusMessage, scanvirus, getFiles, handleVirusScan, showEmoji, disableButtons, resetButtons, updateStatusDiv, updateStatusMessage, highlightInfectedImages, removeImg, HubFileController } from '../index';
/**
 * Function to upload a file server-side.
 * @param formData - The form data containing the file to upload.
 * @returns An object containing the upload result, response, and any errors.
 */
export function fileForUpload(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        let errorData = null;
        let success = true;
        if (window.envMode === 'local') {
            console.log('inside fileForUpload');
        }
        if (window.envMode === 'local') {
            const file = formData.get('file');
            console.log('in fileForUpload: formData:', file === null || file === void 0 ? void 0 : file.name); // Log the Content-Type to verify the response type
        }
        try {
            const response = yield fetch('/uploading-files', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json',
                },
                body: formData
            });
            const contentType = response.headers.get('content-type');
            if (window.envMode === 'local') {
                console.log('Content-Type:', contentType); // Log the Content-Type to verify the response type
            }
            if (!response.ok) {
                if (contentType && contentType.includes('application/json')) {
                    errorData = yield response.json(); // Get the response from the server in JSON format
                    success = false;
                }
                else {
                    const rawErrorData = yield response.text(); // If it’s not JSON, get the text (might be HTML)
                    errorData = {
                        message: (window.serverError || 'The server returned an invalid or unexpected response.'),
                        details: rawErrorData, // Keep the HTML or text content as details
                        state: 'unknown',
                        errorCode: 'unexpected_response',
                        blocking: 'blocking', // Consider this a blocking error by default
                    };
                    success = false;
                }
                return { error: errorData, response, success };
            }
            return { error: false, response, success };
        }
        catch (error) {
            if (window.envMode === 'local') {
                console.error('Error in fileForUpload:', error);
            }
            return { error, response: false, success: false }; // Return the error as part of the object
        }
    });
}
/**
 * Function that attempts to upload a file up to a specified maximum number of attempts.
 * It is used to handle the upload of a file to the server,
 * with the option to retry the upload in case of failure up to
 * maxAttempts times.
 *
 * @param formData - The file data to upload, including any CSRF tokens and other metadata.
 * @param maxAttempts - The maximum number of upload attempts. Default: 3.
 *
 * @returns An object containing:
 *  - success: Indicates whether the upload was completed successfully.
 *  - response: Contains the final upload result or the error if all attempts fail.
 *
 * Notes:
 * - If the upload fails, it is retried up to maxAttempts times.
 * - The 'result' variable is declared outside the loop to retain
 *   its final value at the end of the loop and be correctly returned to the calling function.
 */
export function attemptFileUpload(formData_1) {
    return __awaiter(this, arguments, void 0, function* (formData, maxAttempts = 3) {
        let attempt = 0;
        let success = false;
        let error = null;
        let response = false;
        while (attempt < maxAttempts && !success) {
            attempt++;
            ({ error, response, success } = yield fileForUpload(formData));
            if (success) {
                if (window.envMode === 'local') {
                    console.log(`Attempt ${attempt} succeeded.`);
                }
                return { error, response };
            }
            else {
                if (window.envMode === 'local') {
                    console.warn(`Attempt ${attempt} failed: ${error.message}`);
                }
            }
            if (!success && attempt < maxAttempts) {
                if (window.envMode === 'local') {
                    console.log(`Retrying attempt ${attempt + 1}...`);
                }
            }
        }
        return { error, response };
    });
}
/**
 * Function that handles the upload and scanning of files.
 */
export function handleUpload() {
    return __awaiter(this, void 0, void 0, function* () {
        console.log('handleUpload called'); // Reinstated
        console.time('UploadProcess');
        const files = getFiles() || [];
        if (window.envMode === 'local') {
            console.log('📤 Uploading files:', files.length); // Reinstated
        }
        if (files.length === 0) {
            console.warn('⚠️ No files selected.');
            console.timeEnd('UploadProcess');
            return;
        }
        disableButtons();
        statusMessage.innerText = window.startingSaving + '...';
        let increment = 100 / files.length;
        let flagUploadOk = true;
        let iterFailed = 0;
        let someInfectedFiles = 0;
        let index = 0;
        let userMessage = "";
        for (const file of files) {
            if (window.envMode === 'local') {
                console.log(`📂 Uploading file: ${file.name}`);
            }
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
                    if (yield handleVirusScan(formData)) {
                        flagUploadOk = false;
                        iterFailed++;
                        someInfectedFiles++;
                        highlightInfectedImages(file.name);
                        resetProgressBar();
                        continue;
                    }
                }
                const uploadType = determineUploadType(getUploadContext());
                console.log(`📌 Routing to: ${uploadType}`);
                const hubFileController = new HubFileController();
                const { error, response, success } = yield hubFileController.handleFileUpload(file, uploadType);
                if (!success) {
                    if (error === null || error === void 0 ? void 0 : error.details) {
                        console.error('🚨 Non-JSON error:', error.details);
                        userMessage = error.userMessage || error.message || window.unknownError || "Unknown error";
                        flagUploadOk = false;
                        iterFailed++;
                        updateStatusDiv(userMessage, 'error');
                        if (error.blocking === 'blocking')
                            break;
                    }
                    else {
                        console.error('🚨 Error during upload:', error);
                        userMessage = (error === null || error === void 0 ? void 0 : error.userMessage) || (error === null || error === void 0 ? void 0 : error.message) || window.unknownError || "Unknown error";
                        flagUploadOk = false;
                        iterFailed++;
                        updateStatusDiv(userMessage, 'error');
                    }
                }
                else {
                    if (window.envMode === 'local') {
                        console.log(`✅ Upload succeeded: ${file.name}`);
                    }
                    removeImg(file.name);
                    if (response instanceof Response) {
                        const resultResponse = yield response.json();
                        updateStatusDiv(resultResponse.userMessage || updateFileSavedMessage(file.name), 'success');
                        updateProgressBar(index, increment);
                    }
                }
            }
            catch (error) {
                flagUploadOk = false;
                if (window.envMode === 'local') {
                    console.error(`❌ Catch in handleUpload: ${error}`);
                }
                const uploadError = error instanceof Error ? {
                    message: error.message,
                    userMessage: (window.serverError || 'Error during upload'),
                    details: error.stack,
                    state: "unknown",
                    errorCode: "unexpected_error",
                    blocking: "blocking"
                } : error;
                if (uploadError.blocking === 'blocking') {
                    updateStatusMessage(uploadError.userMessage || "Critical error during upload", 'error');
                    iterFailed = files.length;
                    break;
                }
                else {
                    userMessage = uploadError.userMessage || uploadError.message || window.errorDuringUpload || "Error during upload";
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
    });
}
/**
 * Function to determine the upload type based on the context.
 * @param contextType - The upload type determined by the context (e.g., endpoint or parameter).
 * @returns The upload type ('egi', 'epp', 'utility', etc.).
 */
function determineUploadType(contextType) {
    const validTypes = ['egi', 'epp', 'utility'];
    return validTypes.includes(contextType) ? contextType : 'default';
}
/**
 * Function to determine the upload context from the current URL.
 * @returns The context type ('egi', 'epp', 'utility', etc.).
 */
function getUploadContext() {
    const currentPath = window.location.pathname;
    if (currentPath.includes('/uploading/egi'))
        return 'egi';
    if (currentPath.includes('/uploading/epp'))
        return 'epp';
    if (currentPath.includes('/uploading/utility'))
        return 'utility';
    return 'default'; // Fallback
}
/**
 * Updates the progress bar.
 * @param index - Index of the file currently being uploaded
 * @param increment - Value to add to the progress bar
 */
function updateProgressBar(index, increment) {
    progressBar.style.width = `${(index + 1) * increment}%`;
    progressText.innerText = `${Math.round((index + 1) * increment)}%`;
}
/**
 * Resets the progress bar in case of an error.
 */
function resetProgressBar() {
    progressBar.style.width = "0";
    progressText.innerText = "";
}
/**
 * Function to finalize the upload and display the results.
 *
 * @param flagUploadOk - Boolean indicating whether the overall upload succeeded.
 * @param iterFailed - Number of failed upload attempts.
 */
export function finalizeUpload(flagUploadOk, iterFailed) {
    resetButtons(); // Re-enable buttons at the end of the upload
    const files = getFiles() || [];
    if (flagUploadOk && iterFailed === 0) {
        showEmoji('success');
    }
    else if (!flagUploadOk && iterFailed > 0 && iterFailed < files.length) {
        showEmoji('someError');
    }
    else if (!flagUploadOk && iterFailed === files.length) {
        showEmoji('completeFailure');
        updateStatusMessage(window.completeFailure, 'error');
    }
}
/**
 * Function to update the file saved message.
 *
 * @param fileName - The name of the successfully saved file.
 * @returns The updated message with the saved file name.
 */
export function updateFileSavedMessage(fileName) {
    const messageTemplate = window.fileSavedSuccessfullyTemplate;
    return messageTemplate.replace(':fileCaricato', fileName);
}
//# sourceMappingURL=uploading.js.map