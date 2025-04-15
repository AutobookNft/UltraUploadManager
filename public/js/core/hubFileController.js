var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { EGIUploadHandler } from "../handlers/EGIUploadHandler";
import { EPPUploadHandler } from "../handlers/EPPUploadHandler";
import { UtilityUploadHandler } from "../handlers/UtilityUploadHandler";
import { BaseUploadHandler } from "../handlers/BaseUploadHandler";
/**
 * Hub File Controller
 *
 * Central controller that manages different types of file uploads
 * by routing them to appropriate specialized handlers.
 * Implements a Factory + Strategy pattern for extensibility.
 *
 * @class HubFileController
 */
export class HubFileController {
    /**
     * Initializes the controller with predefined handlers and endpoints
     */
    constructor() {
        this.handlers = {
            'egi': new EGIUploadHandler(),
            'epp': new EPPUploadHandler(),
            'utility': new UtilityUploadHandler(),
            'default': new BaseUploadHandler(),
        };
        this.endpoints = {
            'egi': '/uploading/egi',
            'epp': '/uploading/epp',
            'utility': '/uploading/utility',
            'default': '/uploading/default', // Fallback
        };
    }
    /**
     * Processes a file upload by determining the appropriate handler
     * and delegating the upload operation
     *
     * @param {File} file - The file to be uploaded
     * @param {string} uploadType - The type of upload (egi, epp, utility, default)
     * @returns {Promise<{error: UploadError | null, response: Response | boolean, success: boolean}>}
     */
    handleFileUpload(file, uploadType) {
        return __awaiter(this, void 0, void 0, function* () {
            const handler = this.handlers[uploadType] || this.handlers['default'];
            const endpoint = this.endpoints[uploadType] || this.endpoints['default'];
            console.log(`Handling file upload with handler: ${handler.constructor.name} to endpoint: ${endpoint}`);
            try {
                const { error, response, success } = yield handler.handleUpload(file, endpoint);
                return { error, response, success };
            }
            catch (error) {
                const errorData = {
                    message: "Error during upload processing",
                    details: error instanceof Error ? error.message : String(error),
                    state: "handler",
                    errorCode: "handler_error",
                    blocking: "blocking",
                };
                return { error: errorData, response: false, success: false };
            }
        });
    }
    /**
     * Registers a new upload handler for a specific upload type
     *
     * @param {string} uploadType - The type identifier for this handler
     * @param {BaseUploadHandler} handler - The handler instance
     * @param {string} endpoint - The endpoint URL for this upload type
     */
    registerHandler(uploadType, handler, endpoint) {
        this.handlers[uploadType] = handler;
        this.endpoints[uploadType] = endpoint;
    }
}
//# sourceMappingURL=hubFileController.js.map