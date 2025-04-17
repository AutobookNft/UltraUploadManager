# UltraUploadManager (UUM) - Oracode Compliant Upload Management

[![Latest Version](https://img.shields.io/badge/version-v1.0.0-blue.svg?style=flat-square)](...) <!-- Placeholder -->
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg?style=flat-square)](...) <!-- Placeholder -->
[![Code Coverage](https://img.shields.io/badge/coverage-WIP-yellow.svg?style=flat-square)](...) <!-- Placeholder -->
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg?style=flat-square)](https://opensource.org/licenses/MIT)

**UltraUploadManager (UUM)** is a robust, modular, and Oracode-compliant file upload management system designed for Laravel 11+ applications within the Ultra ecosystem. It provides a comprehensive solution for handling file uploads, including validation, temporary storage, virus scanning, real-time progress updates via broadcasting, and integration with various storage providers (local, S3-compatible like DigitalOcean Spaces).

Built with Dependency Injection, Testability, GDPR awareness, and the [📜 Oracode Doctrine v1.5.0 (Transition)](./Oracode_Doctrine_v1.5.0_Transition.md) at its core.

---

## 🎯 Core Purpose & Philosophy

UUM aims to provide a reliable, secure, and transparent file upload experience for both users and developers. It adheres to Oracode principles:

*   **Explicitly Intentional:** Code and configuration strive for clarity of purpose.
*   **Semantically Coherent:** Naming and structure follow consistent domain language.
*   **Interpretable:** Documentation (PHPDoc standard + Oracode details) aims for long-term understanding.
*   **Interrogable:** Components are designed to be testable and their state understandable.
*   **GDPR Aware:** Handles temporary files and user context with privacy considerations.

---

## ✨ Key Features

*   **Modular Upload Handling:** Uses Factory/Strategy pattern (`HubFileController`, `BaseUploadHandler`, specific handlers like `EGIUploadHandler`) for different upload types/contexts.
*   **Comprehensive Validation:** Validates files based on configured extensions, MIME types, size limits (individual and total), and filename rules. Includes security checks.
*   **Temporary File Management:** Securely stores files temporarily before final processing or scanning. Includes fallback mechanisms (system temp) and automated cleanup (`ultra:clean-temp` command).
*   **Antivirus Scanning (Optional):** Integrates with ClamAV (or other scanners via configuration) to scan files before final storage.
*   **Real-Time Progress:** Uses Laravel Echo (with Pusher) to broadcast events (`FileProcessingUpload`) for frontend progress bars and status updates.
*   **Storage Agnostic:** Leverages Laravel's Filesystem for compatibility with local, S3, DigitalOcean Spaces, etc.
*   **Ultra Ecosystem Integration:** Designed to work seamlessly with:
    *   **ULM (UltraLogManager):** For detailed, semantic logging.
    *   **UTM (UltraTranslationManager):** For all user-facing messages and UI text.
    *   **UCM (UltraConfigManager):** For retrieving package configuration.
    *   **UEM (UltraErrorManager):** For centralized and robust error handling.
*   **TypeScript Frontend:** Includes a rich TypeScript frontend component for handling drag & drop, previews, progress display, validation, and real-time updates.
*   **Testable:** Built with DI for improved testability (Testbench setup included).
*   **Oracode v1.5.0 Documentation:** Adheres to the PHPDoc standard enriched with detailed descriptions.

---

## 🤝 Dependencies

*   **PHP:** ^8.2 | ^8.3
*   **Laravel:** ^11.0
*   **External Libraries:**
    *   `league/flysystem` ^3.0 (Implicit via Laravel Filesystem)
    *   `league/flysystem-aws-s3-v3` ^3.0 (If using S3/DO)
    *   `pusher/pusher-php-server` ^7.2 (For Broadcasting)
    *   `symfony/process` ^7.0 (For external processes like ClamAV)
*   **Ultra Ecosystem:**
    *   `ultra/ultra-log-manager`: dev-main
    *   `ultra/ultra-translation-manager`: dev-main
    *   `ultra/ultra-config-manager`: dev-main
    *   `ultra/ultra-error-manager`: dev-main
*   **Frontend (Peer Dependencies for Application):**
    *   `laravel-echo`
    *   `pusher-js`
    *   `sweetalert2`
    *   `tailwindcss` (or your preferred CSS framework)

---

## 💾 Installation

1.  **Require Package:**
    ```bash
    composer require ultra/ultra-upload-manager:dev-main
    ```
    *(Ensure necessary Ultra dependency repositories are configured in your application's `composer.json` if not using Packagist, and set `minimum-stability` to `dev`)*

2.  **Publish Resources:** Publish the necessary configuration, views, and translations. It's recommended to publish specific tags:
    ```bash
    # Publish main configuration file
    php artisan vendor:publish --tag=uum-config

    # Publish views (optional, only if customization is needed)
    php artisan vendor:publish --tag=uum-views

    # Publish language files (optional, only if customization is needed)
    php artisan vendor:publish --tag=uum-translations

    # IMPORTANT: Also publish resources for DEPENDENCIES if not already done:
    php artisan vendor:publish --tag=uconfig-resources # UCM: config, migrations, etc.
    php artisan vendor:publish --tag=error-manager-config # UEM: config
    php artisan vendor:publish --tag=error-manager-migrations # UEM: migrations
    php artisan vendor:publish --tag=ultra-log-config # ULM: config (optional)
    php artisan vendor:publish --tag=utm-config # UTM: config (optional)
    ```

3.  **Run Migrations:** Execute migrations required by UCM and UEM:
    ```bash
    php artisan migrate
    ```
    *(Note: UUM v1.0 does not add its own migrations yet).*

4.  **Configure UEM Middleware:** **Crucial for error handling.** Modify your application's `bootstrap/app.php` (for Laravel 11+) or `app/Http/Kernel.php` (Laravel <= 10) to register UEM's middleware as per the UEM documentation.
    *   Add `\Ultra\ErrorManager\Http\Middleware\ErrorHandlingMiddleware::class` to global middleware.
    *   Add the `'environment'` alias for `\Ultra\ErrorManager\Http\Middleware\EnvironmentMiddleware::class`.

5.  **Configure Environment (.env):**
    *   **Broadcasting (Pusher):** Ensure your `.env` file has the correct `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`. Set `BROADCAST_DRIVER=pusher`.
    *   **Filesystem:** Configure your desired default filesystem disk (`FILESYSTEM_DISK`) and add credentials for S3/DigitalOcean (`DO_ACCESS_KEY_ID`, `DO_SECRET_ACCESS_KEY`, `DO_BUCKET`, `DO_ENDPOINT`, `DO_DEFAULT_REGION`, etc.) if you plan to use them.
    *   **Queue:** Configure your queue driver (`QUEUE_CONNECTION`) if using asynchronous jobs (like `TempFilesCleaner`).

6.  **Configure Frontend Build (Vite):** Modify your application's `vite.config.js`:
    *   Add UUM's JS/TS entry points to the `input` array:
        ```js
        laravel({
            input: [
                // Your app's entries...
                'resources/css/app.css',
                'resources/js/app.js',
                // Add UUM entries (adjust paths if needed, using vendor/...)
                'vendor/ultra/ultra-upload-manager/resources/js/app.js', // If exists/needed
                'vendor/ultra/ultra-upload-manager/resources/ts/core/file_upload_manager.ts',
                // 'vendor/ultra/ultra-upload-manager/resources/css/app.css', // If needed
            ],
            // ...
        }),
        ```
    *   Ensure necessary `resolve.alias` exist (e.g., `@ultra-images`).
    *   Ensure `server.fs.allow` includes the path to the UUM resources within `vendor` (e.g., `'./vendor/ultra/ultra-upload-manager/resources'`).
    *   Enable `resolve.preserveSymlinks: true` if using path/vcs repositories.
    *   Run `npm install` and `npm run dev` (or `build`).

7.  **Configure Laravel Echo:** Ensure your application's `resources/js/bootstrap.js` initializes Laravel Echo with the correct Pusher credentials from your `.env` file.

8.  **Load Global JS Configuration:** UUM's frontend requires backend configuration (like allowed file types, translations) to be available globally in JavaScript (e.g., on the `window` object). You need to implement a mechanism in your application's main layout/view to pass this data. Example using a Blade directive or view composer:
    ```html
    <!-- In your main layout file (e.g., resources/views/layouts/app.blade.php) -->
    <script>
        window.allowedExtensions = @json(config('AllowedFileType.collection.allowed_extensions', [])); // Example
        window.allowedMimeTypes = @json(config('AllowedFileType.collection.allowed_mime_types', [])); // Example
        window.maxSize = {{ config('AllowedFileType.collection.max_size', 10*1024*1024) }}; // Example
        window.translations = { // Pass necessary JS translations from UTM
            upload: {
                 max_files: "{{ __('uploadmanager::js.max_files_error') }}", // Example using UUM namespace
                 // ... other needed JS translations
            }
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.envMode = "{{ app()->environment() }}";
        // Add any other config/translations needed by uum.js
        document.dispatchEvent(new Event('configLoaded')); // Optional: Event for UUM JS to listen to
    </script>
    @vite(['resources/js/app.js', /* include UUM entry points if not imported by app.js */])
    ```
    *(Refer to UUM's frontend code and `configLoader.blade.php` in the Sandbox for required variables).*

9.  **Clear Caches:**
    ```bash
    php artisan optimize:clear
    ```

---

## ⚙️ Configuration

*   **`config/upload-manager.php`:** (Published from package) Main configuration file.
    *   `upload_path`, `default_path`, `temp_path`, `temp_subdir`: Define storage directories.
    *   `antivirus`: Configure ClamAV binary path and options.
    *   `max_total_size`, `max_file_size`, `max_files`: Application-level limits (may be superseded by server limits).
    *   `allowed_extensions`, `allowed_mime_types`: Default validation rules (can be overridden per handler/context).
*   **`config/filesystems.php`:** (Application config) Define your storage disks (local, s3, do) used by UUM.
*   **`config/AllowedFileType_UUM.php`:** (Published from package) Contains detailed file type definitions (consider merging or referencing application's main AllowedFileType if exists).
*   **`config/error-manager.php`:** (Application config, from UEM) **Important:** Define specific error codes for UUM failures (e.g., `UUM_VIRUS_FOUND`, `UUM_SAVE_FAILED`) and configure how UEM should handle them (logging, notifications, user messages via UTM keys).

---

## 🚀 Basic Usage

*(Note: Examples assume refactored code using Dependency Injection)*

**Backend (Conceptual Controller Example):**

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ultra\UploadManager\Contracts\UploadHandlerFactory; // Example interface
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface;
use Illuminate\Support\Facades\Auth;

class EgiUploadController extends Controller
{
    public function __construct(
        private UploadHandlerFactory $handlerFactory, // Injected factory
        private ErrorManagerInterface $errorManager
    ) {}

    public function store(Request $request)
    {
        $request->validate([
            'egi_file' => ['required', 'file', /* ... other app-level rules */],
            // other metadata fields
        ]);

        $user = Auth::user();
        $file = $request->file('egi_file');
        $metadata = $request->only(['title', 'description', 'collection_id']); // Example metadata

        try {
            // Get the appropriate handler (e.g., for 'egi' type)
            $handler = $this->handlerFactory->make('egi');

            // Process the upload through the handler
            $result = $handler->processUpload($file, $user, $metadata);

            // Check $result for success/failure/data
            if (!$result->isSuccessful()) {
                 // Use UEM to handle specific UUM errors returned by the handler
                 return $this->errorManager->handle($result->getErrorCode(), $result->getContext());
            }

            return response()->json(['message' => 'EGI uploaded successfully!', 'data' => $result->getData()]);

        } catch (\Throwable $e) {
            // Catch unexpected errors and let UEM handle them
            return $this->errorManager->handle('UUM_UNEXPECTED_UPLOAD_ERROR', ['file' => $file->getClientOriginalName()], $e);
        }
    }
}
```

**Frontend (Conceptual Initialization):**

```js
// In your application's main JS/TS file (e.g., resources/js/app.js)
// Ensure Echo and global config (window. ...) are set up first in bootstrap.js/layout

import { initializeApp } from 'vendor/ultra/ultra-upload-manager/resources/ts/core/file_upload_manager'; // Adjust import path based on your setup/aliasing

// Wait for DOM and potentially the global config to be loaded
document.addEventListener('DOMContentLoaded', () => {
    // Check if the upload container exists on the current page
    const uploadContainer = document.getElementById('upload-container'); // Assuming UUM view uses this ID
    if (uploadContainer) {
         // Initialize the UUM frontend logic
         initializeApp();
         console.log('UltraUploadManager Initialized.');
    }
});
```

---

## 📡 Event Broadcasting

UUM broadcasts status updates during the upload and scanning process using Laravel Echo.

* **Driver:** Pusher (configure in .env and config/broadcasting.php)
* **Channel:** upload (Public channel by default in Service Provider)
* **Event Class:** Ultra\UploadManager\Events\FileProcessingUpload
* **Event Name:** .TestUploadEvent12345 (Note: This name seems like a placeholder and should likely be made configurable or more descriptive in the refactored version. Current frontend JS listens for this specific name.)
* **Payload:** Includes message, state, user_id, progress.

Ensure your application's frontend Echo listener is configured correctly in resources/js/bootstrap.js.

---

## 🛡️ Error Handling

UUM relies on UltraErrorManager (UEM) for robust error handling.

* Internal UUM exceptions (e.g., VirusException, validation errors, storage errors) should ideally be caught and mapped to specific UEM error codes within UUM's services/controllers.
* The consuming application (Sandbox/FlorenceEGI) must define these UUM-specific error codes in its config/error-manager.php file, specifying how they should be logged, reported, and what messages (via UTM keys) should be shown to the user.
* Unexpected errors are caught by UEM's global ErrorHandlingMiddleware.

---

## 🧪 Testing

UUM includes its own PHPUnit test suite using Orchestra Testbench.

* Run tests from the package directory: `composer test`
* Coverage reports can be generated (see composer.json scripts).
* Integration with UEM's TestingConditions facade can be used for simulating specific failure scenarios (e.g., antivirus failure).

---

## 🔐 GDPR & Privacy

* **Temporary Files:** UUM stores files temporarily during processing. The `ultra:clean-temp` command helps ensure these are not kept indefinitely. Ensure the temporary storage location (config/upload-manager.php -> temp_path) is appropriately secured.
* **Context Logging:** Be mindful of the user/request context passed to ULM and UEM. Ensure PII is not logged unnecessarily or is properly sanitized if required. UEM DB logging includes basic redaction based on error-manager.database_logging.sensitive_keys.
* **User Association:** Uploads are typically associated with the authenticated user ID for tracking and permissions.

---

## 🚧 Technical Debt / Known Issues (Pre-Refactor v1.0 State)

* The current codebase extracted from the Sandbox requires significant refactoring for proper Dependency Injection, Oracode v1.5.0 documentation, adherence to SOLID principles, and robust error handling via UEM.
* Frontend relies heavily on global window variables set by Blade; a more structured approach (e.g., passing data via component props or dedicated API endpoints) is recommended post-refactoring.
* The broadcast event name `.TestUploadEvent12345` needs review.
* Test coverage needs to be significantly expanded during refactoring.

---

## 🤝 Contributing

(Placeholder - Add contribution guidelines if planning to open source)

---

## 📄 License

UltraUploadManager is open-sourced software licensed under the MIT license.