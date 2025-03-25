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
 * Base Upload Handler
 *
 * Provides core upload functionality that can be extended
 * by specialized handlers. Implements shared error handling
 * and upload mechanics.
 *
 * @class BaseUploadHandler
 */
export class BaseUploadHandler {
    /**
     * Initializes the handler with CSRF token from meta tag
     * @throws {Error} If CSRF token is not found in the DOM
     */
    constructor() {
        this.maxRetries = 3;
        const tokenElement = document.querySelector('meta[name="csrf-token"]');
        if (!tokenElement)
            throw new Error("CSRF token not found in DOM");
        this.csrfToken = tokenElement.getAttribute("content") || "";
    }
    /**
     * Generic upload method that can be used or overridden in specific handlers
     *
     * @param {string} endpoint - The URL endpoint for the upload
     * @param {FormData} formData - The FormData containing the file and metadata
     * @returns {Promise<{error: UploadError | null, response: Response | boolean, success: boolean}>}
     */
    performUpload(endpoint_1, formData_1) {
        return __awaiter(this, arguments, void 0, function* (endpoint, formData, attempt = 1) {
            let errorData = null;
            let success = true;
            console.log(`Performing upload to ${endpoint}, attempt ${attempt}/${this.maxRetries}`);
            try {
                const response = yield fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "X-CSRF-TOKEN": this.csrfToken,
                        "Accept": "application/json",
                    },
                    body: formData,
                });
                if (!response.ok) {
                    const contentType = response.headers.get("content-type");
                    if (contentType && contentType.includes("application/json")) {
                        errorData = yield response.json(); // Server responded with JSON
                    }
                    else {
                        const rawErrorData = yield response.text(); // Server responded with HTML/text
                        errorData = {
                            message: (window.uploadProcessingError || "Error processing the upload"),
                            details: rawErrorData,
                            state: "unknown",
                            errorCode: "unexpected_response",
                            blocking: "blocking",
                        };
                    }
                    success = false;
                    // Retry logic for transient errors
                    if (attempt < this.maxRetries && this.isRetryableError(response.status)) {
                        console.log(`Retrying upload, attempt ${attempt + 1}/${this.maxRetries}`);
                        // Exponential backoff: 1s, 2s, 4s, etc.
                        yield new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt - 1) * 1000));
                        return this.performUpload(endpoint, formData, attempt + 1);
                    }
                }
                return { error: errorData, response, success };
            }
            catch (error) {
                // Handle network or fetch errors
                errorData = {
                    message: "Error during upload request",
                    details: error instanceof Error ? error.message : String(error),
                    state: "network",
                    errorCode: "fetch_error",
                    blocking: "blocking",
                };
                // Retry on network errors
                if (attempt < this.maxRetries) {
                    console.log(`Network error, retrying upload, attempt ${attempt + 1}/${this.maxRetries}`);
                    yield new Promise(resolve => setTimeout(resolve, Math.pow(2, attempt - 1) * 1000));
                    return this.performUpload(endpoint, formData, attempt + 1);
                }
                return { error: errorData, response: false, success: false };
            }
        });
    }
    /**
     * Determines if an error should trigger a retry attempt
     * @param {number} statusCode - HTTP status code from response
     * @returns {boolean} - True if the error is retriable
     */
    isRetryableError(statusCode) {
        // Retry on server errors and some specific client errors
        return statusCode >= 500 || statusCode === 429 || statusCode === 408;
    }
    /**
     * Abstract method that must be implemented by specific handlers
     *
     * @param {File} file - The file to upload
     * @param {string} endpoint - The specific upload endpoint URL
     * @returns {Promise<{error: UploadError | null, response: Response | boolean, success: boolean}>}
     */
    handleUpload(file, endpoint) {
        return __awaiter(this, void 0, void 0, function* () {
            const formData = new FormData();
            formData.append('file', file);
            return this.performUpload(endpoint, formData);
        });
    }
    /**
     * Sets the maximum number of retry attempts
     * @param {number} maxRetries - Maximum number of retries on failure
     */
    setMaxRetries(maxRetries) {
        this.maxRetries = maxRetries;
    }
}
//# sourceMappingURL=BaseUploadHandler.js.map