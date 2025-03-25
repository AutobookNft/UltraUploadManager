/**
 * Temporary File Management Module
 *
 * Handles the creation and deletion of temporary files
 * during the upload process
 */
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
 * Saves a file temporarily to the server
 *
 * @param {FormData} formData - Form data containing the file to save
 * @returns {Promise<Response>} - Server response
 * @throws {Error} If saving fails
 */
export function saveLocalTempFile(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        console.log('Saving temporary file locally');
        try {
            const response = yield fetch('/upload-temp', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });
            if (!response.ok) {
                const result = yield response.json();
                const error = JSON.stringify(result.error);
                console.error('Error in saveLocalTempFile. Error:', error);
                throw new Error(error);
            }
            return response;
        }
        catch (error) {
            console.error('Error in saveLocalTempFile:', error);
            throw error;
        }
    });
}
/**
 * Deletes a temporary file from Digital Ocean storage
 *
 * @param {string} file - File path or identifier to delete
 * @returns {Promise<Response>} - Server response
 * @throws {Error} If deletion fails
 */
export function deleteTemporaryFileExt(file) {
    return __awaiter(this, void 0, void 0, function* () {
        console.log('Deleting temporary file from external storage:', file);
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', csrfToken);
        try {
            const response = yield fetch('/delete-temporary-file-DO', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                body: formData
            });
            if (!response.ok) {
                const result = yield response.json();
                const error = JSON.stringify(result.error);
                console.error('Error in deleteTemporaryFileExt. Error:', error);
                throw new Error(error);
            }
            return response;
        }
        catch (error) {
            console.error('Error in deleteTemporaryFileDO:', error);
            throw error;
        }
    });
}
/**
 * Deletes a temporary local file
 *
 * @param {File} file - The file to delete
 * @returns {Promise<Response>} - Server response
 * @throws {Error} If deletion fails
 */
export function deleteTemporaryFileLocal(file) {
    return __awaiter(this, void 0, void 0, function* () {
        console.log('Deleting temporary local file:', file.name);
        const formData = new FormData();
        formData.append('file', file);
        formData.append('_token', csrfToken);
        try {
            const response = yield fetch('/delete-temporary-file-local', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
            if (!response.ok) {
                const result = yield response.json();
                console.error('Error in deleteTemporaryFileLocal. Result:', result);
                throw result;
            }
            return response;
        }
        catch (error) {
            console.error('Error in deleteTemporaryFileLocal:', error);
            throw error;
        }
    });
}
/**
 * Sets up event handlers to clean up temporary files when page is closed
 * Attaches to window beforeunload event
 */
export function setupTempFileCleanup() {
    window.addEventListener('beforeunload', (e) => __awaiter(this, void 0, void 0, function* () {
        const getFiles = () => {
            const fileInput = document.getElementById('files');
            return fileInput ? fileInput.files : null;
        };
        const files = getFiles();
        if (files === null || files === void 0 ? void 0 : files.length) {
            e.preventDefault();
            e.returnValue = '';
            // Clean up all temporary files
            for (const file of files) {
                try {
                    yield deleteTemporaryFileLocal(file);
                }
                catch (error) {
                    console.error(`Error deleting temporary file ${file.name}:`, error);
                }
            }
        }
    }));
}
//# sourceMappingURL=deleteTemporaryFiles.js.map