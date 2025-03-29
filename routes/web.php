<?php

use App\Http\Controllers\TestTranslationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/test-translations', [TestTranslationController::class, 'test']);
// Route::group(['namespace' => 'Ultra\\UploadManager\\Controllers'], function () {
//     Route::get('/upload', [UploadController::class, 'index']);
// });
