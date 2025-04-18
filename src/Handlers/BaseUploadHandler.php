<?php

/**
 * Base Handler for Core File Upload Logic.
 *
 * Provides foundational functionality for processing file uploads, including
 * initial validation, logging, interaction with testing conditions, file moving,
 * metadata extraction, and basic persistence simulation (via JSON file in this example).
 * It integrates with the Ultra Ecosystem for logging (ULM), error handling (UEM),
 * and configuration (UCM).
 *
 * @package     Ultra\UploadManager\Handlers
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.1.3 // Refined error handling in handler method for validation exceptions.
 * @since       1.0.0
 *
 * @see \Ultra\UltraLogManager\UltraLogManager For logging via ULM.
 * @see \Ultra\ErrorManager\Interfaces\ErrorManagerInterface For error handling via UEM.
 * @see \Ultra\UltraConfigManager\UltraConfigManager For configuration via UCM.
 * @see \Ultra\ErrorManager\Services\TestingConditionsManager For test condition checks.
 * @see \Illuminate\Support\Facades\Storage For storage operations.
 * @see \Ultra\UploadManager\Traits\HasValidation Provides validation logic.
 * @see \Ultra\UploadManager\Traits\HasUtilitys Provides utility logic.
 */

namespace Ultra\UploadManager\Handlers;

// Laravel & PHP Dependencies
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage; // Use Storage Facade for abstraction
use Psr\Log\LoggerInterface; // PSR-3 Interface
use Throwable;
use Exception; // Standard Exception for validation rules for now
use LogicException; // For dependency checks in trait

// Ultra Ecosystem Dependencies
use Ultra\UltraLogManager\UltraLogManager; // Specific ULM implementation
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface; // UEM Interface
use Ultra\ErrorManager\Services\TestingConditionsManager; // Testing Conditions Service
use Ultra\UltraConfigManager\UltraConfigManager; // UCM Implementation

// Local Traits
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;
// TODO: Consider creating a custom ValidationRuleFailedException

class BaseUploadHandler
{
    use HasValidation, HasUtilitys;

    // --- Injected Dependencies (declared as readonly for safety) ---
    protected readonly UltraLogManager $logger;
    protected readonly UltraConfigManager $configManager;
    protected readonly ErrorManagerInterface $errorManager;
    protected readonly TestingConditionsManager $testingConditions;

    /**
     * The default log channel for this handler.
     * @var string
     */
    protected string $logChannel = 'upload';

    /**
     * Constructor with Dependency Injection.
     *
     * @param UltraLogManager $logger PSR-3 Logger (ULM).
     * @param ErrorManagerInterface $errorManager UEM instance.
     * @param TestingConditionsManager $testingConditions Testing conditions manager.
     * @param UltraConfigManager $configManager UCM instance.
     */
    public function __construct(
        UltraLogManager $logger,
        ErrorManagerInterface $errorManager,
        TestingConditionsManager $testingConditions,
        UltraConfigManager $configManager
    ) {
        $this->logger = $logger;
        $this->errorManager = $errorManager;
        $this->testingConditions = $testingConditions;
        $this->configManager = $configManager; // Correctly assigned

        $this->logger->debug('[BaseUploadHandler] Initialized with all dependencies (including UCM).');
    }

    /**
     * Handle the core file upload process with refined error handling.
     *
     * Processes an uploaded file: checks POST limits, validates the file (distinguishing
     * between validation rule failures, dependency errors, and internal validation errors),
     * moves it to storage, extracts metadata, simulates persistence, and returns a JSON response.
     * Uses ULM for logging and UEM for error handling with specific codes.
     *
     * --- Refined Core Logic ---
     * 1. Log start and check POST size limits (delegating error to UEM).
     * 2. Validate file existence/integrity. Handle via UEM if invalid.
     * 3. Log file processing start.
     * 4. Check/handle simulated 'FILE_NOT_FOUND' error.
     * 5. Capture metadata.
     * 6. Check/handle simulated 'GENERIC_SERVER_ERROR'.
     * 7. **Try** validating the file using `$this->validateFile()`:
     *     a. **Catch `LogicException`**: If the trait check fails (dependency missing) -> Handle with `UUM_DEPENDENCY_MISSING`.
     *     b. **Catch `Exception`**: If a validation rule fails (current behavior) -> Handle with `INVALID_FILE_VALIDATION`.
     *     c. **Catch `Throwable`**: If any other unexpected error occurs *during* validation -> Handle with `UUM_VALIDATION_INTERNAL_ERROR`.
     * 8. If validation passes, get destination path from config. Handle missing config via UEM (`UUM_CONFIG_PATH_MISSING`).
     * 9. Move the uploaded file. Handle exceptions via UEM (`UUM_FILE_MOVE_FAILED`).
     * 10. Prepare file metadata (including hash).
     * 11. Simulate persistence (JSON file). Handle exceptions via UEM (`ERROR_SAVING_FILE_METADATA`).
     * 12. Log completion and return success JsonResponse.
     * 13. Catch any unexpected Throwable *outside* the validation block and handle via UEM (`UNEXPECTED_ERROR`).
     * --- End Refined Core Logic ---
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse Success or error response generated by UEM.
     *
     * @throws Throwable Can re-throw critical exceptions if UEM decides to.
     * @sideEffect See original method description.
     * @configReads See original method description.
     * @uemErrorCodes INVALID_FILE, MAX_FILE_SIZE, UPLOAD_NO_FILE_SENT, FILE_NOT_FOUND,
     *                GENERIC_SERVER_ERROR, UUM_DEPENDENCY_MISSING, INVALID_FILE_VALIDATION,
     *                UUM_VALIDATION_INTERNAL_ERROR, UUM_CONFIG_PATH_MISSING, UUM_FILE_MOVE_FAILED,
     *                ERROR_SAVING_FILE_METADATA, UNEXPECTED_ERROR, POST_SIZE_LIMIT_EXCEEDED
     */
    public function handler(Request $request): JsonResponse
    {
        $file = null;
        $originalName = 'unknown';

        try {
            $this->logger->info('[BaseUploadHandler] Starting file upload process.', [ /* context */ ]);

            // --- Check POST Size Limits ---
            if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
                $postMaxSize = ini_get('post_max_size');
                $this->logger->error('[BaseUploadHandler] POST data exceeds server limits.', [ /* context */ ]);
                // Define POST_SIZE_LIMIT_EXCEEDED in UEM config
                return $this->errorManager->handle('POST_SIZE_LIMIT_EXCEEDED', ['post_max_size' => $postMaxSize], new Exception('POST data exceeds server limits'));
            }

            // --- Validate File Existence/Integrity ---
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                $uploadError = $request->hasFile('file') ? $request->file('file')->getError() : 'No file uploaded';
                $this->logger->warning('[BaseUploadHandler] Invalid or missing file received.', [ /* context */ ]);
                $context = ['fileName' => $originalName, 'upload_error_code' => $uploadError];
                $uemCode = match($uploadError) {
                    UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'MAX_FILE_SIZE',
                    UPLOAD_ERR_NO_FILE => 'UPLOAD_NO_FILE_SENT', // Define in UEM config
                    default => 'INVALID_FILE',
                };
                return $this->errorManager->handle($uemCode, $context);
            }

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $index = $request->input('index');
            $this->logger->info('[BaseUploadHandler] Processing file.', [ /* context */ ]);

            // --- Simulate Errors (Testing) ---
            if ($this->testingConditions->isTesting('FILE_NOT_FOUND') && $index === '0') {
                $this->logger->info('[BaseUploadHandler] Simulating FILE_NOT_FOUND error.', [ /* context */ ]);
                return $this->errorManager->handle('FILE_NOT_FOUND', ['fileName' => $originalName]);
            }
            if ($this->testingConditions->isTesting('GENERIC_SERVER_ERROR') && $index === '0') {
                $this->logger->info('[BaseUploadHandler] Simulating GENERIC_SERVER_ERROR.', [ /* context */ ]);
                return $this->errorManager->handle('GENERIC_SERVER_ERROR', ['fileName' => $originalName]);
            }

            // --- Capture Metadata ---
            $fileSize = $file->getSize();
            $originalExtension = $file->getClientOriginalExtension();
            $this->logger->info('[BaseUploadHandler] File metadata captured.', [ /* context */ ]);

            // --- File Validation Block (with refined error catching) ---
            try {
                $this->logger->debug('[Handler] Attempting file validation...');
                $this->validateFile($file, $index); // Call trait method
                $this->logger->info('[Handler] File validation successful.');

            } catch (LogicException $le) { // CATCH 1: Dependency check failed in trait
                $this->logger->critical('[Handler] Validation failed due to missing dependency inside trait.', ['error' => $le->getMessage()]);
                // Handle with specific UEM code (Needs definition in config/error-manager.php)
                return $this->errorManager->handle(
                    'UUM_DEPENDENCY_MISSING', // Critical: Code setup issue
                    ['dependency' => 'Likely UltraConfigManager', 'handler' => static::class, 'traitMethod' => 'validateFile'],
                    $le
                );
            } catch (Exception $ve) { // CATCH 2: Validation rule failed (Current behavior of trait)
                                      // TODO: Change to catch ValidationRuleFailedException if created
                $this->logger->warning('[Handler] File did not pass validation rules.', [
                    'file_name' => $originalName,
                    'validation_error' => $ve->getMessage()
                ]);
                // Handle with the CORRECT UEM code for this scenario
                return $this->errorManager->handle(
                    'INVALID_FILE_VALIDATION',
                    ['fileName' => $originalName, 'validation_error' => $ve->getMessage()],
                    $ve
                );
            } catch (Throwable $e_val) { // CATCH 3: Other unexpected error *during* validation
                 $this->logger->error('[Handler] Unexpected internal error during validation attempt.', [
                    'file_name' => $originalName,
                    'exception_class' => get_class($e_val),
                    'exception_message' => $e_val->getMessage()
                ]);
                // Handle with a specific UEM code (Needs definition in config/error-manager.php)
                 return $this->errorManager->handle(
                     'UUM_VALIDATION_INTERNAL_ERROR', // Error: Internal validation logic failed unexpectedly
                     ['fileName' => $originalName],
                     $e_val
                 );
            }
            // --- End File Validation Block ---

            // --- File Move ---
            $path = config('upload-manager.default_path');
             if (!$path) {
                 $this->logger->error('[BaseUploadHandler] Default storage path is not configured.', ['config_key' => 'upload-manager.default_path']);
                 // Define UUM_CONFIG_PATH_MISSING in UEM config
                 return $this->errorManager->handle('UUM_CONFIG_PATH_MISSING');
             }
             $this->logger->info('[BaseUploadHandler] Moving file to storage.', [ /* context */ ]);
            try {
                $file->move($path, $originalName);
                $storedRealPath = $path . DIRECTORY_SEPARATOR . $originalName;
                 $this->logger->info('[BaseUploadHandler] File moved successfully.', ['stored_path' => $storedRealPath]);
            } catch (Throwable $e_move) {
                $this->logger->error('[BaseUploadHandler] Failed to move uploaded file.', [ /* context */ ]);
                 // Define UUM_FILE_MOVE_FAILED in UEM config
                return $this->errorManager->handle('UUM_FILE_MOVE_FAILED', ['fileName' => $originalName, 'path' => $path], $e_move);
            }

            // --- Prepare Metadata ---
            $fileData = [
                'name'      => $originalName,
                'hash'      => file_exists($storedRealPath) ? md5_file($storedRealPath) : null,
                'size'      => $fileSize,
                'extension' => $originalExtension,
                'stored_at' => now()->toIso8601String(),
            ];
            if ($fileData['hash'] === null) {
                 $this->logger->warning('[BaseUploadHandler] Could not calculate md5 hash.', ['path' => $storedRealPath]);
            }
             $this->logger->info('[BaseUploadHandler] File data prepared.', [ /* context */ ]);

            // --- Simulate Persistence (JSON) ---
            $relativeJsonPath = 'uploads.json';
            $disk = 'local';
            $this->logger->debug('[BaseUploadHandler] Attempting to update JSON metadata storage.', [ /* context */ ]);
            try {
                $uploads = [];
                if (Storage::disk($disk)->exists($relativeJsonPath)) {
                    $json = Storage::disk($disk)->get($relativeJsonPath);
                    if ($json === false) throw new Exception("Failed to read JSON file: {$relativeJsonPath}");
                    $uploads = json_decode($json, true) ?? [];
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->logger->warning('[BaseUploadHandler] Invalid JSON detected, resetting.', [ /* context */ ]);
                        $uploads = [];
                    }
                }
                $uploads[] = $fileData;
                if (!Storage::disk($disk)->put($relativeJsonPath, json_encode($uploads, JSON_PRETTY_PRINT))) {
                    throw new Exception("Failed to write JSON file: {$relativeJsonPath}");
                }
                 $this->logger->info('[BaseUploadHandler] File metadata saved to JSON storage.', ['path' => $relativeJsonPath]);
            } catch (Throwable $e_json) {
                $this->logger->error('[BaseUploadHandler] Failed to save file data to JSON storage.', [ /* context */ ]);
                // Keep original UEM code or make UUM specific? Using original for now.
                return $this->errorManager->handle('ERROR_SAVING_FILE_METADATA', ['fileName' => $originalName, 'jsonPath' => $relativeJsonPath], $e_json);
            }

            // --- Prepare Success Response ---
             $successMessage = 'Upload completed successfully.'; // Fallback message
             // Check if Translator is bound before using trans()
             if ($this->app->bound(\Illuminate\Contracts\Translation\Translator::class)) {
                  $successMessage = trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $originalName]);
             }
            $responsePayload = ['message' => $successMessage, 'fileData' => $fileData];

            $this->logger->info('[BaseUploadHandler] File upload process completed successfully.', [ /* context */ ]);

            // --- Return Success ---
            return response()->json($responsePayload, 200);

        } catch (Throwable $e) { // Final Catch for any unexpected errors outside specific blocks
            $this->logger->critical('[BaseUploadHandler] Unexpected fatal error during file upload handler execution.', [
                'file_name' => $originalName,
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]);
            // Handle with UEM's generic code
            return $this->errorManager->handle('UNEXPECTED_ERROR', ['fileName' => $originalName, 'context' => 'BaseUploadHandler::handler main catch block'], $e);
        }
    } // Fine metodo handler

} // Fine Classe