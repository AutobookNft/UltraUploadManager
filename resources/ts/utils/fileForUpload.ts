/**
 * Interface representing an error during file upload.
 */
interface UploadError {
    message: string;
    details?: string;
    state?: string;
    errorCode?: string;
    blocking?: string;
}

/**
 * Interface representing the result of a file upload operation.
 */
interface FileUploadResult {
    error: UploadError | false; // Error details or false if no error
    response: Response | false; // Fetch response or false if failed
    success: boolean; // Indicates if the upload was successful
}

/**
 * Function to upload a file server-side.
 * @param formData - The form data containing the file to upload.
 * @returns A promise resolving to an object containing the upload result, response, and any errors.
 * @throws {Error} If the CSRF token is not defined.
 */
export async function fileForUpload(formData: FormData): Promise<FileUploadResult> {
    let errorData: UploadError | null = null;
    let success: boolean = true;

    // Check if CSRF token is defined
    if (!window.csrfToken) {
        throw new Error('CSRF token is not defined');
    }

    // Log for local environment
    if (window.envMode === 'local') {
        console.log('dentro fileForUpload');
    }

    // Log file name in local environment with improved typing
    if (window.envMode === 'local') {
        const file = formData.get('file') as File | null;
        console.log('in fileForUpload: formData:', file?.name);
    }

    try {
        const response: Response = await fetch('/uploading-files', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': window.csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        });

        const contentType = response.headers.get('content-type');
        if (window.envMode === 'local') {
            console.log('Content-Type:', contentType);
        }

        if (!response.ok) {
            if (contentType && contentType.includes('application/json')) {
                errorData = await response.json() as UploadError; // Cast to UploadError assuming server returns compatible structure
                success = false;
            } else {
                const rawErrorData = await response.text();
                errorData = {
                    message: window.invalidServerResponse || 'The server returned an invalid or unexpected response.',
                    details: rawErrorData,
                    state: 'unknown',
                    errorCode: 'unexpected_response',
                    blocking: 'blocking',
                };
                success = false;
            }

            return { error: errorData, response, success };
        }

        return { error: false, response, success };
    } catch (error) {
        if (window.envMode === 'local') {
            console.error('Error in fileForUpload:', error);
        }

        // Convert the generic error to UploadError
        const uploadError: UploadError = error instanceof Error
            ? { message: error.message, errorCode: 'fetch_error' }
            : { message: 'Unknown error occurred', errorCode: 'unknown' };

        return { error: uploadError, response: false, success: false };
    }
}
