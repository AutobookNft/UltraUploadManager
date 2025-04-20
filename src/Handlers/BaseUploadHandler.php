<?php

/**
 * Base Handler for Core File Upload Logic (Laravel Standard).
 *
 * Provides foundational functionality using standard Laravel helpers and facades.
 *
 * @package     Ultra\UploadManager\Handlers
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.2.1 // Corrected response key to userMessage, documentation to English.
 * @since       1.0.0
 */

namespace Ultra\UploadManager\Handlers;

// Laravel & PHP Dependencies
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log; // Use Log Facade
use Illuminate\Support\Facades\Storage; // Use Storage Facade for abstraction
use Throwable;
use Exception; // Standard Exception
use LogicException; // For setup/config errors

// Local Traits (Refactored versions)
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;

class BaseUploadHandler
{
    // Use refactored traits using config() and Log::
    use HasValidation, HasUtilitys;

    /**
     * The default log channel for this handler.
     * Inheriting classes can override this.
     * @var string
     */
    protected string $logChannel = 'stack'; // Use Laravel's default stack

    /**
     * 🎯 Constructor: No dependencies needed in this refactored version.
     * Logs initialization.
     */
    public function __construct()
    {
        Log::channel($this->logChannel)->debug('[BaseUploadHandler] Initialized (Laravel Standard).');
    }

    /**
     * Handle the core file upload process using standard Laravel tools.
     *
     * Processes an uploaded file: checks POST limits, validates (using refactored trait),
     * moves it to storage, extracts metadata, simulates persistence (JSON),
     * and returns a JSON response. Uses standard Log facade.
     *
     * --- Core Logic ---
     * (Steps remain the same as previous refactored description)
     * --- End Core Logic ---
     *
     * @param Request $request The incoming HTTP request.
     * @return JsonResponse Success or error response.
     *
     * @throws Throwable Can re-throw exceptions for global handler or caller.
     * @sideEffect Moves uploaded file, writes to JSON file, logs messages.
     * @configReads upload-manager.default_path, logging.channels configuration.
     */
    public function handler(Request $request): JsonResponse
    {
        $file = null;
        $originalName = 'unknown';
        $logContext = ['handler' => static::class];

        try {
            Log::channel($this->logChannel)->info('[BaseUploadHandler] Starting file upload process.', $logContext);

            // --- Check POST Size Limits ---
            if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) === 'post') {
                $postMaxSize = ini_get('post_max_size');
                $errorMsg = "POST data exceeds server limits (max: {$postMaxSize}).";
                Log::channel($this->logChannel)->error('[BaseUploadHandler] ' . $errorMsg, $logContext);
                throw new Exception($errorMsg, 413); // 413 Payload Too Large
            }

            // --- Validate File Existence/Integrity ---
            if (!$request->hasFile('file') || !$request->file('file')->isValid()) {
                $uploadError = $request->hasFile('file') ? $request->file('file')->getError() : UPLOAD_ERR_NO_FILE;
                $errorMsg = 'Invalid or missing file received.';
                 match($uploadError) {
                     UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $errorMsg = 'File exceeds allowed size limits.',
                     UPLOAD_ERR_NO_FILE => $errorMsg = 'No file was uploaded.',
                     default => $errorMsg = 'File upload error: code ' . $uploadError,
                 };
                 Log::channel($this->logChannel)->warning('[BaseUploadHandler] ' . $errorMsg, array_merge($logContext, ['upload_error_code' => $uploadError]));
                 throw new Exception($errorMsg, 400); // 400 Bad Request
            }

            $file = $request->file('file');
            $originalName = $file->getClientOriginalName();
            $logContext['file_name'] = $originalName;
            $index = $request->input('index', 0);
            Log::channel($this->logChannel)->info('[BaseUploadHandler] Processing file.', $logContext);

            // --- Capture Metadata ---
            $fileSize = $file->getSize();
            $originalExtension = $file->getClientOriginalExtension();
            $logContext['size'] = $fileSize;
            $logContext['extension'] = $originalExtension;
            Log::channel($this->logChannel)->info('[BaseUploadHandler] File metadata captured.', $logContext);

            // --- File Validation Block ---
            try {
                Log::channel($this->logChannel)->debug('[Handler] Attempting file validation...', $logContext);
                $this->validateFile($file, $index); // Call refactored trait method
                Log::channel($this->logChannel)->info('[Handler] File validation successful.', $logContext);
            } catch (Exception $ve) {
                Log::channel($this->logChannel)->warning('[Handler] File did not pass validation rules.', array_merge($logContext, ['validation_error' => $ve->getMessage()]));
                throw new Exception("File validation failed: " . $ve->getMessage(), 422, $ve); // 422 Unprocessable Entity
            } catch (Throwable $e_val) {
                 Log::channel($this->logChannel)->error('[Handler] Unexpected internal error during validation attempt.', array_merge($logContext, ['exception_class' => get_class($e_val), 'exception_message' => $e_val->getMessage()]));
                 throw new Exception("Internal error during file validation.", 500, $e_val); // 500 Internal Server Error
            }
            // --- End File Validation Block ---

            // --- File Move ---
            $path = config('upload-manager.default_path');
             if (!$path || !is_string($path)) {
                 Log::channel($this->logChannel)->error('[BaseUploadHandler] Default storage path is not configured or invalid.', array_merge($logContext, ['config_key' => 'upload-manager.default_path', 'retrieved_value' => $path]));
                 throw new LogicException("Default storage path ('upload-manager.default_path') not configured correctly.");
             }
             Log::channel($this->logChannel)->info('[BaseUploadHandler] Moving file to storage.', array_merge($logContext, ['destination_path_config' => $path]));
            try {
                $destinationDirectory = rtrim($path, DIRECTORY_SEPARATOR);
                $file->move($destinationDirectory, $originalName);
                $storedRealPath = $destinationDirectory . DIRECTORY_SEPARATOR . $originalName;
                 Log::channel($this->logChannel)->info('[BaseUploadHandler] File moved successfully.', array_merge($logContext, ['stored_path' => $storedRealPath]));
            } catch (Throwable $e_move) {
                 Log::channel($this->logChannel)->error('[BaseUploadHandler] Failed to move uploaded file.', array_merge($logContext, ['path' => $path, 'error' => $e_move->getMessage()]));
                 throw new Exception("Failed to store uploaded file.", 500, $e_move);
            }

            // --- Prepare Metadata ---
            $fileData = [
                'name'      => $originalName,
                'hash'      => file_exists($storedRealPath) ? md5_file($storedRealPath) : null,
                'size'      => $fileSize,
                'extension' => $originalExtension,
                'stored_at' => now()->toIso8601String(),
                'stored_path' => $storedRealPath
            ];
            if ($fileData['hash'] === null && file_exists($storedRealPath)) {
                 Log::channel($this->logChannel)->warning('[BaseUploadHandler] Could not calculate md5 hash.', array_merge($logContext, ['path' => $storedRealPath]));
            }
             Log::channel($this->logChannel)->info('[BaseUploadHandler] File data prepared.', $logContext);

            // --- Simulate Persistence (JSON) ---
            $relativeJsonPath = 'uploads.json';
            $disk = 'local';
            Log::channel($this->logChannel)->debug('[BaseUploadHandler] Attempting to update JSON metadata storage.', array_merge($logContext, ['disk' => $disk, 'json_path' => $relativeJsonPath]));
            try {
                $uploads = [];
                if (Storage::disk($disk)->exists($relativeJsonPath)) {
                    $json = Storage::disk($disk)->get($relativeJsonPath);
                    if ($json === false || $json === null) {
                         Log::channel($this->logChannel)->warning('[BaseUploadHandler] Failed to read existing JSON file or file is empty, starting new.', array_merge($logContext, ['path' => $relativeJsonPath]));
                         $uploads = [];
                    } else {
                        $uploads = json_decode($json, true);
                         if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::channel($this->logChannel)->warning('[BaseUploadHandler] Invalid JSON detected in storage file, resetting.', array_merge($logContext, ['path' => $relativeJsonPath, 'json_error' => json_last_error_msg()]));
                            $uploads = [];
                        }
                    }
                }
                $uploads[] = $fileData;
                if (!Storage::disk($disk)->put($relativeJsonPath, json_encode($uploads, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
                     throw new Exception("Failed to write JSON file: {$relativeJsonPath} on disk '{$disk}'");
                }
                 Log::channel($this->logChannel)->info('[BaseUploadHandler] File metadata saved to JSON storage.', array_merge($logContext, ['path' => $relativeJsonPath]));
            } catch (Throwable $e_json) {
                 Log::channel($this->logChannel)->error('[BaseUploadHandler] Failed to save file data to JSON storage.', array_merge($logContext, ['path' => $relativeJsonPath, 'error' => $e_json->getMessage()]));
                 throw new Exception("Failed to save upload metadata.", 500, $e_json);
            }

            // --- Prepare Success Response ---
             // Use trans() with fallback
             $successUserMessage = trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $originalName]);
             if (empty($successUserMessage) || str_starts_with($successUserMessage, 'uploadmanager::')) {
                 $successUserMessage = "File '{$originalName}' uploaded successfully.";
             }

            // *** CORREZIONE CHIAVE RISPOSTA ***
            $responsePayload = ['userMessage' => $successUserMessage, 'fileData' => $fileData]; // <-- Usa userMessage

            Log::channel($this->logChannel)->info('[BaseUploadHandler] File upload process completed successfully.', $logContext);

            // --- Return Success ---
            return response()->json($responsePayload, 200);

        } catch (Throwable $e) { // Final Catch-all
            Log::channel($this->logChannel)->critical('[BaseUploadHandler] Unexpected fatal error during file upload handler execution.', array_merge($logContext, [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
            ]));
            // Generic JSON error response
            return response()->json([
                 // Use trans() with fallback for user message
                 'userMessage' => trans('uploadmanager::errors.generic_upload_error') ?: 'An unexpected error occurred during upload.', // <-- Usa userMessage anche qui
                 'error_details' => $e->getMessage()
                ],
                 method_exists($e, 'getStatusCode') ? $e->getStatusCode() : (is_int($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500)
            );
        }
    } // End handler method

} // End Class BaseUploadHandler