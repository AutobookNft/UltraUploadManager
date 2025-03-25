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
export class EPPUploadHandler extends BaseUploadHandler {
    handleUpload(file, endpoint) {
        return __awaiter(this, void 0, void 0, function* () {
            const formData = new FormData();
            // 🔹 Passaggio 1: Cifriamo il file prima dell'upload (simulato con una conversione Base64)
            const encryptedFile = yield this.encryptFile(file);
            formData.append("file", encryptedFile);
            formData.append("_token", this.csrfToken);
            formData.append("uploadType", "epp");
            // 🔹 Passaggio 2: Eseguiamo l'upload con la logica personalizzata
            return yield this.performUpload("/upload/epp", formData);
        });
    }
    /**
     * Sovrascriviamo `performUpload` per personalizzare il comportamento di upload.
     */
    performUpload(endpoint, formData) {
        return __awaiter(this, void 0, void 0, function* () {
            let errorData = null; // Tipizziamo come UploadError | null
            let success = true;
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
                        errorData = yield response.json();
                    }
                    else {
                        errorData = {
                            message: "Il server ha restituito una risposta non valida.",
                            details: yield response.text(),
                            state: "unknown",
                            errorCode: "unexpected_response",
                            blocking: "blocking",
                        };
                    }
                    success = false;
                }
                else {
                    // 🔹 Verifica il token restituito dall'API per confermare l'upload
                    const result = yield response.json();
                    if (!result.verificationToken || result.verificationToken !== "VALID") {
                        success = false;
                        errorData = {
                            message: "Errore: token di verifica non valido.",
                            errorCode: "invalid_token",
                        };
                    }
                }
                // 🔹 Logga l'upload separatamente per gli EPP
                this.logEPPUploadStatus(success, errorData);
                return { error: errorData, response, success };
            }
            catch (error) {
                errorData = {
                    message: "Errore durante la richiesta di upload",
                    details: error instanceof Error ? error.message : String(error),
                    state: "network",
                    errorCode: "fetch_error",
                    blocking: "blocking",
                };
                return { error: errorData, response: false, success: false };
            }
        });
    }
    /**
     * Metodo per cifrare il file (simulazione).
     */
    encryptFile(file) {
        return __awaiter(this, void 0, void 0, function* () {
            // Simuliamo una cifratura base64 per questo esempio
            const arrayBuffer = yield file.arrayBuffer();
            const base64String = btoa(String.fromCharCode(...new Uint8Array(arrayBuffer)));
            const encryptedBlob = new Blob([base64String], { type: file.type });
            return new File([encryptedBlob], file.name, { type: file.type });
        });
    }
    /**
     * Metodo per registrare il log degli upload EPP.
     */
    logEPPUploadStatus(success, errorData) {
        if (success) {
            console.log("✔ Upload EPP completato con successo.");
        }
        else {
            console.error("❌ Upload EPP fallito:", errorData);
        }
    }
}
//# sourceMappingURL=EPPUploadHandler.js.map