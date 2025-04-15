import { csrfToken } from '../index';

declare const window: any;

type FetchResponse = Response;

/**
 * Saves a temporary file locally by making a POST request to the server.
 *
 * @param formData - The form data containing the file to upload.
 * @returns A promise that resolves to the response from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export async function saveLocalTempFile(formData: FormData): Promise<FetchResponse> {
    if (window.envMode === 'local') {
        console.log('Inside saveLocalTempFile');
    }

    try {
        const response = await fetch('/upload-temp', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        });

        if (window.envMode === 'local') {
            console.log('Inside saveLocalTempFile. Response:', response);
        }

        if (!response.ok) {
            const result = await response.json();

            if (window.envMode === 'local') {
                console.log('Inside saveLocalTempFile. Result:', result);
            }

            const error = JSON.stringify(result.error);
            if (window.envMode === 'local') {
                console.log('Inside saveLocalTempFile. Error:', error);
            }

            throw new Error(error);
        }

        return response;

    } catch (error: any) {

        if (window.envMode === 'local') {
            console.error('Error in saveLocalTempFile:', error);
        }
        throw error;

    }
}
