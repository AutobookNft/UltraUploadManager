<?php

/**
 * Base Handler for Core File Upload Logic.
 *
 * Provides foundational functionality for processing file uploads, including
 * initial validation, logging, interaction with testing conditions, file moving,
 * metadata extraction, and basic persistence simulation (via JSON file in this example).
 * It integrates with the Ultra Ecosystem for logging (ULM), error handling (UEM),
 * and potentially configuration (via injected config or UCM).
 *
 * @package     Ultra\UploadManager\Handlers // Updated Namespace
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.1.0 // Refactored for DI, Oracode v1.5.0, Ultra Integration
 * @since       1.0.0 // As BaseUploadHandler
 *
 * @see \Psr\Log\LoggerInterface For logging via ULM.
 * @see \Ultra\ErrorManager\Interfaces\ErrorManagerInterface For error handling via UEM.
 * @see \Ultra\ErrorManager\Services\TestingConditionsManager For test condition checks.
 * @see \Illuminate\Contracts\Filesystem\Factory For storage operations (used via Storage facade).
 * @see \Ultra\UploadManager\Traits\HasValidation Assumed trait providing validation logic.
 * @see \Ultra\UploadManager\Traits\HasUtilitys Assumed trait providing utility logic.
 */

namespace Ultra\UploadManager\Handlers; // Namespace likely within Handlers now

// Laravel & PHP Dependencies
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory; // For Storage Facade resolution hint
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage; // Use Storage Facade for abstraction
use Psr\Log\LoggerInterface; // ULM Interface
use Throwable; // Catch all throwables
use Exception; // Standard Exception

// Ultra Ecosystem Dependencies
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface; // UEM Interface
use Ultra\ErrorManager\Services\TestingConditionsManager; // Testing Conditions Service

// Local Traits (Assume they exist and are compatible with injected dependencies if needed)
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;

// Note: Removed 'extends Controller'
class BaseUploadHandler
{
    // Use traits - Ensure these traits use $this->logger, $this->errorManager etc. if needed,
    // or refactor them into services.
    use HasValidation, HasUtilitys;

    /**
     * PSR-3 Logger instance (typically UltraLogManager).
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * Ultra Error Manager instance.
     * @var ErrorManagerInterface
     */
    protected ErrorManagerInterface $errorManager;

    /**
     * Testing Conditions Manager instance.
     * @var TestingConditionsManager
     */
    protected TestingConditionsManager $testingConditions;

    // Removed Filesystem injection, using Storage Facade instead for better Laravel integration.

    /**
     * The default log channel for this handler.
     * @var string
     */
    protected string $logChannel = 'upload'; // Or make configurable

    /**
     * Constructor with Dependency Injection.
     *
     * @param LoggerInterface $logger PSR-3 Logger (ULM).
     * @param ErrorManagerInterface $errorManager UEM instance.
     * @param TestingConditionsManager $testingConditions Testing conditions manager.
     */
    public function __construct(
        LoggerInterface $logger,
        ErrorManagerInterface $errorManager,
        TestingConditionsManager $testingConditions
        // Removed Filesystem $filesystem
    ) {
        $this->logger = $logger;
        $this->errorManager = $errorManager;
        $this->testingConditions = $testingConditions;
        $this->logger->debug('[BaseUploadHandler] Initialized.'); // Log initialization
    }

    /**
     * Handle the core file upload process.
     *
     * Processes an uploaded file: checks POST limits, validates the file,
     * moves it to storage, extracts metadata, simulates persistence (JSON file),
     * and returns a JSON response. Uses ULM for logging and UEM for error handling.
     * Checks TestingConditions for simulating errors.
     *
     * --- Core Logic ---
     * 1. Log start and check POST size limits (delegating error to UEM).
     * 2. Validate file existence and integrity (`$request->file()`). Handle via UEM if invalid.
     * 3. Log file processing start, including index.
     * 4. Check and handle simulated 'FILE_NOT_FOUND' error via TestingConditions/UEM.
     * 5. Capture file metadata.
     * 6. Check and handle simulated 'GENERIC_SERVER_ERROR' via TestingConditions/UEM.
     * 7. Validate the file using `$this->validateFile()` (from Trait). Handle exceptions via UEM.
     * 8. Get destination path from config (`upload-manager.default_path`).
     * 9. Move the uploaded file using `$file->move()`. Handle exceptions via UEM.
     * 10. Prepare file metadata array (including md5 hash of stored file).
     * 11. Simulate persistence by reading/writing to a JSON file using Storage facade. Handle exceptions via UEM.
     * 12. Log completion and return success JsonResponse.
     * 13. Catch any unexpected Throwable and handle via UEM.
     * --- End Core Logic ---
     *
     * @param Request $request The incoming HTTP request containing the file and metadata.
     * @return JsonResponse A JSON response indicating success or containing error details (generated by UEM).
     *
     * @throws Throwable Can re-throw critical exceptions if UEM decides to.
     *
     * @sideEffect Moves the uploaded file to the configured storage path.
     * @sideEffect Reads from and writes to `storage/app/uploads.json` (using default 'local' disk).
     * @sideEffect Logs various stages and potential errors via injected LoggerInterface (ULM).
     * @sideEffect Delegates error handling to injected ErrorManagerInterface (UEM).
     *
     * @configReads upload-manager.default_path Defines the storage directory for moved files.
     * @see \Ultra\UploadManager\Traits\HasValidation::validateFile() For file validation logic.
     */
    public function handler(Request $request): JsonResponse
    {
        $file = null; // Initialize file variable
        $originalName = 'unknown'; // Default for error context

        try {
            $this->logger->info('[BaseUploadHandler] Starting file upload process.', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            // Check if POST content exceeded server limits BEFORE trying to access files
            if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
                $postMaxSize = ini_get('post_max_size');
                $this->logger->error('[BaseUploadHandler] POST data exceeds server limits.', [
                    'post_max_size' => $postMaxSize,
                    'ip' => $request->ip()
                ]);
                // Let UEM handle creating the appropriate Response
                return $this->errorManager->handle('POST_SIZE_LIMIT_EXCEEDED', [
                    'post_max_size' => $postMaxSize
                ], new Exception('POST data exceeds server limits')); // Provide context exception
            }

            // 1. Validate file existence and integrity using Laravel's built-in checks
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                $uploadError = $request->hasFile('file') ? $request->file('file')->getError() : 'No file uploaded';
                $this->logger->warning('[BaseUploadHandler] Invalid or missing file received.', [
                    'has_file' => $request->hasFile('file'),
                    'is_valid' => $request->hasFile('file') ? $request->file('file')->isValid() : false,
                    'upload_error_code' => $uploadError
                ]);
                 // Define context for UEM
                $context = [
                    'fileName' => $originalName,
                    'upload_error_code' => $uploadError
                 ];
                 // Determine specific UEM code based on error if possible
                 $uemCode = 'INVALID_FILE'; // Default
                 if ($uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE) {
                     $uemCode = 'MAX_FILE_SIZE'; // More specific
                 } elseif ($uploadError === UPLOAD_ERR_NO_FILE) {
                     $uemCode = 'UPLOAD_NO_FILE_SENT'; // Define this in UEM config
                 }
                return $this->errorManager->handle($uemCode, $context);
            }

            $file = $request->file('file'); // Assign file now that we know it's valid
            $originalName = $file->getClientOriginalName(); // Get name for logging/context

            // Get current file index in the upload sequence
            $index = $request->input('index'); // Use input() for flexibility
            $this->logger->info('[BaseUploadHandler] Processing file.', [
                'index' => $index,
                'file_name' => $originalName
            ]);

            // Simulate FILE_NOT_FOUND error if test condition is active
            if ($this->testingConditions->isTesting('FILE_NOT_FOUND') && $index === '0') { // Use injected service
                $this->logger->info('[BaseUploadHandler] Simulating FILE_NOT_FOUND error.', [
                    'test_condition' => 'FILE_NOT_FOUND',
                    'index' => $index
                ]);
                return $this->errorManager->handle('FILE_NOT_FOUND', ['fileName' => $originalName]);
            }

            // 2. Store file metadata before moving
            $fileSize = $file->getSize();
            $originalExtension = $file->getClientOriginalExtension();

            $this->logger->info('[BaseUploadHandler] File metadata captured.', [
                'name' => $originalName,
                'size' => $fileSize,
                'extension' => $originalExtension
            ]);

            // Simulate GENERIC_SERVER_ERROR if test condition is active
            if ($this->testingConditions->isTesting('GENERIC_SERVER_ERROR') && $index === '0') { // Use injected service
                $this->logger->info('[BaseUploadHandler] Simulating GENERIC_SERVER_ERROR.', [
                    'test_condition' => 'GENERIC_SERVER_ERROR',
                    'index' => $index
                ]);
                return $this->errorManager->handle('GENERIC_SERVER_ERROR', ['fileName' => $originalName]);
            }

            // 3. Validate the file using the Trait method
            // Assumes validateFile throws Exception on failure
            try {
                $this->validateFile($file, $index); // From HasValidation trait
                 $this->logger->info('[BaseUploadHandler] File validation successful.', ['file_name' => $originalName]);
            } catch (Throwable $e) { // Catch Throwable
                $this->logger->warning('[BaseUploadHandler] File validation failed.', [
                    'file_name' => $originalName,
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage()
                ]);
                // Delegate to UEM, passing the original exception
                return $this->errorManager->handle('INVALID_FILE_VALIDATION', [
                    'fileName' => $originalName,
                    'validation_error' => $e->getMessage() // Add specific error
                ], $e);
            }

            // 4. Configure and move the file to storage location
            $path = config('upload-manager.default_path'); // Get path from config
             if (!$path) {
                 $this->logger->error('[BaseUploadHandler] Default storage path is not configured.', ['config_key' => 'upload-manager.default_path']);
                 return $this->errorManager->handle('UUM_CONFIG_PATH_MISSING'); // Define this code
             }
            $this->logger->info('[BaseUploadHandler] Moving file to storage.', [
                'destination_path' => $path,
                'file_name' => $originalName
            ]);

            try {
                // Use move method on UploadedFile instance
                $file->move($path, $originalName);
                $storedRealPath = $path . DIRECTORY_SEPARATOR . $originalName; // Path where the file *should* be
                 $this->logger->info('[BaseUploadHandler] File moved successfully.', ['stored_path' => $storedRealPath]);

            } catch (Throwable $e) { // Catch Throwable
                $this->logger->error('[BaseUploadHandler] Failed to move uploaded file.', [
                    'destination_path' => $path,
                    'file_name' => $originalName,
                    'exception_class' => get_class($e),
                     'exception_message' => $e->getMessage() // Add exception message
                ]);
                 // Define a specific code for file move errors
                return $this->errorManager->handle('UUM_FILE_MOVE_FAILED', [
                    'fileName' => $originalName,
                    'path' => $path
                ], $e);
            }

            // 5. Build file data (including hash AFTER moving)
            $fileData = [
                'name'      => $originalName,
                'hash'      => file_exists($storedRealPath) ? md5_file($storedRealPath) : null, // Calculate hash on stored file
                'size'      => $fileSize,
                'extension' => $originalExtension,
                'stored_at' => now()->toIso8601String(),
            ];
            if ($fileData['hash'] === null) {
                 $this->logger->warning('[BaseUploadHandler] Could not calculate md5 hash. File might not exist after move.', ['path' => $storedRealPath]);
            }

            $this->logger->info('[BaseUploadHandler] File data prepared.', [
                'file_data_keys' => implode(', ', array_keys($fileData)) // Log keys only, not potentially large hash
            ]);

            // 6. Save metadata to JSON storage (Example - Replace with proper persistence)
            $relativeJsonPath = 'uploads.json'; // Relative path within the default disk
            $disk = 'local'; // Disk for JSON storage (should be configurable)
            $this->logger->debug('[BaseUploadHandler] Attempting to update JSON metadata storage.', ['disk' => $disk, 'path' => $relativeJsonPath]);
            try {
                $uploads = [];
                if (Storage::disk($disk)->exists($relativeJsonPath)) {
                    $json = Storage::disk($disk)->get($relativeJsonPath);
                    if ($json === false) { // Handle read error
                         throw new Exception("Failed to read JSON file: {$relativeJsonPath}");
                    }
                    $uploads = json_decode($json, true) ?? []; // Handle JSON decode error
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $this->logger->warning('[BaseUploadHandler] Invalid JSON detected, resetting.', ['path' => $relativeJsonPath, 'json_error' => json_last_error_msg()]);
                        $uploads = []; // Reset if invalid JSON
                    }
                }
                $uploads[] = $fileData; // Append new data
                if (!Storage::disk($disk)->put($relativeJsonPath, json_encode($uploads, JSON_PRETTY_PRINT))) {
                    throw new Exception("Failed to write JSON file: {$relativeJsonPath}");
                }
                $this->logger->info('[BaseUploadHandler] File metadata saved to JSON storage.', ['path' => $relativeJsonPath]);

            } catch (Throwable $e) { // Catch Throwable
                $this->logger->error('[BaseUploadHandler] Failed to save file data to JSON storage.', [
                    'path' => $relativeJsonPath,
                    'exception_class' => get_class($e),
                    'exception_message' => $e->getMessage()
                ]);
                // Delegate to UEM
                return $this->errorManager->handle('ERROR_SAVING_FILE_METADATA', [ // Keep original code? Or make UUM specific?
                    'fileName' => $originalName,
                    'jsonPath' => $relativeJsonPath
                ], $e);
            }

            // 7. Prepare success response
            // Use UTM for user messages if available and configured
             $successMessage = $this->app->bound(TranslatorContract::class)
                 ? trans('uploadmanager::uploadmanager.upload_complete_scan_next') // Example key
                 : 'Upload completed, starting virus scan...'; // Fallback
            $responsePayload = ['message' => $successMessage, 'fileData' => $fileData]; // Include useful data

            $this->logger->info('[BaseUploadHandler] File upload process completed successfully.', [
                'response_keys' => implode(', ', array_keys($responsePayload)),
                'file_name' => $originalName
            ]);

            // 8. Return success response to client
            return response()->json($responsePayload, 200); // Explicit 200 OK

        } catch (Throwable $e) { // Catch any unexpected Throwable
            $this->logger->critical('[BaseUploadHandler] Unexpected fatal error during file upload.', [ // Use critical for unexpected
                'file_name' => $originalName, // Use captured name if available
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                // Avoid logging full trace here, UEM will handle it
            ]);

            // Handle with Ultra Error Manager
            return $this->errorManager->handle('UNEXPECTED_ERROR', [ // UEM's generic code
                'fileName' => $originalName,
                'context' => 'BaseUploadHandler::handler main catch block'
            ], $e); // Pass the original exception
        }
    }
}