// resources/ts/global.d.ts

// declare let files: FileList;

interface Window {

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

    onerror: OnErrorEventHandler;
    customOnError: (devMessage: string, codeError?: number, stack?: string | null) => Promise<boolean>;
    extractErrorDetails: (errorStack: string | undefined, codeError: number) => any;
    checkErrorStatus: (endpoint: string, jsonKey: string, codeError: number) => Promise<boolean | null>;




}

interface ErrorWithCode extends Error {
    code?: number;
  }

// Dichiarazioni per le funzioni esterne utilizzate in onerror
declare function sendErrorToServer(errorDetails: any): Promise<void>;
declare function logNonBlockingError(errorDetails: any): void;

declare module 'sweetalert2';
