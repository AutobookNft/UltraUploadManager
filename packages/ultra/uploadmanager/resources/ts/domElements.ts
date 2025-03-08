// domElements.ts

export const statusMessage = document.getElementById('status-message') as HTMLElement;
export const statusDiv = document.getElementById('status') as HTMLElement;
export const scanProgressText = document.getElementById('scan-progress-text') as HTMLElement;
export const progressBar = document.getElementById('progress-bar') as HTMLElement;
export const progressText = document.getElementById('progress-text') as HTMLElement;
export const uploadFilebtn = document.getElementById('file-label') as HTMLElement;
export const returnToCollectionBtn = document.getElementById('returnToCollection') as HTMLElement;
export const scanvirusLabel = document.getElementById('scanvirus_label') as HTMLElement;
export const scanvirus = document.getElementById('scanvirus') as HTMLInputElement;
export const virusAdvise = document.getElementById('virus-advise') as HTMLElement;
export const circleLoader = document.getElementById('circle-loader') as HTMLElement;
export const circleContainer = document.getElementById('circle-container') as HTMLElement;
export const uploadBtn = document.getElementById('uploadBtn') as HTMLButtonElement;
export const cancelUploadBtn = document.getElementById('cancelUpload') as HTMLButtonElement;
export const emojiElements = document.querySelectorAll('.emoji') as NodeListOf<HTMLElement>;
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (!csrfMeta) {
    throw new Error("Meta tag with csrf-token not found");
}
export const csrfToken = csrfMeta.getAttribute('content') as string;
export const collection = document.getElementById('collection') as HTMLElement;


export function getFiles(): FileList | null {
    if (!document.getElementById('files')) {
        return null
    }
    return (document.getElementById('files') as HTMLInputElement).files;
}
