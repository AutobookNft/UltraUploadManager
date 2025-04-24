/**
 * 📜 Oracode TypeScript Handler: EGIUploadHandler
 * Specializes the base upload handler for EGI-specific file uploads within the frontend.
 * Extends BaseUploadHandler to leverage core fetch and retry logic.
 * Adheres to Oracode v1.5 documentation standards for frontend code.
 * Uses the globally defined UploadError interface.
 *
 * @package     @ultra-ts/handlers // Assumed base UUM TS namespace/alias
 * @version     1.1.0 // Version reflecting corrected signature and Oracode docs
 * @author      Fabio Cherici & Padmin D. Curtis
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @since       2025-04-24 // Date reflects latest correction
 *
 * @purpose     🎯 To prepare the FormData specifically for EGI uploads (adding `uploadType='egi'`)
 *              and initiate the upload process to the designated EGI backend endpoint (`/upload/egi`)
 *              by calling the base class's `performUpload` method.
 *
 * @context     🧩 Instantiated and invoked by the `HubFileController` (or similar frontend router)
 *              when the application context indicates an EGI upload is required. Operates within the browser.
 *
 * @state       💾 Primarily stateless, inherits retry state from BaseUploadHandler. Manages internal
 *              `formData` preparation.
 *
 * @feature     🗝️ Extends `BaseUploadHandler` (TS).
 * @feature     🗝️ Overrides `handleUpload` with a signature matching the base class.
 * @feature     🗝️ Ensures correct `FormData` preparation for EGI (adds `uploadType='egi'`).
 * @feature     🗝️ Explicitly targets the `/upload/egi` backend endpoint via `performUpload`.
 * @feature     🗝️ Handles receiving either a `File` or pre-existing `FormData`.
 * @feature     🗝️ Leverages base class `fetch` and retry logic.
 *
 * @signal      🚦 Returns a Promise resolving to `{ error: UploadError | null; response: Response | boolean; success: boolean }`
 *              using the globally defined `UploadError` type.
 * @signal      🚦 Logs progress and warnings/errors to the browser console.
 *
 * @privacy     🛡️ Prepares FormData containing the file and CSRF token (if available).
 * @privacy     🛡️ Adds non-sensitive 'uploadType' identifier.
 * @privacy     🛡️ `@privacy-internal`: Handles File/FormData, csrfToken. Uses global UploadError type.
 * @privacy     🛡️ `@privacy-purpose`: Prepare data payload for secure transmission to the EGI backend endpoint.
 * @privacy     🛡️ `@privacy-data`: File object/content, CSRF token, 'uploadType' string.
 * @privacy     🛡️ `@privacy-consideration`: Assumes `performUpload` handles HTTPS. CSRF token read from global scope.
 *
 * @dependency  🤝 `BaseUploadHandler` (TS) from './BaseUploadHandler'.
 * @dependency  🤝 Global `csrfToken` from '../index'.
 * @dependency  🤝 Global type `UploadError` defined in `global.d.ts`.
 * @dependency  🤝 Browser APIs: `File`, `FormData`, `Response`, `fetch`, `console`, `Promise`.
 *
 * @testing     🧪 Unit Test: Mock `BaseUploadHandler`. Instantiate `EGIUploadHandler`.
 * @testing     🧪 Test `handleUpload` signature matches Base.
 * @testing     🧪 Test `handleUpload` passing `File`: Assert `performUpload` called with '/upload/egi' and correct FormData.
 * @testing     🧪 Test `handleUpload` passing `FormData`: Assert `performUpload` called with '/upload/egi' and correct FormData.
 *
 * @rationale   💡 Provides a specialized frontend handler for EGI uploads adhering to the Strategy pattern
 *              and Oracode standards, ensuring correct data preparation before invoking base upload logic.
 */
import { BaseUploadHandler } from "./BaseUploadHandler";
import { csrfToken } from '../index'; // Correct relative import within UUM

// No import needed for UploadError as it's in global.d.ts

export class EGIUploadHandler extends BaseUploadHandler {

    /**
     * 🚀 Initializes the EGI-specific upload handler.
     * @constructor
     */
    constructor() {
        super();
        console.log('[EGIUploadHandler] Initialized.');
    }

    /**
     * 🚀 Handles the EGI-specific upload preparation and delegates to the core upload mechanism.
     * Ensures the correct endpoint and FormData are used for EGI uploads.
     * @purpose Prepare FormData for EGI upload and call the base performUpload method with the correct EGI endpoint.
     *
     * --- Logic ---
     * 1. Define the specific EGI endpoint `/upload/egi`.
     * 2. Check if the input `fileOrFormData` is a `File` or `FormData`.
     * 3. Prepare `formData` accordingly, ensuring 'file', '_token', and 'uploadType=egi' are present.
     * 4. Log preparation steps for debugging.
     * 5. Call the inherited `performUpload` method, explicitly passing the prepared `formData` and the hardcoded `egiEndpoint`.
     * 6. Return the result Promise from `performUpload`.
     * --- End Logic ---
     *
     * @param {File | FormData} fileOrFormData - The file object or pre-constructed FormData to upload.
     * @param {string} endpoint - The endpoint parameter from the base class signature (its value is ignored here as we use the fixed EGI endpoint).
     * @returns {Promise<{ error: UploadError | null; response: Response | boolean; success: boolean }>} A Promise resolving to the upload result, using the global UploadError type.
     *
     * @override Overrides BaseUploadHandler.handleUpload to ensure EGI-specific endpoint and FormData preparation, while matching the required signature.
     *
     * @privacy-purpose To correctly format the data payload for the EGI backend endpoint.
     * @privacy-data Handles File/FormData, adds 'uploadType', reads global csrfToken.
     * @privacy-lawfulBasis Necessary to fulfill the upload request.
     */
    async handleUpload(
        fileOrFormData: File | FormData,
        endpoint: string // <-- SIGNATURE MATCHES BASE CLASS NOW
    ): Promise<{
        error: UploadError | null; // Uses global UploadError
        response: Response | boolean;
        success: boolean
    }> {
        const egiEndpoint = "/upload/egi"; // EGI-specific backend endpoint
        let formData: FormData;
        const logPrefix = "[EGIUploadHandler]";

        console.log(`${logPrefix} handleUpload called. Base endpoint param (ignored): ${endpoint}. Using fixed EGI endpoint: ${egiEndpoint}`);

        // 1. Prepare FormData correctly (Logic remains the same)
        if (fileOrFormData instanceof File) {
            console.log(`${logPrefix} Received File object: ${fileOrFormData.name}. Creating new FormData.`);
            formData = new FormData();
            formData.append("file", fileOrFormData);
            if (csrfToken) {
                formData.append("_token", csrfToken);
                 console.log(`${logPrefix} Appended CSRF token.`);
            } else {
                 console.warn(`${logPrefix} csrfToken not found globally.`);
            }
            formData.append("uploadType", "egi");
            console.log(`${logPrefix} Appended uploadType='egi'.`);
        } else {
             console.log(`${logPrefix} Received FormData object. Ensuring 'uploadType=egi'.`);
             formData = fileOrFormData;
             if (!formData.has("uploadType")) {
                 formData.append("uploadType", "egi");
                 console.log(`${logPrefix} Appended uploadType='egi'.`);
             }
             if (!formData.has('_token') && csrfToken) {
                 console.warn(`${logPrefix} Received FormData might be missing '_token'. Appending if possible.`);
                 formData.append("_token", csrfToken);
             }
             if (!formData.has('file')) {
                 console.error(`${logPrefix} CRITICAL: Received FormData is missing the 'file' part.`);
             }
        }

        // 2. Call performUpload from the base class with the EGI endpoint
        console.log(`${logPrefix} Calling performUpload with specific endpoint: ${egiEndpoint}`);
        return await this.performUpload(egiEndpoint, formData); // Explicitly use egiEndpoint
    }
}