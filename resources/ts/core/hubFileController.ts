import { EGIUploadHandler } from "../handlers/EGIUploadHandler";
import { EPPUploadHandler } from "../handlers/EPPUploadHandler";
import { UtilityUploadHandler } from "../handlers/UtilityUploadHandler";
import { BaseUploadHandler } from "../handlers/BaseUploadHandler";

/**
 * Central controller for managing different types of file uploads.
 *
 * This class acts as a hub that routes file uploads to specialized handlers based on:
 * 1. A provided upload type (for modal contexts).
 * 2. The current URL path (for endpoint-based uploads).
 * It implements a combination of the Factory and Strategy design patterns to provide extensibility and flexibility.
 * New handlers can be registered dynamically for specific URI patterns or upload types.
 *
 * @class HubFileController
 * @oracode.semantically_coherent Ensures handler selection is clear and predictable.
 * @oracode.testable Handler mapping is deterministic and mockable.
 */
export class HubFileController {
    /**
     * A dictionary mapping URI patterns to their corresponding upload handlers.
     * @private
     */
    private pathHandlers: { [pathPattern: string]: BaseUploadHandler } = {
        '/upload/egi': new EGIUploadHandler(),
        '/upload/epp': new EPPUploadHandler(),
        '/upload/utility': new UtilityUploadHandler(),
    };

    /**
     * A dictionary mapping upload types to their corresponding handlers.
     * @private
     */
    private typeHandlers: { [uploadType: string]: BaseUploadHandler } = {
        'egi': new EGIUploadHandler(),
        'epp': new EPPUploadHandler(),
        'utility': new UtilityUploadHandler(),
    };

    /**
     * Processes a file upload by routing it to the appropriate handler based on:
     * 1. The provided uploadType (if any, typically from modal context).
     * 2. The current URL path.
     * Falls back to a default handler if no specific match is found.
     *
     * @param fileOrFormData - The file to be uploaded or a complete FormData object.
     * @param uploadType - Optional upload type for modal contexts (e.g., 'egi', 'epp', 'utility').
     * @returns A promise resolving to an object containing:
     *          - `error`: An error object if the upload fails, or `null` if successful.
     *          - `response`: The server response or a boolean indicating success.
     *          - `success`: A boolean indicating whether the upload was successful.
     * @throws UploadError Propagates any unhandled errors from the handler for further processing.
     * @oracode.explicitly_intentional Prioritizes uploadType over URL for modal contexts.
     */
    async handleFileUpload(fileOrFormData: File | FormData, uploadType?: string): Promise<{
        error: UploadError | null;
        response: Response | boolean;
        success: boolean;
    }> {
        const currentPath = window.location.pathname;
        let handler: BaseUploadHandler = new BaseUploadHandler();
        let endpoint: string = '/uploading/default';

        // Priority 1: Use uploadType if provided (modal context)
        if (uploadType && this.typeHandlers[uploadType]) {
            handler = this.typeHandlers[uploadType];
            endpoint = `/upload/${uploadType}`;
            console.log(`Routing to handler: ${handler.constructor.name} for upload type: ${uploadType}`);
        } else {
            // Priority 2: Fallback to URL-based routing
            for (const [pathPattern, handlerInstance] of Object.entries(this.pathHandlers)) {
                if (currentPath.includes(pathPattern)) {
                    handler = handlerInstance;
                    endpoint = pathPattern;
                    console.log(`Routing to handler: ${handler.constructor.name} for path: ${currentPath}`);
                    break;
                }
            }
        }

        try {
            return await handler.handleUpload(fileOrFormData, endpoint);
        } catch (error) {
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
     * Registers a new upload handler for a specific URI pattern or upload type.
     *
     * @param pathPattern - The URI pattern to match (e.g., "/uploading/custom").
     * @param uploadType - The upload type to match (e.g., "custom").
     * @param handler - The handler instance responsible for processing uploads.
     */
    registerHandler(pathPattern: string, uploadType: string, handler: BaseUploadHandler): void {
        this.pathHandlers[pathPattern] = handler;
        this.typeHandlers[uploadType] = handler;
    }
}
