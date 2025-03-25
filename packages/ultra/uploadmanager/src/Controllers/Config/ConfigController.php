<?php

namespace Ultra\UploadManager\Controllers\Config;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\UltraLogManager\Facades\UltraLog;
use Exception;

class ConfigController extends Controller
{

    /**
     * The logging channel name
     *
     * @var string
     */
    protected $channel = 'upload';

    public function getGlobalConfig(Request $request)
    {
        try {
            // Utilizziamo la lingua dell'applicazione invece di gestirla separatamente
            $lang = app()->getLocale();

            // Log::channel('upload')->info('Global Config called with lang: ' . $lang);

            // Log::channel('upload')->info('Global Config called with js: ' . json_encode(__('uploadmanager::js'), JSON_PRETTY_PRINT));
            Log::channel('upload')->info('Testing translation direct access'.
                json_encode([
                    'direct_js' => trans('uploadmanager::uploadmanager.js'),
                    'direct_file_upload' => trans('uploadmanager::uploadmanager'),
                    'direct_using_namespace' => trans('uploadmanager::uploadmanager.js.starting_upload'),
                    'raw_app_locale' => app()->getLocale(),
                    'fallback_locale' => config('app.fallback_locale')
                ]));

            $defaultHostingService = getDefaultHostingService() ?? 'default';
            Log::channel('upload')->info('Default Hosting Service: '. $defaultHostingService);

            $config = [

                'currentLang' => $lang,
                'availableLangs' => ['it', 'en', 'fr', 'pt', 'es', 'de'],
                'translations' => [
                    // Otteniamo le traduzioni dal pacchetto
                    'js' => trans('uploadmanager::uploadmanager.js'),

                    'labels' => [
                        'file_upload' => trans('uploadmanager::uploadmanager.file_upload'),
                        'max_file_size_reminder' => trans('uploadmanager::uploadmanager.max_file_size_reminder'),
                        'upload_your_files' => trans('uploadmanager::uploadmanager.upload_your_files'),
                        'save_the_files' => trans('uploadmanager::uploadmanager.save_the_files'),
                        'cancel' => trans('uploadmanager::uploadmanager.cancel'),
                        'return_to_collection' => trans('uploadmanager::uploadmanager.return_to_collection'),
                        'virus_scan_disabled' => trans('uploadmanager::uploadmanager.virus_scan_disabled'),
                        'virus_scan_enabled' => trans('uploadmanager::uploadmanager.virus_scan_enabled'),
                    ],
                ],

                'envMode' => app()->environment(),
                'defaultHostingService' => $defaultHostingService,
                'imagesPath' => config('app.images_path'),
                'sendEmail' => config('error_constants.SEND_EMAIL'),
                'devTeamEmailAddress' => config('app.devteam_email'),
                'URLRedirectToCollection' => config('app.redirect_to_collection'),
                'errorDelTempLocalFileCode' => config('error_constants.ERROR_DELETING_LOCAL_TEMP_FILE'),
                'errorDelTempExtFileCode' => config('error_constants.ERROR_DELETING_EXT_TEMP_FILE'),
                'enableToCreateDirectory' => config('error_constants.UNABLE_TO_CREATE_DIRECTORY'),
                'enableToChangePermissions' => config('error_constants.UNABLE_TO_CHANGE_PERMISSIONS'),
                'settingAttempts' => config('app.setting_attempt'),
                'temporaryFolder' => config('app.bucket_temp_file_folder'),
                'allowedExtensions' => config('AllowedFileType.collection.allowed_extensions'),
                'allowedMimeTypes' => config('AllowedFileType.collection.allowed_mime_types'),
                'maxSize' => config('AllowedFileType.collection.max_size'),
                'validTypes' => config('upload-manager.collection.valid_types'),

                'uploadTypePaths' => config('upload-manager.upload_types.paths', [
                    '/uploading/egi' => 'egi',
                    '/uploading/epp' => 'epp',
                    '/uploading/utility' => 'utility',
                ]),
                'defaultUploadType' => config('upload-manager.upload_types.default', 'default'),

            ];

            Log::channel('upload')->info('Testing ENV MODE: '. $config['envMode']);

            $config['uploadProcessingError'] = trans('uploadmanager::uploadmanager.js.upload_processing_error');
            $config['invalidServerResponse'] = trans('uploadmanager::uploadmanager.js.invalid_server_response');
            $config['unexpectedUploadError'] = trans('uploadmanager::uploadmanager.js.unexpected_upload_error');
            $config['criticalUploadError'] = trans('uploadmanager::uploadmanager.js.critical_upload_error');
            $config['fileNotFoundForScan'] = trans('uploadmanager::uploadmanager.js.file_not_found_for_scan');
            $config['scanError'] = trans('uploadmanager::uploadmanager.js.scan_error');
            $config['noFileSpecified'] = trans('uploadmanager::uploadmanager.js.no_file_specified');

            $config['mintYourMasterpiece'] = trans('uploadmanager::uploadmanager.mint_your_masterpiece');
            $config['preparingToMint'] = trans('uploadmanager::uploadmanager.preparing_to_mint');
            $config['cancelConfirmation'] = trans('uploadmanager::uploadmanager.js.confirm_cancel');
            $config['uploadWaiting'] = trans('uploadmanager::uploadmanager.js.upload_waiting');
            $config['serverError'] = trans('uploadmanager::uploadmanager.js.server_error');
            $config['saveError'] = trans('uploadmanager::uploadmanager.js.save_error');
            $config['configError'] = trans('uploadmanager::uploadmanager.js.config_error');

            // Validation
            $config['allowedExtensionsMessage'] = trans("uploadmanager::uploadmanager.js.allowed_extensions_message");
            $config['allowedMimeTypesMessage'] = trans("uploadmanager::uploadmanager.js.allowed_mime_types_message");
            $config['maxSizeMessage'] = trans("uploadmanager::uploadmanager.js.max_size_message");

            $config['invalidFilesTitle'] = trans('uploadmanager::uploadmanager.js.invalidFilesTitle');
            $config['invalidFilesMessage'] = trans('uploadmanager::uploadmanager.js.invalidFilesMessage');
            $config['checkFilesGuide'] = trans('uploadmanager::uploadmanager.js.checkFilesGuide');
            $config['okButton'] = trans('uploadmanager::uploadmanager.js.okButton');
            $config['fileTypeImage'] = trans('uploadmanager::uploadmanager.js.file_type_image');
            $config['fileTypeDocument'] = trans('uploadmanager::uploadmanager.js.file_type_document');
            $config['fileTypeAudio'] = trans('uploadmanager::uploadmanager.js.file_type_audio');
            $config['fileTypeVideo'] = trans('uploadmanager::uploadmanager.js.file_type_video');
            $config['fileTypeArchive'] = trans('uploadmanager::uploadmanager.js.file_type_archive');
            $config['fileType3dModel'] = trans('uploadmanager::uploadmanager.js.file_type_3d_model');

            // Error messages for new file types
            $config['errorUnsupportedType'] = trans('uploadmanager::uploadmanager.js.error_unsupported_type');
            $config['errorArchiveTooLarge'] = trans('uploadmanager::uploadmanager.js.error_archive_too_large');
            $config['error3dModelTooLarge'] = trans('uploadmanager::uploadmanager.js.error_3d_model_too_large');
            $config['errorSecurityBlocked'] = trans('uploadmanager::uploadmanager.js.error_security_blocked');

            // Map JS translations to window objects
            $config['startingUpload'] = trans('uploadmanager::uploadmanager.js.starting_upload');
            $config['loading'] = trans('uploadmanager::uploadmanager.js.loading');
            $config['uploadFiniscedText'] = trans('uploadmanager::uploadmanager.js.upload_finished');
            $config['uploadAndScanText'] = trans('uploadmanager::uploadmanager.js.upload_and_scan');
            $config['virusScanAdvise'] = trans('uploadmanager::uploadmanager.js.virus_scan_advice');
            $config['enableVirusScanning'] = trans('uploadmanager::uploadmanager.js.enable_virus_scanning');
            $config['disableVirusScanning'] = trans('uploadmanager::uploadmanager.js.disable_virus_scanning');
            $config['btnDel'] = trans('uploadmanager::uploadmanager.js.delete_button');
            $config['of'] = trans('uploadmanager::uploadmanager.js.of');
            $config['deleteFileError'] = trans('uploadmanager::uploadmanager.js.delete_file_error');
            $config['someError'] = trans('uploadmanager::uploadmanager.js.some_error');
            $config['completeFailure'] = trans('uploadmanager::uploadmanager.js.complete_failure');

            // Emoji translations
            $config['emogyHappy'] = trans('uploadmanager::uploadmanager.js.emoji_happy');
            $config['emogySad'] = trans('uploadmanager::uploadmanager.js.emoji_sad');
            $config['emogyAngry'] = trans('uploadmanager::uploadmanager.js.emoji_angry');

            // File handling messages
            $config['fileSavedSuccessfullyTemplate'] = trans('uploadmanager::uploadmanager.file_saved_successfully');
            $config['fileScannedSuccessfully'] = trans('uploadmanager::uploadmanager.file_scanned_successfully');
            $config['noFileUploaded'] = trans('uploadmanager::uploadmanager.no_file_uploaded');
            $config['fileDeletedSuccessfully'] = trans('uploadmanager::uploadmanager.file_deleted_successfully');

            // Status messages
            $config['imCheckingFileValidity'] = trans('uploadmanager::uploadmanager.im_checking_the_validity_of_the_file');
            $config['imRecordingInformation'] = trans('uploadmanager::uploadmanager.im_recording_the_information_in_the_database');
            $config['allFilesSaved'] = trans('uploadmanager::uploadmanager.all_files_are_saved');
            $config['uploadFailed'] = trans('uploadmanager::uploadmanager.upload_failed');
            $config['someErrors'] = trans('uploadmanager::uploadmanager.some_errors');

            // Virus scan messages
            $config['antivirusScanInProgress'] = trans('uploadmanager::uploadmanager.antivirus_scan_in_progress');
            $config['scanSkippedButUploadContinues'] = trans('uploadmanager::uploadmanager.scan_skipped_but_upload_continues');
            $config['scanningStopped'] = trans('uploadmanager::uploadmanager.scanning_stopped');
            $config['scanningSuccess'] = trans('uploadmanager::uploadmanager.scanning_success');
            $config['oneOrMoreFilesInfected'] = trans('uploadmanager::uploadmanager.one_or_more_files_were_found_infected');
            $config['allFilesScannedNoInfectedFiles'] = trans('uploadmanager::uploadmanager.all_files_were_scanned_no_infected_files');
            $config['fileDetectedAsInfected'] = trans('uploadmanager::uploadmanager.the_uploaded_file_was_detected_as_infected');
            $config['possibleScanningIssues'] = trans('uploadmanager::uploadmanager.possible_scanning_issues');
            $config['unableToCompleteScanContinuing'] = trans('uploadmanager::uploadmanager.unable_to_complete_scan_continuing');

            // Process states
            $config['startingSaving'] = trans('uploadmanager::uploadmanager.js.starting_saving');
            $config['startingScan'] = trans('uploadmanager::uploadmanager.js.starting_scan');
            $config['scanningComplete'] = trans('uploadmanager::uploadmanager.js.scanning_complete');

            // Error handling
            $config['errorDuringUpload'] = trans('uploadmanager::uploadmanager.js.error_during_upload');
            $config['errorDeleteTempLocal'] = trans('uploadmanager::uploadmanager.js.error_delete_temp_local');
            $config['errorDeleteTempExt'] = trans('uploadmanager::uploadmanager.js.error_delete_temp_ext');
            $config['errorDuringUploadRequest'] = trans('uploadmanager::uploadmanager.js.error_during_upload_request');
            $config['unknownError'] = trans('uploadmanager::uploadmanager.js.unknownError');
            $config['unspecifiedError'] = trans('uploadmanager::uploadmanager.js.unspecifiedError');



            return response()->json($config);

        } catch (\Exception $e) {
            Log::channel('upload')->error('Error in getGlobalConfig: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }


    /**
     * Returns the current upload limits considering both server and application settings.
     *
     * This method compares the server's PHP.ini settings (post_max_size, upload_max_filesize, max_file_uploads)
     * with the application's configured limits (max_total_size, max_file_size, max_files) and returns the most
     * restrictive values. It also logs a warning and notifies the dev team if the server limits are more restrictive.
     *
     * @return \Illuminate\Http\JsonResponse Response with effective upload limits
     */
    public function getUploadLimits()
    {
        // Limite server (php.ini)
        $serverPostMaxSize = $this->parseSize(ini_get('post_max_size'));
        $serverUploadMaxFilesize = $this->parseSize(ini_get('upload_max_filesize'));
        $serverMaxFileUploads = (int)ini_get('max_file_uploads');

        // Limiti applicazione (config)
        $appMaxTotalSize = $this->parseSize(config('upload-manager.max_total_size', ini_get('post_max_size')));
        $appMaxFileSize = $this->parseSize(config('upload-manager.max_file_size', ini_get('upload_max_filesize')));
        $appMaxFiles = (int)config('upload-manager.max_files', ini_get('max_file_uploads'));
        $sizeMargin = (float)config('upload-manager.size_margin', 1.1); // Aggiunto

        // Usa il limite più restrittivo tra server e applicazione
        $effectiveTotalSize = min($serverPostMaxSize, $appMaxTotalSize);
        $effectiveFileSize = min($serverUploadMaxFilesize, $appMaxFileSize);
        $effectiveMaxFiles = min($serverMaxFileUploads, $appMaxFiles);

        // Genera warning se i limiti del server sono più restrittivi dell'applicazione
        if ($serverPostMaxSize < $appMaxTotalSize ||
            $serverUploadMaxFilesize < $appMaxFileSize ||
            $serverMaxFileUploads < $appMaxFiles) {

            UltraLog::warning(
                'ServerLimitsMoreRestrictive',
                trans('uploadmanager::uploadmanager.dev.server_limits_restrictive'),
                [
                    'server_post_max_size' => ini_get('post_max_size'),
                    'app_max_total_size' => config('upload-manager.max_total_size'),
                    'server_upload_max_filesize' => ini_get('upload_max_filesize'),
                    'app_max_file_size' => config('upload-manager.max_file_size'),
                    'server_max_file_uploads' => $serverMaxFileUploads,
                    'app_max_files' => $appMaxFiles
                ],
                $this->channel
            );

            UltraError::handle('SERVER_LIMITS_RESTRICTIVE', [
                'server_post_max_size' => ini_get('post_max_size'),
                'app_max_total_size' => config('upload-manager.max_total_size'),
                'server_upload_max_filesize' => ini_get('upload_max_filesize'),
                'app_max_file_size' => config('upload-manager.max_file_size'),
                'server_max_file_uploads' => $serverMaxFileUploads,
                'app_max_files' => $appMaxFiles
            ], new Exception(trans('uploadmanager::uploadmanager.dev.server_limits_restrictive')));
        }

        return response()->json([
            // Limiti effettivi (i più restrittivi)
            'max_total_size' => $effectiveTotalSize,
            'max_file_size' => $effectiveFileSize,
            'max_files' => $effectiveMaxFiles,

            // Valori formattati per la visualizzazione
            'max_total_size_formatted' => $this->formatSize($effectiveTotalSize),
            'max_file_size_formatted' => $this->formatSize($effectiveFileSize),

            // Flag per indicare da dove provengono i limiti
            'total_size_limited_by' => ($serverPostMaxSize <= $appMaxTotalSize) ? 'server' : 'app',
            'file_size_limited_by' => ($serverUploadMaxFilesize <= $appMaxFileSize) ? 'server' : 'app',
            'max_files_limited_by' => ($serverMaxFileUploads <= $appMaxFiles) ? 'server' : 'app',

            // Margine di sicurezza
            'size_margin' => $sizeMargin
        ]);
    }

    /**
     * Converts size string (like "8M") to bytes.
     *
     * @param string $size Size string to parse (e.g., "8M", "2G")
     * @return int Size in bytes
     */
    private function parseSize($size)
    {
        $unit = preg_replace('/[^a-zA-Z]/', '', $size);
        $size = preg_replace('/[^0-9.]/', '', $size);

        if ($unit) {
            return round($size * pow(1024, stripos('KMGTPEZY', $unit[0])));
        }

        return round($size);
    }

    /**
     * Formats bytes into human-readable size.
     *
     * @param int $bytes Size in bytes
     * @return string Formatted size (e.g., "8 MB")
     */
    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

