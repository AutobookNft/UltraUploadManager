<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});



// Route::group(['namespace' => 'Ultra\\UploadManager\\Controllers'], function () {
//     Route::get('/upload', [UploadController::class, 'index']);
// });
