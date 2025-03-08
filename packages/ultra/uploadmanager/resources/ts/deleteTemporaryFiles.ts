import { csrfToken } from './domElements';

declare const window: any;

/**
 * Deletes a temporary file from Digital Ocean.
 *
 * @param file - The file to be deleted.
 * @returns A promise that resolves to the response from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function deleteTemporaryFileExt(file: string): Promise<Response> {
    if (window.envMode === 'local') {
        console.log('Inside deleteTemporaryFileExt');
    }

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

            if (window.envMode === 'local') {
                console.log('Inside deleteTemporaryFileExt. Result:', result);
            }

            const error = JSON.stringify(result.error);
            if (window.envMode === 'local') {
                console.log('Inside deleteTemporaryFileExt. Error:', error);
            }

            throw new Error(error);
        }

        return response;

    } catch (error: any) {
        if (window.envMode === 'local') {
            console.error('Error in deleteTemporaryFileDO:', error);
        }
        throw error;
    }
}

/**
 * Deletes a temporary local file.
 *
 * @param file - The file to be deleted.
 * @returns A promise that resolves to the response from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function deleteTemporaryFileLocal(file: File): Promise<Response> {
    if (window.envMode === 'local') {
        console.log('Inside deleteTemporaryFileLocal', file);
    }

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

        if (window.envMode === 'local') {
            console.info('response:', response);
            console.info('response.status:', response.status);
        }

        if (!response.ok) {
            const result = await response.json();

            if (window.envMode === 'local') {
                console.log('Inside deleteTemporaryFileLocal. Result:', result);
            }

            throw result;
        }

        return response;

    } catch (error: any) {

        if (window.envMode === 'local') {
            console.error('Error in deleteTemporaryFileLocal:', error);
        }

        throw error;
    }
}

