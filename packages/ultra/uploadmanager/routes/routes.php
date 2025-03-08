<?php

use App\Http\Controllers\ErrorCodeController;
use Illuminate\Support\Facades\Route;
use Ultra\UploadManager\Controllers\ErrorEmailController;
use Ultra\UploadManager\Controllers\ErrorReportingController;
use Ultra\UploadManager\Controllers\FileController;
use Ultra\UploadManager\Controllers\ItemsEdit;
use Ultra\UploadManager\Controllers\NonBlockingErrorController;
use Ultra\UploadManager\Controllers\UploadingFiles;



Route::get('/', function () {
    return view('welcome');
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

Route::get('/get-error-constant/{code}', [\Ultra\UploadManager\Controllers\ErrorCodeController::class, 'getErrorConstant']);

Route::get('/get-non-blocking-error-constant/{code}', [NonBlockingErrorController::class, 'getNonBlockingErrorConstant']);


Route::middleware(['throttle:50,1'])
    ->group(function () {
        Route::redirect('/', '/marketplace');
        Route::post('/upload-temp', [UploadingFiles::class, 'saveTemporaryFile']);
        Route::post('/uploading-files', [UploadingFiles::class, 'upload']);
        Route::post('/scanvirus', [UploadingFiles::class, 'scanVirus']);
        Route::post('/scan-virus', [UploadingFiles::class, 'startVirusScan']);
        Route::post('/get-presigned-url', [UploadingFiles::class, 'getPresignedUrl']);
        Route::post('/set-file-ACL', [UploadingFiles::class, 'setFileACL']);
        Route::post('/delete-temporary-file-local', [UploadingFiles::class, 'deleteTemporaryFileLocal']);
        Route::post('/delete-temporary-file-DO', [UploadingFiles::class, 'deleteTemporaryFileDO']);
        Route::post('/delete-temporary-folder', [UploadingFiles::class, 'deleteTemporaryFolder']);
        Route::post('/notify-upload-complete', [UploadingFiles::class, 'notifyUploadComplete']);
        Route::post('/finalize-upload', [UploadingFiles::class, 'finalizeUpload']);
        Route::post('/save-file', [FileController::class, 'store']);
        Route::post('/save-image-id', [ItemsEdit::class, 'bind'])->name('save-image-id');
        Route::post('/unpair-cover', [ItemsEdit::class, 'unpair'])->name('unpair-cover');
        Route::post('/send-error-email', [ErrorEmailController::class, 'send']);
        Route::post('/report-js-error', [ErrorReportingController::class, 'reportJsError']);

        Route::get('/uploading', [UploadingFiles::class, 'show'])->name('uploading');
    });
