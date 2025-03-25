import { EGIUploadHandler } from "../handlers/EGIUploadHandler";
import { EPPUploadHandler } from "../handlers/EPPUploadHandler";
import { UtilityUploadHandler } from "../handlers/UtilityUploadHandler";
import { BaseUploadHandler } from "../handlers/BaseUploadHandler";

/**
 * Central controller for managing different types of file uploads.
 *
 * This class acts as a hub that routes file uploads to specialized handlers based on the current URL path.
 * It implements a combination of the Factory and Strategy design patterns to provide extensibility and flexibility.
 * New handlers can be registered dynamically for specific URI patterns.
 *
 * @class HubFileController
 */
export class HubFileController {
    /**
     * A dictionary mapping URI patterns to their corresponding upload handlers.
     * @private
     * @type {{ [pathPattern: string]: BaseUploadHandler }}
     */
    private pathHandlers: { [pathPattern: string]: BaseUploadHandler };

    /**
     * Initializes the controller with a set of predefined upload handlers.
     *
     * Sets up default handlers for specific paths such as EGI, EPP, and utility uploads.
     * Additional handlers can be added later using `registerHandler`.
     */
    constructor() {
        this.pathHandlers = {
            '/uploading/egi': new EGIUploadHandler(),
            '/uploading/epp': new EPPUploadHandler(),
            '/uploading/utility': new UtilityUploadHandler(),
            // Additional handlers can be registered here or via `registerHandler`.
        };
    }

    /**
     * Processes a file upload by routing it to the appropriate handler based on the current URL path.
     *
     * Matches the current window location path against registered patterns and delegates the upload
     * to the corresponding handler. Falls back to a default handler if no specific match is found.
     *
     * @param {File|FormData} fileOrFormData - The file to be uploaded or a complete FormData object.
     * @returns {Promise<{ error: UploadError | null, response: Response | boolean, success: boolean }>}
     *          A promise resolving to an object containing:
     *          - `error`: An error object if the upload fails, or `null` if successful.
     *          - `response`: The server response (typically a `Response` object) or a boolean indicating success.
     *          - `success`: A boolean indicating whether the upload was successful.
     * @throws {Error} Propagates any unhandled errors from the handler for further processing.
     */
    async handleFileUpload(fileOrFormData: File | FormData): Promise<{
        error: UploadError | null;
        response: Response | boolean;
        success: boolean;
    }> {
        const currentPath = window.location.pathname;

        // Default handler and endpoint if no specific match is found
        let handler: BaseUploadHandler = new BaseUploadHandler();
        let endpoint: string = '/uploading/default';

        // Search for a matching handler based on the current path
        for (const [pathPattern, handlerInstance] of Object.entries(this.pathHandlers)) {
            if (currentPath.includes(pathPattern)) {
                handler = handlerInstance;
                endpoint = pathPattern;
                break;
            }
        }

        console.log(`Routing to handler: ${handler.constructor.name} for path: ${currentPath}`);

        try {
            // Delegate the upload to the selected handler
            return await handler.handleUpload(fileOrFormData, endpoint);
        } catch (error) {
            // Construct a detailed error object for failed uploads
            const errorData: UploadError = {
                message: window.errorDuringUpload || "Error during upload processing",
                details: error instanceof Error ? error.message : String(error),
                state: "handler",
                errorCode: "handler_error",
                blocking: "blocking",
            };
            return { error: errorData, response: false, success: false };
        }
    }

    /**
     * Registers a new upload handler for a specific URI pattern.
     *
     * Allows dynamic extension of the controller by associating a new handler with a given path pattern.
     * Overwrites any existing handler for the same pattern.
     *
     * @param {string} pathPattern - The URI pattern to match (e.g., "/uploading/custom").
     * @param {BaseUploadHandler} handler - The handler instance responsible for processing uploads for this pattern.
     */
    registerHandler(pathPattern: string, handler: BaseUploadHandler): void {
        this.pathHandlers[pathPattern] = handler;
    }
}
