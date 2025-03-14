<?php

<?php

namespace Ultra\UploadManager\Controllers\Config;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

class ConfigController extends Controller
{

    // Aggiungi questo middleware nel costruttore se non c'è già
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (session('locale')) {
                app()->setLocale(session('locale'));
            }
            return $next($request);
        });
    }
    public function getGlobalConfig(Request $request)
    {
        $lang = $request->query('lang', app()->getLocale());

        // Imposta la lingua per questa richiesta
        app()->setLocale($lang);

        // Salva la lingua nella sessione
        session(['locale' => $lang]);

        $config = [
            'currentLang' => $lang,
            'availableLangs' => ['it', 'en', 'fr', 'pt', 'es', 'de'],
            'translations' => [
                'labels' => __('label'),
                'errors' => __('errors'),
            ],
            'defaultHostingService' => getDefaultHostingService(),
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
            'envMode' => app()->environment(),
        ];

        return response()->json($config);
    }

}


