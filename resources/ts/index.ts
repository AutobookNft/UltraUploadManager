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

export {
    initializeApp,
    files,
    handleFileSelect,
    handleDrop,
    cancelUpload,
    
} from './core/file_upload_manager';

export {
    csrfToken,
    progressBar,
    progressText,
    scanProgressText,
    statusMessage,
    statusDiv,
    scanvirus,
    virusAdvise,
    scanvirusLabel,
    getFiles,
    uploadBtn,
    uploadFilebtn,
    returnToCollectionBtn,
    cancelUploadBtn,
    circleLoader,
    circleContainer,
    emojiElements,
    collection,
    dropZone,
    setupDomEventListeners,
} from './utils/domElements';

export { handleVirusScan } from './utils/scanFile';

export { showEmoji } from './utils/showEmoji';

export {
    disableButtons,
    resetButtons,
    handleImage,
    enableButtons,
    updateStatusDiv,
    updateStatusMessage,
    highlightInfectedImages,
    removeFile,
    removeImg,
    removeEmoji,
    updateUploadLimitsDisplay,
    validateFilesAgainstLimits,
    formatSize,
    redirectToCollection,
    redirectToURL
} from './utils/uploadUtils';

