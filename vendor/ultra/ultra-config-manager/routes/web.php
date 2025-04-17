<?php

/**
 * 📜 Oracode Route Definition: UltraConfigManager Web Routes
 *
 * @package         Routes
 * @version         1.1.0 // Versione incrementata per Oracode e Route Model Binding
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 * @see             Ultra\UltraConfigManager\Http\Controllers\UltraConfigController
 * @see             Ultra\UltraConfigManager\Http\Middleware\CheckConfigManagerRole
 * @see             Ultra\UltraConfigManager\Providers\UConfigServiceProvider (where middleware is aliased and routes loaded)
 */

use Illuminate\Support\Facades\Route; // Kept for idiomatic Laravel route definition
use Ultra\UltraConfigManager\Http\Controllers\UltraConfigController;
use Ultra\UltraConfigManager\Models\UltraConfigModel; // Needed if using explicit binding, good practice anyway

/*
|--------------------------------------------------------------------------
| UltraConfigManager Web Routes
|--------------------------------------------------------------------------
|
| 🎯 Purpose: Defines all web routes necessary for the UltraConfigManager UI.
|    Handles viewing, creating, editing, updating, deleting configurations,
|    and viewing audit trails. Routes are grouped by required permissions.
|
| 🧱 Structure: Routes grouped under 'uconfig' prefix and 'uconfig.' name prefix.
|    Middleware `uconfig.check_role` applied per group based on required permission.
|    Uses Route Model Binding for routes referencing a specific configuration entry ({uconfig}).
|
| 🧩 Context: This file is typically published to the host application's `routes/` directory
|    (e.g., `routes/uconfig.php`) and loaded by the `UConfigServiceProvider`. Assumes
|    execution within the 'web' middleware group (applied by the Service Provider).
|
| 🛠️ Usage: Defines accessible URLs like `/uconfig`, `/uconfig/create`, `/uconfig/{uconfig}/edit`, etc.
|
| 🚦 Signals: Maps URLs/HTTP verbs to `UltraConfigController` methods. Applies authorization middleware.
|
| 🛡️ Privacy: Routes themselves don't handle PII, but they lead to controller actions that might.
|    Authorization is handled by the `CheckConfigManagerRole` middleware.
|
| 🤝 Dependencies: `Route` Facade, `UltraConfigController`, `CheckConfigManagerRole` middleware alias.
|    Implicitly depends on Laravel's routing system. Uses Route Model Binding for `UltraConfigModel`.
|
| 🧪 Testing: Test these routes using Laravel Feature Tests to simulate HTTP requests and
|    assert correct responses, authorization handling, and controller interaction.
|
| 💡 Logic: Standard RESTful resource routing conventions adapted for specific permissions.
|    Route Model Binding simplifies controller actions by automatically fetching the model.
|
*/

Route::prefix('uconfig')->name('uconfig.')->group(function () {

    // --- Routes requiring 'view-config' permission ---
    Route::middleware('uconfig.check_role:view-config')->group(function () {
        Route::get('/', [UltraConfigController::class, 'index'])->name('index');
        // Route::get('create', [UltraConfigController::class, 'create'])->name('create'); // Moved to create-config
        // Edit form requires viewing a specific config
        Route::get('/{uconfig}/edit', [UltraConfigController::class, 'edit'])->name('edit');
        // Audit trail requires viewing a specific config
        Route::get('/{uconfig}/audit', [UltraConfigController::class, 'audit'])->name('audit');
         // Show route (if needed, maybe redirect to edit?) - Requires binding
         // Route::get('/{uconfig}', [UltraConfigController::class, 'show'])->name('show');
    });

    // --- Routes requiring 'create-config' permission ---
    // Note: Assumes create-config implies view-config, otherwise add view middleware too.
    // Let's assume middleware handles hierarchy or separate view is needed.
    // Adding view middleware for safety, user needs to see the list to get to create.
    Route::middleware(['uconfig.check_role:view-config', 'uconfig.check_role:create-config'])->group(function () {
         Route::get('/create', [UltraConfigController::class, 'create'])->name('create'); // Form display
         Route::post('/', [UltraConfigController::class, 'store'])->name('store'); // Form submission
    });


    // --- Routes requiring 'update-config' permission ---
    // Assumes update-config implies view-config to see the edit form first.
    Route::middleware(['uconfig.check_role:view-config', 'uconfig.check_role:update-config'])->group(function () {
        // Route model binding for {uconfig}
        Route::put('/{uconfig}', [UltraConfigController::class, 'update'])->name('update');
    });

    // --- Routes requiring 'delete-config' permission ---
    // Assumes delete-config implies view-config to see the item to delete.
    Route::middleware(['uconfig.check_role:view-config', 'uconfig.check_role:delete-config'])->group(function () {
        // Route model binding for {uconfig}
        Route::delete('/{uconfig}', [UltraConfigController::class, 'destroy'])->name('destroy');
    });

    // Optional: Explicit Route Model Binding definition if needed (usually implicit is fine)
    // Route::model('uconfig', UltraConfigModel::class);

});