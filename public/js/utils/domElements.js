// domElements.ts
export const statusMessage = document.getElementById('status-message');
export const statusDiv = document.getElementById('status');
export const scanProgressText = document.getElementById('scan-progress-text');
export const progressBar = document.getElementById('progress-bar');
export const progressText = document.getElementById('progress-text');
export const uploadFilebtn = document.getElementById('file-label');
export const returnToCollectionBtn = document.getElementById('returnToCollection');
export const scanvirusLabel = document.getElementById('scanvirus_label');
export const scanvirus = document.getElementById('scanvirus');
export const virusAdvise = document.getElementById('virus-advise');
export const circleLoader = document.getElementById('circle-loader');
export const circleContainer = document.getElementById('circle-container');
export const uploadBtn = document.getElementById('uploadBtn');
export const cancelUploadBtn = document.getElementById('cancelUpload');
export const emojiElements = document.querySelectorAll('.emoji');
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
if (!csrfMeta) {
    throw new Error("Meta tag with csrf-token not found");
}
export const csrfToken = csrfMeta.getAttribute('content');
export const collection = document.getElementById('collection');
export function getFiles() {
    if (!document.getElementById('files')) {
        return null;
    }
    return document.getElementById('files').files;
}
//# sourceMappingURL=domElements.js.map