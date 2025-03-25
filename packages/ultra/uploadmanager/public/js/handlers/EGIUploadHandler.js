var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { BaseUploadHandler } from "./BaseUploadHandler";
export class EGIUploadHandler extends BaseUploadHandler {
    constructor() {
        super();
        this.maxAttempts = 3;
    }
    handleUpload(file, endpoint) {
        return __awaiter(this, void 0, void 0, function* () {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("_token", this.csrfToken);
            formData.append("uploadType", "egi");
            let attempt = 0;
            let error = null; // Tipizziamo error come UploadError | null
            let response = false;
            let success = false;
            while (attempt < this.maxAttempts && !success) {
                attempt++;
                ({ error, response, success } = yield this.performUpload("/upload/egi", formData));
                if (success) {
                    console.log(`Tentativo ${attempt} riuscito: ${file.name}`);
                    return { error, response, success };
                }
                else {
                    console.warn(`Tentativo ${attempt} fallito: ${(error === null || error === void 0 ? void 0 : error.message) || "Errore sconosciuto"}`);
                }
                if (!success && attempt < this.maxAttempts) {
                    console.log(`Riprovo il tentativo ${attempt + 1}...`);
                }
            }
            return { error, response, success };
        });
    }
}
//# sourceMappingURL=EGIUploadHandler.js.map