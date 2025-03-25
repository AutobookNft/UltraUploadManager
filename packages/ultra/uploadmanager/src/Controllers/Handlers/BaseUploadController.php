<?php

namespace Ultra\UploadManager\Controllers\Handlers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Ultra\UploadManager\Events\FileProcessingUpload;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\ErrorManager\Facades\TestingConditions;
use Ultra\UltraLogManager\Facades\UltraLog;  // Aggiungiamo questa importazione
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;
use Exception; 

/**
 * Base Upload Controller
 *
 * Handles the core file upload functionality with error handling
 * integrated with Ultra Error Manager.
 *
 * @package Ultra\UploadManager\Controllers\Handlers
 */
class BaseUploadController extends Controller
{
    use HasValidation, HasUtilitys;

    /**
     * The log channel to use for upload-related log entries
     *
     * @var string
     */
    protected $channel = 'upload';

    /**
     * Main handler method for file uploads
     *
     * Processes the uploaded file through validation, storage, and logging
     * with comprehensive error handling using Ultra Error Manager.
     *
     * @param Request $request The HTTP request containing the file
     * @return \Illuminate\Http\JsonResponse Response with status or error
     */
    public function handler(Request $request)
    {
        // Iniziamo il logging con UltraLogManager
        UltraLog::info('UploadStart', 'Starting file upload process', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ], $this->channel);

        // Controlla se il contenuto del POST supera i limiti
        if (empty($_FILES) && empty($_POST) && isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post') {
            // Questa condizione si verifica quando i dati POST superano il limite impostato in php.ini
            UltraLog::error('PostSizeLimitExceeded', 'POST data exceeds server limits', [
                'post_max_size' => ini_get('post_max_size'),
                'ip' => $request->ip()
            ], $this->channel);

            return UltraError::handle('POST_SIZE_LIMIT_EXCEEDED', [
                'post_max_size' => ini_get('post_max_size')
            ], new Exception('POST data exceeds server limits'));
        }

        try {
            // 1. Validate file existence and integrity
            $file = $request->file('file');

            if (!$file || !$file->isValid()) {
                UltraLog::warning('FileValidation', 'Invalid or missing file received', [
                    'has_file' => $request->hasFile('file')
                ], $this->channel);

                return UltraError::handle('INVALID_FILE', [
                    'fileName' => $request->hasFile('file') ? $request->file('file')->getClientOriginalName() : 'unknown'
                ]);
            }

            // Get current file index in the upload sequence
            $index = $request->input('index');
            UltraLog::info('FileProcessing', 'Processing file in upload sequence', [
                'index' => $index,
                'file_name' => $file->getClientOriginalName()
            ], $this->channel);

            // Simulate FILE_NOT_FOUND error if test condition is active
            if (TestingConditions::isTesting('FILE_NOT_FOUND') && $index === '0') {
                UltraLog::info('TestSimulation', 'Simulating FILE_NOT_FOUND error', [
                    'test_condition' => 'FILE_NOT_FOUND',
                    'index' => $index
                ], $this->channel);

                return UltraError::handle('FILE_NOT_FOUND', [
                    'fileName' => $file->getClientOriginalName()
                ]);
            }

            // 2. Store file metadata before moving the file
            $fileSize = $file->getSize();
            $originalExtension = $file->getClientOriginalExtension();
            $originalName = $file->getClientOriginalName();

            UltraLog::info('FileMetadata', 'File metadata captured', [
                'name' => $originalName,
                'size' => $fileSize,
                'extension' => $originalExtension
            ], $this->channel);

            // Simulate GENERIC_SERVER_ERROR if test condition is active
            if (TestingConditions::isTesting('GENERIC_SERVER_ERROR') && $index === '0') {
                UltraLog::info('TestSimulation', 'Simulating GENERIC_SERVER_ERROR', [
                    'test_condition' => 'GENERIC_SERVER_ERROR',
                    'index' => $index
                ], $this->channel);

                return UltraError::handle('GENERIC_SERVER_ERROR', [
                    'fileName' => $originalName
                ]);
            }

            // 3. Validate the file (extension, size, etc.)
            try {
                $this->validateFile($file, $index);
            } catch (\Exception $e) {
                UltraLog::warning('ValidationFailure', 'File validation failed', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage()
                ], $this->channel);

                return UltraError::handle('INVALID_FILE_VALIDATION', [
                    'fileName' => $originalName,
                    'error' => $e->getMessage()
                ], $e);
            }

            // 4. Configure and move the file to storage location
            $path = config('upload-manager.default_path');
            UltraLog::info('FileStorage', 'Moving file to storage location', [
                'destination_path' => $path,
                'file_name' => $originalName
            ], $this->channel);

            try {
                $file->move($path, $originalName);
            } catch (\Exception $e) {
                UltraLog::error('FileMoveFailure', 'Failed to move uploaded file', [
                    'destination_path' => $path,
                    'file_name' => $originalName,
                    'exception' => get_class($e)
                ], $this->channel);

                return UltraError::handle('ERROR_DURING_FILE_UPLOAD', [
                    'fileName' => $originalName,
                    'path' => $path
                ], $e);
            }

            // 5. Build file data with collected information
            $fullPath = $path . DIRECTORY_SEPARATOR . $originalName;
            $fileData = [
                'name'      => $originalName,
                'hash'      => md5_file($fullPath),
                'size'      => $fileSize,
                'extension' => $originalExtension,
            ];

            UltraLog::info('FileDataPrepared', 'File data prepared for storage', [
                'file_data' => json_encode($fileData)
            ], $this->channel);

            // 6. Save in JSON storage
            $jsonPath = storage_path('app/uploads.json');
            $uploads = [];
            if (file_exists($jsonPath)) {
                $json = file_get_contents($jsonPath);
                $uploads = json_decode($json, true) ?? [];
            }
            $uploads[] = $fileData;

            try {
                file_put_contents($jsonPath, json_encode($uploads, JSON_PRETTY_PRINT));
            } catch (\Exception $e) {
                UltraLog::error('JsonStorageFailure', 'Failed to save file data to JSON storage', [
                    'json_path' => $jsonPath,
                    'exception' => get_class($e)
                ], $this->channel);

                return UltraError::handle('ERROR_SAVING_FILE_METADATA', [
                    'fileName' => $originalName
                ], $e);
            }

            // 7. Prepare success response
            $response = ['message' => 'Upload completed, starting virus scan...'];
            UltraLog::info('UploadComplete', 'File upload process completed', [
                'response' => json_encode($response),
                'file_name' => $originalName
            ], $this->channel);

            // 8. Return success response to client
            return response()->json($response);

        } catch (\Exception $e) {
            // Catch any unexpected exceptions
            UltraLog::error('UnexpectedError', 'Unexpected error during file upload', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], $this->channel);

            // Handle with Ultra Error Manager
            return UltraError::handle('UNEXPECTED_ERROR', [
                'fileName' => $request->hasFile('file') ? $request->file('file')->getClientOriginalName() : 'unknown',
                'exceptionMessage' => $e->getMessage()
            ], $e);
        }
    }
}
