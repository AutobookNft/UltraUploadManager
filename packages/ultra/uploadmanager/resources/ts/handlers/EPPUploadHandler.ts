import { BaseUploadHandler } from "./BaseUploadHandler";

export class EPPUploadHandler extends BaseUploadHandler {
    async handleUpload(file: File, endpoint: string): Promise<{ error: UploadError | null; response: Response | boolean; success: boolean }> {
        const formData = new FormData();

        // 🔹 Passaggio 1: Cifriamo il file prima dell'upload (simulato con una conversione Base64)
        const encryptedFile = await this.encryptFile(file);
        formData.append("file", encryptedFile);
        formData.append("_token", this.csrfToken);
        formData.append("uploadType", "epp");

        // 🔹 Passaggio 2: Eseguiamo l'upload con la logica personalizzata
        return await this.performUpload("/upload/epp", formData);
    }

    /**
     * Sovrascriviamo `performUpload` per personalizzare il comportamento di upload.
     */
    protected async performUpload(endpoint: string, formData: FormData): Promise<{ error: UploadError | null; response: Response | boolean; success: boolean }> {
        let errorData: UploadError | null = null; // Tipizziamo come UploadError | null
        let success: boolean = true;

        try {
            const response: Response = await fetch(endpoint, {
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
                    errorData = await response.json();
                } else {
                    errorData = {
                        message: "Il server ha restituito una risposta non valida.",
                        details: await response.text(),
                        state: "unknown",
                        errorCode: "unexpected_response",
                        blocking: "blocking",
                    };
                }
                success = false;
            } else {
                // 🔹 Verifica il token restituito dall'API per confermare l'upload
                const result = await response.json();
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
        } catch (error) {
            errorData = {
                message: "Errore durante la richiesta di upload",
                details: error instanceof Error ? error.message : String(error),
                state: "network",
                errorCode: "fetch_error",
                blocking: "blocking",
            };
            return { error: errorData, response: false, success: false };
        }
    }

    /**
     * Metodo per cifrare il file (simulazione).
     */
    private async encryptFile(file: File): Promise<File> {
        // Simuliamo una cifratura base64 per questo esempio
        const arrayBuffer = await file.arrayBuffer();
        const base64String = btoa(String.fromCharCode(...new Uint8Array(arrayBuffer)));
        const encryptedBlob = new Blob([base64String], { type: file.type });

        return new File([encryptedBlob], file.name, { type: file.type });
    }

    /**
     * Metodo per registrare il log degli upload EPP.
     */
    private logEPPUploadStatus(success: boolean, errorData: UploadError | null): void {
        if (success) {
            console.log("✔ Upload EPP completato con successo.");
        } else {
            console.error("❌ Upload EPP fallito:", errorData);
        }
    }
}
