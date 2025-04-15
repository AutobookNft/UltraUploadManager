// resources/ts/global.d.ts

// declare let files: FileList;

let droppedFiles: FileList | null = null;

interface Window {

    // Environment and configuration
    droppedFiles: FileList | null
    imagesPath: string;
    assetBaseUrl: string;
    errorDelTempLocalFileCode: string;
    titleInvalidFileNameMessage: string;
    maxSizeMessage: string;
    allowedExtensions: string[];
    allowedExtensionsMessage: string;
    allowedMimeTypes: string[];
    allowedMimeTypesMessage: string;
    allowedMimeTypesListMessage: string;
    allowedExtensionsListMessage: string;
    uploadFiniscedText: string;
    maxSize: number;
    invalidFileNameMessage: string;
    titleExtensionNotAllowedMessage: string;
    titleFileTypeNotAllowedMessage: string;
    titleFileSizeExceedsMessage: string;
    envMode: string;
    virusScanAdvise: string;
    enableVirusScanning: string;
    disableVirusScanning: string;
    btnDel: string;
    deleteFileError: string;
    emogyHappy: string;
    emogySad: string;
    emogyAngry: string;
    scanInterval: number;
    someInfectedFiles: string;
    someError: string;
    completeFailure: string;
    success: string;
    startingUpload: string; // Aggiunto per errore TS2339
    translations: any;
    criticalErrors: any;
    nonBlockingErrors: any;
    errorCodes: any;
    defaultHostingService: string;
    uploadAndScanText: string;
    loading: string; // Aggiunto per errore TS2339
    redirectToCollection: string;
    currentView: string;
    temporaryFolder: string;
    csrfToken: string; // Required, used for CSRF protection in fetch
    validTypes: string[];
    uploadTypePaths: Record<string, string>;
    defaultUploadType: string;

    // Traduzioni esistenti
    currentLang: string;
    availableLangs: string[];
    sendEmail: string;
    devTeamEmailAddress: string;
    URLRedirectToCollection: string;
    errorDelTempLocalFileCode: string;
    errorDelTempExtFileCode: string;
    enableToCreateDirectory: string;
    enableToChangePermissions: string;
    settingAttempts: string;
    allowedExtensions: string[];
    allowedMimeTypes: string[];
    maxSize: number;

    // Traduzioni UI
    mintYourMasterpiece: string;
    preparingToMint: string;
    cancelConfirmation: string;
    uploadStatusWaiting: string;
    uploadWaiting: string;
    serverError: string;
    saveError: string;
    configError: string;
    invalidFilesTitle: string;
    okButton: string;
    invalidFilesMessage: string;
    checkFilesGuide: string;

    // Traduzioni JS
    startingUpload: string; // Duplicato, ma mantenuto per compatibilità
    loading: string; // Duplicato, ma mantenuto per compatibilità
    uploadFiniscedText: string; // Duplicato, ma mantenuto per compatibilità
    uploadAndScanText: string; // Duplicato, ma mantenuto per compatibilità
    virusScanAdvise: string;
    enableVirusScanning: string;
    disableVirusScanning: string;
    btnDel: string;
    of: string;
    deleteFileError: string;
    someError: string;
    completeFailure: string;

    // Emoji translations
    emogyHappy: string;
    emogySad: string;
    emogyAngry: string;

    // File handling messages
    fileSavedSuccessfullyTemplate: string;
    fileScannedSuccessfully: string;
    noFileUploaded: string;
    fileDeletedSuccessfully: string;

    // Status messages
    imCheckingFileValidity: string;
    imRecordingInformation: string;
    allFilesSaved: string;
    uploadFailed: string;
    someErrors: string;

    // Virus scan messages
    antivirusScanInProgress: string;
    scanSkippedButUploadContinues: string;
    scanningStopped: string;
    oneOrMoreFilesInfected: string;
    allFilesScannedNoInfectedFiles: string;
    fileDetectedAsInfected: string;
    possibleScanningIssues: string;
    unableToCompleteScanContinuing: string;

    // Process states
    startingSaving: string;
    startingScan: string;
    scanningComplete: string;

    // Error handling
    errorDuringUpload: string;
    errorDeleteTempLocal: string;
    errorDeleteTempExt: string;
    errorDuringUploadRequest: string;
    unknownError: string;
    errorDuringScan: string;
    errorDuringSave: string;
    errorDuringConfig: string;
    errorDuringDelete: string;
    errorDuringVirusScan: string;
    errorDuringFileCheck: string;

    // Nuove chiavi da aggiungere
    uploadProcessingError: string;
    invalidServerResponse: string;
    unexpectedUploadError: string;
    criticalUploadError: string;
    fileNotFoundForScan: string;
    scanError: string;
    noFileSpecified: string;

    droppedFiles: FileList;
    translations: {
        upload: {
            max_files: string;
            max_file_size: string;
            max_total_size: string;
            max_files_error: string;
            max_file_size_error: string;
            max_total_size_error: string;
        };
    };

    uploadLimits: {
        max_files: number;
        max_file_size: number;
        max_total_size: number;
        max_file_size_formatted: string;
        max_total_size_formatted: string;
        total_size_limited_by: string;
        file_size_limited_by: string;
        max_files_limited_by: string;
        size_margin: number;
    };

    dropZone: HTMLElement;

    // Event handlers and utilities
    onerror: OnErrorEventHandler;
    customOnError: (devMessage: string, codeError?: number, stack?: string | null) => Promise<boolean>;
    extractErrorDetails: (errorStack: string | undefined, codeError: number) => any;
    checkErrorStatus: (endpoint: string, jsonKey: string, codeError: number) => Promise<boolean | null>;

    // Laravel Echo integration
    Echo: {
        connector: {
            pusher: any; // Placeholder, sostituisci con @types/pusher-js se installato
        };
        channel: (name: string) => {
            listen: (event: string, callback: (data: any) => void) => void;
        };
        socketId: () => string;
    };

    // Aggiunto per supportare scanvirus come oggetto
    scanvirus: { checked: boolean };
}

// Other type declarations
declare module '*.png' {
    const value: string;
    export default value;
}

declare module '@ultra-images/*' {
    const value: string;
    export default value;
}

type ScanFileResponse = {
    response: Response;
    data: any;
};

type UploadEvent = {
    state: string;
    message: string;
    user_id?: number;
    progress?: number;
    [key: string]: any;
};

interface UploadError {
    message: string;
    userMessage?: string;
    details?: string;
    state?: string;
    errorCode?: string;
    blocking?: "blocking" | "not";
}

interface FileUploadResult {
    error: UploadError | false;
    response: Response | false;
    success: boolean;
}

interface ValidationResult {
    isValid: boolean;
    message?: string;
    title?: string;
}

interface ErrorWithCode extends Error {
    code?: number;
}

// Dichiarazioni per le funzioni esterne utilizzate in onerror
declare function sendErrorToServer(errorDetails: any): Promise<void>;
declare function logNonBlockingError(errorDetails: any): void;

declare module 'sweetalert2';
