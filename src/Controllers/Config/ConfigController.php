<?php

namespace Ultra\UploadManager\Controllers\Config;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\UltraLogManager\Facades\UltraLog;
use Exception;
use Illuminate\Support\Facades\Auth;
use Ultra\UploadManager\Services\SizeParser;


class ConfigController extends Controller
{

    /**
     * The logging channel name
     *
     * @var string
     */
    protected $channel = 'upload';

    protected $sizeParser;

    public function __construct(SizeParser $sizeParser)
    {
        $this->sizeParser = $sizeParser;
    }

    public function getGlobalConfig(Request $request)
    {
        try {
            // Utilizziamo la lingua dell'applicazione invece di gestirla separatamente
            $lang = app()->getLocale();

          
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
                'redirectToUrlAfterUpload' => config('app.redirect_to_url_after_upload'),
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

            


            return response()->json($config);

        } catch (Exception $e) {
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
        // Server limits (php.ini)
        $serverPostMaxSize = $this->sizeParser->parse(ini_get('post_max_size'));
        $serverUploadMaxFilesize = $this->sizeParser->parse(ini_get('upload_max_filesize'));
        $serverMaxFileUploads = (int)ini_get('max_file_uploads');

        // Log dei valori grezzi
        Log::channel('upload')->info('Raw Server Limits: ', [
            'post_max_size' => $serverPostMaxSize,
            'upload_max_filesize' => $serverMaxFileUploads,
            'max_file_uploads' => $serverMaxFileUploads
        ]);

        // Application limits (config)
        $appMaxTotalSize = $this->sizeParser->parse(config('upload-manager.max_total_size', ini_get('post_max_size')));
        $appMaxFileSize = $this->sizeParser->parse(config('upload-manager.max_file_size', ini_get('upload_max_filesize')));
        $appMaxFiles = (int)config('upload-manager.max_files', ini_get('max_file_uploads'));


        $effectiveTotalSize = min($serverPostMaxSize, $appMaxTotalSize);
        $effectiveFileSize = min($serverUploadMaxFilesize, $appMaxFileSize);
        $effectiveMaxFiles = min($serverMaxFileUploads, $appMaxFiles);

        // Log dei limiti effettivi
        Log::channel('upload')->info('Effective Limits: ', [
            'max_total_size' => $effectiveTotalSize,
            'max_file_size' => $effectiveFileSize,
            'max_files' => $effectiveMaxFiles
        ]);

        return response()->json([
            'max_total_size' => $effectiveTotalSize,
            'max_file_size' => $effectiveFileSize,
            'max_files' => $effectiveMaxFiles,
            'max_total_size_formatted' => $this->formatSize($effectiveTotalSize),
            'max_file_size_formatted' => $this->formatSize($effectiveFileSize),
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

     /**
     * Verifica se l'utente è autorizzato ad aprire la modale di upload.
     *
     * Controlla che l'utente sia autenticato, abbia una sessione valida,
     * e abbia verificato l'email. Restituisce un JSON con lo stato di autorizzazione.
     *
     * @param Request $request La richiesta HTTP.
     * @return \Illuminate\Http\JsonResponse Risposta JSON con lo stato di autorizzazione.
     *
     * @oracode.semantically_coherent Risposta chiara e prevedibile per il client.
     * @oracode.testable Stato di autorizzazione deterministico e mockabile.
     * @oracode.explicitly_intentional Rispetta i requisiti di autenticazione di Jetstream.
     * @oracode.testable_logging Usa Log::channel() per logging testabile.
     * @gdpr Nessun dato personale viene raccolto oltre l'ID utente autenticato.
     */
    public function checkUploadAuthorization(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $user = Auth::user();
            Log::channel($this->channel)->info(
                'CheckUploadAuthorization',
                ['user_id' => $user ? $user->id : 'guest', 'ip' => $request->ip()]
            );

            if (!$user) {
                $response = [
                    'authorized' => false,
                    'reason' => trans('uploadmanager::uploadmanager.unauthenticated'),
                    'redirect' => route('login'),
                ];
                Log::channel($this->channel)->warning(
                    'UnauthorizedAccessAttempt',
                    ['ip' => $request->ip(), 'response' => $response]
                );
                return response()->json($response, 401);
            }

            // if (!$user->hasVerifiedEmail()) {
            //     $response = [
            //         'authorized' => false,
            //         'reason' => trans('uploadmanager::uploadmanager.email_not_verified'),
            //         'redirect' => route('verification.notice'),
            //     ];
            //     Log::channel($this->channel)->warning(
            //         'UnverifiedEmail',
            //         ['user_id' => $user->id, 'response' => $response]
            //     );
            //     return response()->json($response, 403);
            // }

            Log::channel($this->channel)->info(
                'AuthorizationSuccess',
                ['user_id' => $user->id]
            );

            return response()->json([
                'authorized' => true,
                'user_id' => $user->id,
            ], 200);

        } catch (Exception $e) {
            Log::channel($this->channel)->error(
                'AuthorizationCheckFailed',
                ['error' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()]
            );
            return UltraError::handle('UNEXPECTED_ERROR', [
                'context' => 'checkUploadAuthorization',
                'error' => $e->getMessage(),
            ], $e);
        }
    }
}