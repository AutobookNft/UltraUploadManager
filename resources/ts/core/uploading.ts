import {
    csrfToken,
    progressBar,
    progressText,
    scanProgressText,
    statusMessage,
    scanvirus,
    getFiles,
    handleVirusScan,
    showEmoji,
    disableButtons,
    resetButtons,
    updateStatusDiv,
    updateStatusMessage,
    highlightInfectedImages,
    removeImg,
    HubFileController,

} from '../index';

interface FileUploadResult {
    error: any; // You can create a more specific type if you know the error structure
    response: Response | false; // The `Response` type for fetch API or `false` in case of error
    success: boolean;
}

interface UploadError {
    message: string;
    userMessage?: string;
    details?: string;
    state?: string;
    errorCode?: string;
    blocking?: "blocking" | "not";
}

/**
 * Function to upload a file server-side.
 * @param formData - The form data containing the file to upload.
 * @returns An object containing the upload result, response, and any errors.
 */
export async function fileForUpload(formData: FormData): Promise<FileUploadResult> {
    let errorData: any = null;
    let success: boolean = true;

    if ((window as any).envMode === 'local') {
        console.log('inside fileForUpload');
    }

    if ((window as any).envMode === 'local') {
        const file = formData.get('file') as File;
        console.log('in fileForUpload: formData:', file?.name); // Log the Content-Type to verify the response type
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
            console.log('Content-Type:', contentType); // Log the Content-Type to verify the response type
        }

        if (!response.ok) {
            if (contentType && contentType.includes('application/json')) {
                errorData = await response.json(); // Get the response from the server in JSON format
                success = false;
            } else {
                const rawErrorData = await response.text(); // If it’s not JSON, get the text (might be HTML)
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
    } catch (error) {
        if ((window as any).envMode === 'local') {
            console.error('Error in fileForUpload:', error);
        }

        return { error, response: false, success: false }; // Return the error as part of the object
    }
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
                console.log(`Attempt ${attempt} succeeded.`);
            }
            return { error, response };
        } else {
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
}

/**
 * Function that handles the upload and scanning of files.
 *
 * @oracode.explicitly_intentional Passes upload type for modal contexts to ensure correct handler selection.
 */
export async function handleUpload(): Promise<void> {
    const formData = new FormData();
    console.log('handleUpload called');
    console.time('UploadProcess');
    const files = getFiles() || [];
    if (window.envMode === 'local') {
        console.log('📤 Uploading files:', files.length);
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

    for (const file of Array.from(files)) {
        if (window.envMode === 'local') {
            console.log(`📂 Uploading file: ${file.name}`);
        }

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

            // Check if the scanvirus checkbox is checked
            if (scanvirus.checked) {
                updateStatusMessage(window.startingScan + '...', 'info');
                formData.append('someInfectedFiles', someInfectedFiles.toString());
                formData.append('fileName', file.name);

                // Check if the file is infected
                if (await handleVirusScan(formData)) {
                    flagUploadOk = false;
                    iterFailed++;
                    someInfectedFiles++;
                    highlightInfectedImages(file.name);
                    resetProgressBar();
                    continue;
                }
            }

            const hubFileController = new HubFileController();
            // Pass the upload type from window.uploadType (set by file_upload_manager.ts)
            const { error, response, success } = await hubFileController.handleFileUpload(formData, window.uploadType);

            if (!success) {
                if (error?.details) {
                    console.error('🚨 Non-JSON error:', error.details);
                    userMessage = error.userMessage || error.message || window.unknownError || "Unknown error";
                    flagUploadOk = false;
                    iterFailed++;
                    updateStatusDiv(userMessage, 'error');
                    if (error.blocking === 'blocking') break;
                } else {
                    console.error('🚨 Error during upload:', error);
                    userMessage = error?.userMessage || error?.message || window.unknownError || "Unknown error";
                    flagUploadOk = false;
                    iterFailed++;
                    updateStatusDiv(userMessage, 'error');
                }
            } else {
                if (window.envMode === 'local') {
                    console.log(`✅ Upload succeeded: ${file.name}`);
                }

                removeImg(file.name);

                if (response instanceof Response) {
                    const resultResponse = await response.json();
                    if (typeof resultResponse.userMessage === 'string' && resultResponse.userMessage.trim() !== '') {
                        updateStatusDiv(resultResponse.userMessage, 'success');
                    } else {
                        console.warn('Server response missing or invalid "userMessage" string property. Using fallback message.', { responseReceived: resultResponse });
                        updateStatusDiv(updateFileSavedMessage(file.name), 'success');
                    }
                    updateProgressBar(index, increment);
                }
            }
        } catch (error) {
            flagUploadOk = false;
            if (window.envMode === 'local') {
                console.error(`❌ Catch in handleUpload: ${error}`);
            }

            const uploadError: UploadError = error instanceof Error ? {
                message: error.message,
                userMessage: (window.serverError || 'Error during upload'),
                details: error.stack,
                state: "unknown",
                errorCode: "unexpected_error",
                blocking: "blocking"
            } : {
                message: String(error),
                userMessage: (window.serverError || 'Error during upload'),
                state: "unknown",
                errorCode: "unexpected_error",
                blocking: "blocking"
            };

            if (uploadError.blocking === 'blocking') {
                updateStatusMessage(uploadError.userMessage || "Critical error during upload", 'error');
                iterFailed = files.length;
                break;
            } else {
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
}

/**
 * Updates the progress bar.
 * @param index - Index of the file currently being uploaded
 * @param increment - Value to add to the progress bar
 */
function updateProgressBar(index: number, increment: number): void {
    progressBar.style.width = `${(index + 1) * increment}%`;
    progressText.innerText = `${Math.round((index + 1) * increment)}%`;
}

/**
 * Resets the progress bar in case of an error.
 */
function resetProgressBar(): void {
    progressBar.style.width = "0";
    progressText.innerText = "";
}

/**
 * Function to finalize the upload and display the results.
 *
 * @param flagUploadOk - Boolean indicating whether the overall upload succeeded.
 * @param iterFailed - Number of failed upload attempts.
 */
export function finalizeUpload(flagUploadOk: boolean, iterFailed: number): void {
    resetButtons(); // Re-enable buttons at the end of the upload

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
 * Function to update the file saved message.
 *
 * @param fileName - The name of the successfully saved file.
 * @returns The updated message with the saved file name.
 */
export function updateFileSavedMessage(fileName: string): string {
    const messageTemplate = window.fileSavedSuccessfullyTemplate;
    if (typeof messageTemplate !== 'string') { // Controllo esplicito
        console.error('Global variable "fileSavedSuccessfullyTemplate" is missing or not a string!');
        return `File ${fileName} saved (template missing).`; // Fallback sicuro
    }
    return messageTemplate.replace(':fileCaricato', fileName);
}
