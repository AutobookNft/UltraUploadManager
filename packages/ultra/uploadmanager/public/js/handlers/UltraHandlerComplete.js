var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
export class BaseUploadHandler {
    constructor() {
        const tokenElement = document.querySelector('meta[name="csrf-token"]');
        if (!tokenElement)
            throw new Error("CSRF token non trovato nel DOM");
        this.csrfToken = tokenElement.getAttribute("content") || "";
    }
    /**
     * Metodo generico di upload, da usare o sovrascrivere negli handler specifici.
     * @param endpoint - L'URL dell'endpoint su cui effettuare l'upload.
     * @param formData - Il FormData contenente il file e altri metadati.
     * @returns Un oggetto contenente l'esito dell'upload, con `error`, `response` e `success`.
     */
    performUpload(endpoint, formData) {
        return __awaiter(this, void 0, void 0, function* () {
            let errorData = null; // Tipizziamo come UploadError | null
            let success = true;
            console.log('dentro performUpload. Endpoint:', endpoint);
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
                        errorData = yield response.json(); // Il server ha risposto con JSON
                    }
                    else {
                        const rawErrorData = yield response.text(); // Il server ha risposto con HTML/testo
                        errorData = {
                            message: "Il server ha restituito una risposta non valida.",
                            details: rawErrorData,
                            state: "unknown",
                            errorCode: "unexpected_response",
                            blocking: "blocking",
                        };
                    }
                    success = false;
                }
                return { error: errorData, response, success }; // Usiamo 'error' nel return
            }
            catch (error) {
                // Gestiamo errori di rete o fetch
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
     * Metodo astratto che deve essere implementato dagli handler specifici.
     * @param file - Il file da caricare.
     * @param endpoint - L'URL specifico dell'endpoint di upload.
     */
    handleUpload(file, endpoint) {
        return __awaiter(this, void 0, void 0, function* () {
            const formData = new FormData();
            formData.append('file', file);
            return this.performUpload(endpoint, formData); // Usa l'endpoint passato
        });
    }
}
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
export class UtilityUploadHandler extends BaseUploadHandler {
    handleUpload(file, endpoint) {
        return __awaiter(this, void 0, void 0, function* () {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("_token", this.csrfToken);
            formData.append("uploadType", "utility");
            return yield this.performUpload("/upload/utility", formData);
        });
    }
}
//# sourceMappingURL=UltraHandlerComplete.js.map