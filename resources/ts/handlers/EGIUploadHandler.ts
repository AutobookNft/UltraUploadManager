import { BaseUploadHandler } from "./BaseUploadHandler";
import { csrfToken } from '../index';

export class EGIUploadHandler extends BaseUploadHandler {
    private maxAttempts: number = 3;

    constructor() {
        super();
    }

    async handleUpload(file: File, endpoint: string): Promise<{ error: UploadError | null; response: Response | boolean; success: boolean }> {
        const formData = new FormData();
        formData.append("file", file);
        formData.append("_token", csrfToken);
        formData.append("uploadType", "egi");

        let attempt = 0;
        let error: UploadError | null = null; // Tipizziamo error come UploadError | null
        let response: Response | boolean = false;
        let success = false;

        while (attempt < this.maxAttempts && !success) {
            attempt++;
            ({ error, response, success } = await this.performUpload("/upload/egi", formData));

            if (success) {
                console.log(`Tentativo ${attempt} riuscito: ${file.name}`);
                return { error, response, success };
            } else {
                console.warn(`Tentativo ${attempt} fallito: ${error?.message || "Errore sconosciuto"}`);
            }

            if (!success && attempt < this.maxAttempts) {
                console.log(`Riprovo il tentativo ${attempt + 1}...`);
            }
        }

        return { error, response, success };
    }
}
