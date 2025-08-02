<?php

use Illuminate\Support\Facades\Route;
use Ultra\UploadManager\Controllers\ErrorCodeController;
use Ultra\UploadManager\Controllers\ErrorEmailController;
use Ultra\UploadManager\Controllers\ErrorReportingController;
use Ultra\UploadManager\Controllers\FileController;
use Ultra\UploadManager\Handlers\BaseUploadHandler;
// use Ultra\UploadManager\Controllers\ItemsEdit;
use Ultra\UploadManager\Controllers\NonBlockingErrorController;
use Ultra\UploadManager\Controllers\SaveTemporaryFiles;
use Ultra\UploadManager\Controllers\ScanVirusController;
use Ultra\UploadManager\Controllers\SystemTempFileController;
use Ultra\UploadManager\Controllers\TestTransaltionController;
use Ultra\UploadManager\Controllers\UploadingFiles;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

Route::get('/test-event', function() {
    event(new \Ultra\UploadManager\Events\FileProcessingUpload(
        'Test message',
        'virusScan',
        null
    ));
    return 'Evento inviato!';
});

// Route::group(['namespace' => 'Ultra\\UploadManager\\Controllers'], function () {
//     Route::get('/upload', [UploadController::class, 'index']);
// });

Route::get('/error_codes', function () {
    return response()->json(config('error_constants'));
});

Route::get('/translations', function () {
    return response()->json([
        'errors' => __('errors'),
        'label' => __('label'),
        // Aggiungi qui altre sezioni di traduzioni se necessario
    ]);
});

// Rotta per phpinfo
Route::get('/phpinfo', function () {
    phpinfo();
});

Route::get('/get-error-constant/{code}', [ErrorCodeController::class, 'getErrorConstant']);

Route::get('/get-non-blocking-error-constant/{code}', [NonBlockingErrorController::class, 'getNonBlockingErrorConstant']);


Route::middleware(['throttle:50,1'])
    ->group(function () {
        
        Route::post('/upload-temp', [SaveTemporaryFiles::class, 'saveTemporaryFile']);

        Route::post('/uploading-files', [UploadingFiles::class, 'upload']);

        Route::post('/scanvirus', [UploadingFiles::class, 'scanVirus']);

        // Route::post('/scan-virus', [UploadingFiles::class, 'startVirusScan']);

        Route::post('/get-presigned-url', [UploadingFiles::class, 'getPresignedUrl']);
        Route::post('/set-file-ACL', [UploadingFiles::class, 'setFileACL']);
        Route::post('/delete-temporary-file-local', [UploadingFiles::class, 'deleteTemporaryFileLocal']);
        Route::post('/delete-temporary-file-DO', [UploadingFiles::class, 'deleteTemporaryFileDO']);
        Route::post('/delete-temporary-folder', [UploadingFiles::class, 'deleteTemporaryFolder']);
        Route::post('/notify-upload-complete', [UploadingFiles::class, 'notifyUploadComplete']);
        Route::post('/finalize-upload', [UploadingFiles::class, 'finalizeUpload']);

        // Route::post('/save-file', [FileController::class, 'store']);
        // Route::post('/save-image-id', [ItemsEdit::class, 'bind'])->name('save-image-id');
        // Route::post('/unpair-cover', [ItemsEdit::class, 'unpair'])->name('unpair-cover');

        Route::post('/send-error-email', [ErrorEmailController::class, 'send']);

        Route::post('/report-js-error', [ErrorReportingController::class, 'reportJsError']);

        Route::get('/uploading', [UploadingFiles::class, 'show'])->name('uploading');

        Route::post('/uploading/default', [BaseUploadHandler::class, 'handler'])->name('uploading.hendler');

        Route::get('/api/system/upload-limits', [Ultra\UploadManager\Controllers\Config\ConfigController::class, 'getUploadLimits'])->name('global.config.limits');

        Route::get('/config/global-config', [Ultra\UploadManager\Controllers\Config\ConfigController::class, 'getGlobalConfig'])->name('global.config');

        Route::post('/upload-system-temp', [SystemTempFileController::class, 'saveToSystemTemp'])
            ->name('upload.system.temp');

        Route::post('/delete-system-temp', [SystemTempFileController::class, 'deleteSystemTempFile'])
            ->name('delete.system.temp');

        Route::post('/scan-virus', [ScanVirusController::class, 'startVirusScan'])
            ->name('scan.virus');


    });


    Route::get('/trans-test', [TestTransaltionController::class, 'testTranslations']);