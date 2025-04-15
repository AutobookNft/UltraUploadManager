// // packages/ultra/uploadmanager/resources/ts/index.ts
// // Core (fondamentali per l'uso del pacchetto)
export { BaseUploadHandler } from './handlers/BaseUploadHandler';
export { HubFileController } from './core/hubFileController';
export { handleUpload } from './core/uploading';
export { saveLocalTempFile } from './utils/saveLocalTempFile';
export { deleteTemporaryFileExt, deleteTemporaryFileLocal } from './utils/deleteTemporaryFiles';
export { validateFile, validateFileName } from './utils/validation';
export { prepareFilesForUploadUI } from './utils/prepareFilesForUploadUI';
export { setupRealTimeUploadListener } from './utils/listener';
// Aggiungi queste righe in index.ts
export { saveToSystemTempDir, deleteSystemTempFile } from './utils/saveToSystemTemp';
export { csrfToken, progressBar, progressText, scanProgressText, statusMessage, statusDiv, scanvirus, virusAdvise, scanvirusLabel, getFiles, uploadBtn, uploadFilebtn, returnToCollectionBtn, cancelUploadBtn, circleLoader, circleContainer } from './utils/domElements';
export { handleVirusScan } from './utils/scanFile';
export { showEmoji } from './utils/showEmoji';
export { disableButtons, resetButtons, removeEmojy, handleImage, enableButtons, updateStatusDiv, updateStatusMessage, highlightInfectedImages, removeFile, removeImg } from './utils/uploadUtils';
//# sourceMappingURL=index.js.map