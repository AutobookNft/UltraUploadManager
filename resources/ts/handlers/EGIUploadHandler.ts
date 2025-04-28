/**
 * 📜 Oracode TypeScript Handler: EGIUploadHandler
 * Specializes the base upload handler for EGI-specific file uploads within the frontend.
 * Gathers metadata from the DOM, prepares FormData, and initiates upload via the base class.
 * Extends BaseUploadHandler to leverage core fetch and retry logic.
 * Adheres to Oracode v1.5 documentation standards for frontend code.
 * Uses the globally defined UploadError interface.
 *
 * @package     @ultra-ts/handlers // Or appropriate alias/path for UUM TS handlers
 * @version     1.2.0 // Reflects added metadata logic and Oracode docs
 * @author      Fabio Cherici & Padmin D. Curtis
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @since       2025-04-24
 *
 * @purpose     🎯 To prepare the complete FormData for EGI uploads (including file and user-entered metadata)
 *              and initiate the upload process to the designated EGI backend endpoint (`/upload/egi`)
 *              by calling the base class's `performUpload` method.
 *
 * @context     🧩 Instantiated and invoked by the `HubFileController` (or similar frontend router)
 *              when the application context indicates an EGI upload is required. Operates within the browser
 *              environment and interacts with specific DOM elements to retrieve metadata.
 *
 * @state       💾 Manages `formData` preparation locally during execution. Relies on DOM state for metadata.
 *              Inherits retry state from BaseUploadHandler.
 *
 * @feature     🗝️ Extends `BaseUploadHandler` (TS).
 * @feature     🗝️ Overrides `handleUpload` with a signature matching the base class.
 * @feature     🗝️ Gathers EGI metadata (title, description, price, etc.) directly from DOM elements.
 * @feature     🗝️ Prepares a complete `FormData` object including the file and all gathered metadata.
 * @feature     🗝️ Adds `uploadType='egi'` identifier to the FormData.
 * @feature     🗝️ Explicitly targets the `/upload/egi` backend endpoint.
 * @feature     🗝️ Handles receiving either a `File` or pre-existing `FormData`.
 * @feature     🗝️ Delegates actual `fetch` and retry logic to `BaseUploadHandler.performUpload`.
 *
 * @signal      🚦 Returns a Promise resolving to `{ error: UploadError | null; response: Response | boolean; success: boolean }`
 *              using the globally defined `UploadError` type.
 * @signal      🚦 Returns an immediate error object if critical preparation steps fail (missing file, DOM access error).
 * @signal      🚦 Logs progress, warnings, and errors extensively to the browser console.
 *
 * @privacy     🛡️ Handles user-selected file data.
 * @privacy     🛡️ Reads and packages user-entered metadata (title, description, price, dates) from the DOM.
 * @privacy     🛡️ Reads global CSRF token.
 * @privacy     🛡️ `@privacy-internal`: Handles File/FormData, csrfToken, user-input metadata strings/booleans. Uses global UploadError.
 * @privacy     🛡️ `@privacy-purpose`: Data (file + metadata) is prepared solely for secure transmission to the backend EGI upload endpoint for processing and EGI creation.
 * @privacy     🛡️ `@privacy-data`: File object/content, CSRF token, 'uploadType' string, user-entered title, description, price, publish_date, publish_now flag.
 * @privacy     🛡️ `@privacy-lawfulBasis`: Necessary to fulfill the user's explicit request to upload an EGI file with its associated details.
 * @privacy     🛡️ `@privacy-consideration`: Assumes DOM element IDs for metadata are stable and correctly mapped. DOM interaction should be handled securely to prevent injection if data sources were dynamic (not the case here). Assumes `performUpload` uses HTTPS.
 *
 * @dependency  🤝 `BaseUploadHandler` (TS) from './BaseUploadHandler'.
 * @dependency  🤝 Global `csrfToken` from '../index'.
 * @dependency  🤝 Global type `UploadError` defined in `global.d.ts`.
 * @dependency  🤝 Specific DOM Element IDs expected on the page (e.g., 'egi-title', 'egi-description', etc.).
 * @dependency  🤝 Browser APIs: `File`, `FormData`, `Response`, `fetch`, `console`, `Promise`, `document.getElementById`.
 *
 * @testing     🧪 Unit Test: Mock `BaseUploadHandler`. Mock `document.getElementById`.
 * @testing     🧪 Test `handleUpload` signature.
 * @testing     🧪 Test `handleUpload` with `File`: Assert DOM is queried, `performUpload` called with '/upload/egi' and FormData containing file, token, type, AND mocked metadata.
 * @testing     🧪 Test `handleUpload` with `FormData`: Assert DOM is queried, `performUpload` called with '/upload/egi' and FormData containing original data + type + metadata.
 * @testing     🧪 Test `handleUpload` when DOM elements are missing: Assert immediate error is returned.
 * @testing     🧪 Test `handleUpload` when input `FormData` lacks 'file': Assert immediate error is returned.
 *
 * @rationale   💡 Provides the specialized frontend logic for EGI uploads, encapsulating metadata gathering
 *              and FormData preparation before leveraging the generic, reusable upload mechanism of the base class.
 *              Keeps EGI-specific details (DOM IDs, metadata keys) localized to this handler.
 */
import { BaseUploadHandler } from "./BaseUploadHandler";
import { csrfToken } from '../index'; // Correct relative import within UUM/EGI package structure

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
     * 🚀 Handles the EGI-specific upload: gathers metadata, prepares FormData, and delegates upload.
     * Matches the signature of the overridden base method.
     * @purpose Gather EGI metadata from DOM, prepare complete FormData, and call base performUpload method with the EGI endpoint.
     *
     * --- Logic ---
     * 1. Define the fixed EGI endpoint `/upload/egi`.
     * 2. Initialize or receive `FormData`, ensuring the `file` part is present.
     * 3. Append CSRF token (if available) and `uploadType='egi'`.
     * 4. Query the DOM for specific input elements (title, description, price, etc.). Handle potential DOM errors.
     * 5. Append the retrieved metadata values to the `FormData` object.
     * 6. Log preparation steps.
     * 7. Call the inherited `performUpload` method with the completed `FormData` and the fixed `egiEndpoint`.
     * 8. Return the result Promise from `performUpload`.
     * --- End Logic ---
     *
     * @param {File | FormData} fileOrFormData - The file object or pre-constructed FormData to upload.
     * @param {string} endpoint - Endpoint parameter from base signature (value ignored, uses '/upload/egi').
     * @returns {Promise<{ error: UploadError | null; response: Response | boolean; success: boolean }>} Promise resolving to the upload result. Uses global UploadError type.
     *
     * @override Overrides BaseUploadHandler.handleUpload to perform EGI-specific metadata gathering and FormData preparation before calling the base upload logic.
     *
     * @sideEffect Reads values from DOM elements ('egi-title', 'egi-description', etc.). Logs to console.
     * @privacy-purpose To gather user-provided metadata and package it with the file for EGI creation request.
     * @privacy-data Handles File/FormData, adds 'uploadType', reads csrfToken, reads user input (title, desc, price, etc.) from DOM.
     * @privacy-lawfulBasis Necessary to fulfill the EGI upload request including user-specified details.
     */
    async handleUpload(
        fileOrFormData: File | FormData,
        endpoint: string // <-- SIGNATURE MATCHES BASE CLASS
    ): Promise<{
        error: UploadError | null; // Uses global UploadError
        response: Response | boolean;
        success: boolean
    }> {
        const egiEndpoint = "/upload/egi"; // EGI-specific backend endpoint
        let formData: FormData;
        const logPrefix = "[EGIUploadHandler]";

        console.log(`${logPrefix} handleUpload called. Base endpoint param (ignored): ${endpoint}. Using fixed EGI endpoint: ${egiEndpoint}`);

        // --- 1. Get File and Initialize/Prepare FormData ---
        let theFile: File | null = null;
        if (fileOrFormData instanceof File) {
            formData = new FormData();
            formData.append("file", fileOrFormData);
            theFile = fileOrFormData;
            console.log(`${logPrefix} Initialized new FormData with File: ${theFile?.name}`);
        } else {
            formData = fileOrFormData;
            theFile = formData.get("file") as File; // Attempt to get file for logging
            console.log(`${logPrefix} Received existing FormData. File included: ${!!theFile}`);
            if (!theFile) { // Critical check if FormData was passed without a file
                console.error(`${logPrefix} CRITICAL: Received FormData does not contain 'file'. Cannot proceed.`);
                return { error: { message: "Internal Error: Input FormData missing file.", errorCode: "FRONTEND_PREP_ERROR", blocking: "blocking" }, response: false, success: false };
            }
        }

        // --- 2. Add Token and Type ---
        if (csrfToken) {
            if (!formData.has("_token")) {
                formData.append("_token", csrfToken);
                console.log(`${logPrefix} Appended CSRF token.`);
            }
        } else {
            console.warn(`${logPrefix} csrfToken not found globally.`);
        }
        if (!formData.has("uploadType")) {
            formData.append("uploadType", "egi");
            console.log(`${logPrefix} Appended uploadType='egi'.`);
        }

        // --- 3. Gather and Append Metadata from DOM ---
        console.log(`${logPrefix} Gathering EGI metadata from form...`);
        try {
            // Use specific and robust selectors (ensure these IDs exist in your Blade view)
            const titleInput = document.querySelector<HTMLInputElement>('#egi-title'); // More specific selector if needed
            const descriptionInput = document.querySelector<HTMLTextAreaElement>('#egi-description');
            const priceInput = document.querySelector<HTMLInputElement>('#egi-floor-price');
            const publishDateInput = document.querySelector<HTMLInputElement>('#egi-date');
            const publishNowInput = document.querySelector<HTMLInputElement>('#egi-publish');

            // Append to FormData, using empty string as fallback if element not found or value is null/undefined
            formData.append('egi-title', titleInput?.value?.trim() ?? '');
            formData.append('egi-description', descriptionInput?.value?.trim() ?? '');
            formData.append('egi-floor-price', priceInput?.value ?? '0'); // Default to '0' if missing? Backend should validate.
            formData.append('egi-date', publishDateInput?.value ?? ''); // Backend handles parsing/validation
            formData.append('egi-publish', publishNowInput?.checked ? '1' : '0'); // Send '1' or '0'

            // Example for another field
            // const customFieldInput = document.querySelector<HTMLInputElement>('#egi-custom-field');
            // formData.append('custom_field', customFieldInput?.value ?? '');

            console.log(`${logPrefix} Metadata added to FormData.`);

        } catch (domError) {
            console.error(`${logPrefix} Error accessing DOM elements for metadata:`, domError);
            return {
                error: {
                    message: "Internal Error: Failed to gather metadata from page.",
                    errorCode: "FRONTEND_DOM_ERROR",
                    blocking: "blocking",
                    details: domError instanceof Error ? domError.message : String(domError)
                },
                response: false,
                success: false
            };
        }
        // --- End Metadata Gathering ---

        // 4. Call performUpload with complete FormData and specific EGI endpoint
        console.log(`${logPrefix} Calling performUpload with complete FormData for endpoint: ${egiEndpoint}`);
        return await this.performUpload(egiEndpoint, formData);
    }
}
