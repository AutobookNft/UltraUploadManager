import { csrfToken, progressBar, progressText } from './domElements';
import { saveLocalTempFile } from './saveLocalTempFile';
import { deleteTemporaryFileExt, deleteTemporaryFileLocal } from './deleteTemporaryFiles';
import { updateStatusDiv } from './uploadUtils';

declare const window: any;

type ScanFileResponse = {
    response: Response;
    data: any;
};

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
        // throw new Error(`Error during file scan: ${error.message}`);
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
    await saveLocalTempFile(formData);

    // Scan the file and manage the progress bar
    const { response, data } = await scanFileWithProgress(formData);

    await deleteTemporaryFileLocal(formData.get('file') as File);

    if (window.envMode === 'local') {
        console.log(`response: ${response}`);
        console.log(`data: ${JSON.stringify(data)}`);
    }

    if (!response.ok) {
        // The file is infected
        if (response.status === 422) {
            updateStatusDiv(data.userMessage, 'error');
            return true; // Virus found
        }

        // Throw an error if the response is not OK, with all the data from the response
        throw data;
    }

    // File is not infected
    return false;
}
