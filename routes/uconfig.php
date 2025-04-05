<?php

use Illuminate\Support\Facades\Route;
use Ultra\UltraConfigManager\Http\Controllers\UltraConfigController;

Route::prefix('uconfig')->name('uconfig.')->group(function () {
    // Rotte che richiedono solo la visualizzazione
    Route::middleware('uconfig.check_role:view-config')->group(function () {
        Route::get('/', [UltraConfigController::class, 'index'])->name('index');
        Route::get('create', [UltraConfigController::class, 'create'])->name('create');
        Route::get('{uconfig}', [UltraConfigController::class, 'edit'])->name('edit');
        Route::get('{uconfig}/audit', [UltraConfigController::class, 'audit'])->name('audit');
    });

    // Rotte che richiedono permessi di creazione
    Route::middleware('uconfig.check_role:create-config')->group(function () {
        Route::post('/', [UltraConfigController::class, 'store'])->name('store');
    });

    // Rotte che richiedono permessi di aggiornamento
    Route::middleware('uconfig.check_role:update-config')->group(function () {
        Route::put('{uconfig}', [UltraConfigController::class, 'update'])->name('update');
    });

    // Rotte che richiedono permessi di eliminazione
    Route::middleware('uconfig.check_role:delete-config')->group(function () {
        Route::delete('{uconfig}', [UltraConfigController::class, 'destroy'])->name('destroy');
    });
});
