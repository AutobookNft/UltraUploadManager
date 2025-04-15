var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { csrfToken } from '../index';
/**
 * Saves a temporary file locally by making a POST request to the server.
 *
 * @param formData - The form data containing the file to upload.
 * @returns A promise that resolves to the response from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export function saveLocalTempFile(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        if (window.envMode === 'local') {
            console.log('Inside saveLocalTempFile');
        }
        try {
            const response = yield fetch('/upload-temp', {
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
                const result = yield response.json();
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
        }
        catch (error) {
            if (window.envMode === 'local') {
                console.error('Error in saveLocalTempFile:', error);
            }
            throw error;
        }
    });
}
//# sourceMappingURL=saveLocalTempFile.js.map