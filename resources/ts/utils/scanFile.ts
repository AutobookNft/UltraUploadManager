import {
    csrfToken,
    progressBar,
    progressText,
    saveLocalTempFile,
    deleteTemporaryFileLocal,
    updateStatusDiv,
    saveToSystemTempDir,
    deleteSystemTempFile
} from '../index';

/**
 * Performs a virus scan on a file with a progress feedback simulation.
 *
 * @param formData - The form data containing the file to be scanned.
 * @returns A promise that resolves to the response and data from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function scanFileWithProgress(formData: FormData): Promise<ScanFileResponse> {
    if (window.envMode === 'local') {
        console.log('Inside scanFileWithProgress');
    }

    let progress = 0;
    const realScanDuration = 35000; // Simulated real scan duration of 35 seconds
    const startTime = Date.now();

    // Function to simulate progress up to 95%
    function simulateProgress(): void {
        const elapsedTime = Date.now() - startTime;
        progress = Math.min(95, (elapsedTime / realScanDuration) * 100);
        progressBar.style.width = `${progress}%`;
        progressText.innerText = `${Math.round(progress)}%`;

        if (progress >= 95) {
            clearInterval(interval);
        }
    }

    const interval = setInterval(simulateProgress, 100);

    if (window.envMode === 'local') {
        console.log('In scanFileWithProgress: formData:', formData); // Log the Content-Type to verify the type of response
    }

    try {
        // If the form contains a custom temporary path, add it to the request
        if (formData.has('systemTempPath')) {
            formData.append('customTempPath', formData.get('systemTempPath') as string);
        }

        const response = await fetch('/scan-virus', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        clearInterval(interval);

        if (response.ok) {
            const finalProgressInterval = setInterval(() => {
                progress += 0.5;
                progressBar.style.width = `${progress}%`;
                progressText.innerText = `${Math.round(progress)}%`;

                if (progress >= 100) {
                    clearInterval(finalProgressInterval);
                }
            }, 50);

            const data = await response.json();

            if (data) {
                return { response, data };
            } else {
                throw new Error('The JSON response is incorrect');
            }
        } else {
            const data = await response.json();
            return { response, data };
        }
    } catch (error: any) {
        clearInterval(interval);
        throw error;
    }
}

/**
 * Handles the antivirus scan for the given file, saving it temporarily before scanning.
 *
 * @param formData - The form data containing the file to be scanned.
 * @returns A promise that resolves to a boolean indicating if the file is infected.
 * @throws An error if the request fails or the response is not ok.
 */
export async function handleVirusScan(formData: FormData): Promise<boolean> {
    let usedFallbackMethod = false;

    try {
        // Try the standard method for temporary file saving
        await saveLocalTempFile(formData);
        if (window.envMode === 'local') {
            console.log('File saved temporarily using standard method');
        }
    } catch (primaryError) {
        if (window.envMode === 'local') {
            console.error('Error in primary temporary file saving:', primaryError);
        }

        try {
            // Alternative method: try using the system temp directory
            await saveToSystemTempDir(formData);
            usedFallbackMethod = true;
            if (window.envMode === 'local') {
                console.log('File saved temporarily using fallback method');
            }
        } catch (backupError) {
            // If both methods fail, we still try but with a warning
            if (window.envMode === 'local') {
                console.error('Error in alternative method too:', backupError);
            }
            updateStatusDiv(window.possibleScanningIssues || 'Warning: possible issues during virus scan', 'warning');
            // Continue with scanning anyway
        }
    }

    try {
        // Scan the file and handle progress bar
        const { response, data } = await scanFileWithProgress(formData);

        // Cleanup: delete temporary file based on the method used
        try {
            if (usedFallbackMethod) {
                await deleteSystemTempFile(formData);
            } else {
                await deleteTemporaryFileLocal(formData.get('file') as File);
            }
        } catch (deleteError) {
            // Log the error but continue (file has already been scanned)
            if (window.envMode === 'local') {
                console.warn('Error deleting temporary file:', deleteError);
            }
        }

        if (!response.ok) {
            // File is infected
            if (response.status === 422) {
                updateStatusDiv(data.userMessage, 'error');
                return true; // Virus found
            }

            // Other type of error in response
            throw data;
        }

        // File not infected
        return false;
    } catch (scanError: any) {
        // Special handling for "No such file or directory" error
        if (scanError.message && typeof scanError.message === 'string' &&
            (scanError.message.includes('No such file or directory') ||
             scanError.message.includes('file non trovato'))) {

            if (window.envMode === 'local') {
                console.warn('Temporary file not found during scanning. Proceeding anyway:', scanError);
            }

            updateStatusDiv(window.unableToCompleteScanContinuing || 'Warning: Unable to complete virus scan, but continuing anyway', 'warning');
            return false; // Consider file not infected to continue
        }

        // If there's an error in scanning, we still need to make sure to clean up
        try {
            if (usedFallbackMethod) {
                await deleteSystemTempFile(formData);
            } else {
                await deleteTemporaryFileLocal(formData.get('file') as File);
            }
        } catch (cleanupError) {
            // Log cleanup error, but the main error is the scanning error
            if (window.envMode === 'local') {
                console.warn('Error in cleanup after scanning error:', cleanupError);
            }
        }

        // Rethrow the scanning error for other types of errors
        throw scanError;
    }
}
