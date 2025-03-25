/**
 * Temporary File Management Module
 *
 * Handles the creation and deletion of temporary files
 * during the upload process
 */

import { csrfToken } from '../index';

/**
 * Saves a file temporarily to the server
 *
 * @param {FormData} formData - Form data containing the file to save
 * @returns {Promise<Response>} - Server response
 * @throws {Error} If saving fails
 */
export async function saveLocalTempFile(formData: FormData): Promise<Response> {
    console.log('Saving temporary file locally');

    try {
        const response = await fetch('/upload-temp', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (!response.ok) {
            const result = await response.json();
            const error = JSON.stringify(result.error);
            console.error('Error in saveLocalTempFile. Error:', error);
            throw new Error(error);
        }

        return response;
    } catch (error) {
        console.error('Error in saveLocalTempFile:', error);
        throw error;
    }
}

/**
 * Deletes a temporary file from Digital Ocean storage
 *
 * @param {string} file - File path or identifier to delete
 * @returns {Promise<Response>} - Server response
 * @throws {Error} If deletion fails
 */
export async function deleteTemporaryFileExt(file: string): Promise<Response> {
    console.log('Deleting temporary file from external storage:', file);

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', csrfToken);

    try {
        const response = await fetch('/delete-temporary-file-DO', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });

        if (!response.ok) {
            const result = await response.json();
            const error = JSON.stringify(result.error);
            console.error('Error in deleteTemporaryFileExt. Error:', error);
            throw new Error(error);
        }

        return response;
    } catch (error) {
        console.error('Error in deleteTemporaryFileDO:', error);
        throw error;
    }
}

/**
 * Deletes a temporary local file
 *
 * @param {File} file - The file to delete
 * @returns {Promise<Response>} - Server response
 * @throws {Error} If deletion fails
 */
export async function deleteTemporaryFileLocal(file: File): Promise<Response> {
    console.log('Deleting temporary local file:', file.name);

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', csrfToken);

    try {
        const response = await fetch('/delete-temporary-file-local', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        if (!response.ok) {
            const result = await response.json();
            console.error('Error in deleteTemporaryFileLocal. Result:', result);
            throw result;
        }

        return response;
    } catch (error) {
        console.error('Error in deleteTemporaryFileLocal:', error);
        throw error;
    }
}

/**
 * Sets up event handlers to clean up temporary files when page is closed
 * Attaches to window beforeunload event
 */
export function setupTempFileCleanup(): void {
    window.addEventListener('beforeunload', async (e: BeforeUnloadEvent) => {
        const getFiles = () => {
            const fileInput = document.getElementById('files') as HTMLInputElement;
            return fileInput ? fileInput.files : null;
        };

        const files = getFiles();
        if (files?.length) {
            e.preventDefault();
            e.returnValue = '';

            // Clean up all temporary files
            for (const file of Array.from(files)) { // Converti FileList in array
                try {
                    await deleteTemporaryFileLocal(file);
                } catch (error) {
                    console.error(`Error deleting temporary file ${file.name}:`, error);
                }
            }
        }
    });
}
