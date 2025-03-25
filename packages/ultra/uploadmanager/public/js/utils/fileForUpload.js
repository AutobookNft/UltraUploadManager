var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
/**
 * Function to upload a file server-side.
 * @param formData - The form data containing the file to upload.
 * @returns A promise resolving to an object containing the upload result, response, and any errors.
 * @throws {Error} If the CSRF token is not defined.
 */
export function fileForUpload(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        let errorData = null;
        let success = true;
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
            const file = formData.get('file');
            console.log('in fileForUpload: formData:', file === null || file === void 0 ? void 0 : file.name);
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
                console.log('Content-Type:', contentType);
            }
            if (!response.ok) {
                if (contentType && contentType.includes('application/json')) {
                    errorData = (yield response.json()); // Cast to UploadError assuming server returns compatible structure
                    success = false;
                }
                else {
                    const rawErrorData = yield response.text();
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
        }
        catch (error) {
            if (window.envMode === 'local') {
                console.error('Error in fileForUpload:', error);
            }
            // Convert the generic error to UploadError
            const uploadError = error instanceof Error
                ? { message: error.message, errorCode: 'fetch_error' }
                : { message: 'Unknown error occurred', errorCode: 'unknown' };
            return { error: uploadError, response: false, success: false };
        }
    });
}
//# sourceMappingURL=fileForUpload.js.map