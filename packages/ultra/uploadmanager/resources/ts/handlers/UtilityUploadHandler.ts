import { BaseUploadHandler } from "./BaseUploadHandler";

export class UtilityUploadHandler extends BaseUploadHandler {
    async handleUpload(file: File, endpoint: string): Promise<{ error: UploadError | null; response: Response | boolean; success: boolean }> {
        const formData = new FormData();
        formData.append("file", file);
        formData.append("_token", this.csrfToken);
        formData.append("uploadType", "utility");

        return await this.performUpload("/upload/utility", formData);
    }
}
