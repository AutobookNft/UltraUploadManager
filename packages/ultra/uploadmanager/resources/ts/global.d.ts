// resources/ts/global.d.ts

// declare let files: FileList;

interface Window {
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
    deleteFileError : string;
    emogyHappy: string;
    emogySad: string;
    emogyAngry: string;
    scanInterval: number;
    someInfectedFiles: string;
    someError: string;
    completeFailure: string;
    success: string;
    startingUpload: string;
    translations: any;
    criticalErrors: any;
    nonBlockingErrors: any;
    errorCodes: any;
    defaultHostingService: string;
    uploadAndScanText: string;
    loading: string;
    redirectToCollection: string;
    currentView: string
    temporaryFolder: string;

    onerror: OnErrorEventHandler;
    customOnError: (devMessage: string, codeError?: number, stack?: string | null) => Promise<boolean>;
    extractErrorDetails: (errorStack: string | undefined, codeError: number) => any;
    checkErrorStatus: (endpoint: string, jsonKey: string, codeError: number) => Promise<boolean | null>;

}

interface Window {
    startingUpload: string;
    loading: string;
    uploadFiniscedText: string;
    uploadAndScanText: string;
    scanvirus: { checked: boolean };
    envMode: string;
}


// types.d.ts
declare module '*.png' {
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
  user_id: number;
  progress: number;
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
    error: any; // Puoi creare un tipo più specifico se conosci la struttura degli errori
    response: Response | false; // Il tipo `Response` per fetch API o `false` in caso di errore
    success: boolean;
}

interface ErrorWithCode extends Error {
    code?: number;
  }

// Dichiarazioni per le funzioni esterne utilizzate in onerror
declare function sendErrorToServer(errorDetails: any): Promise<void>;
declare function logNonBlockingError(errorDetails: any): void;

declare module 'sweetalert2';
