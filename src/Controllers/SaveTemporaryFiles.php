<?php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;
use Ultra\UploadManager\Traits\TestingTrait;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\ErrorManager\Facades\TestingConditions;
use Ultra\UltraLogManager\Facades\UltraLog;

/**
 * Temporary File Storage Controller
 *
 * Handles the temporary storage of uploaded files with robust error handling
 * and fallback mechanisms to ensure reliable file storage.
 */
class SaveTemporaryFiles extends Controller
{
    use HasValidation, HasUtilitys, TestingTrait;

    /**
     * The logging channel name
     *
     * @var string
     */
    protected $channel = 'upload';

    /**
     * Saves a temporary file with robust permissions handling.
     *
     * This method attempts to save an uploaded file to a temporary storage location
     * using multiple fallback strategies if the initial attempt fails.
     *
     * @param Request $request The request containing the file to save
     * @return \Illuminate\Http\JsonResponse Response with status of the save operation
     */
    public function saveTemporaryFile(Request $request)
    {
        UltraLog::info('TemporaryFileUploadStart', 'Starting temporary file upload process', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ], $this->channel);

        // Check if a file was uploaded
        if (!$request->hasFile('file')) {
            UltraLog::error('NoFileUploaded', 'No file in request', [], $this->channel);
            $exception = new Exception('No file uploaded');
            return UltraError::handle('INVALID_FILE', [
                'fileName' => 'unknown'
            ], $exception);
        }

        // Get file from request with explicit null check
        $file = $request->file('file');
        if (!$file) {
            UltraLog::error('NoFileProvided', 'File object is null', [], $this->channel);
            $exception = new Exception('File object is null after request');
            return UltraError::handle('INVALID_FILE', ['fileName' => 'unknown' ], $exception);
        }

        try {
            // Get the file name
            $fileName = $file->getClientOriginalName();
            UltraLog::info('TemporaryFileDetails', 'File to save in temp folder', [
                'filename' => $fileName,
                'size' => $file->getSize(),
                'mime' => $file->getMimeType()
            ], $this->channel);

            // Simulate FILE_NOT_FOUND error if test condition is active
            if (TestingConditions::isTesting('FILE_NOT_FOUND')) {
                UltraLog::info('TestSimulation', 'Simulating FILE_NOT_FOUND error', [
                    'test_condition' => 'FILE_NOT_FOUND',
                    'filename' => $fileName
                ], $this->channel);
                $simulatedException = new Exception("Simulated FILE_NOT_FOUND for testing");
                return UltraError::handle('FILE_NOT_FOUND', [
                    'fileName' => $fileName
                ], $simulatedException);
            }

            // Validate the file
            try {
                $this->validateFile($file);
            } catch (Exception $e) {
                UltraLog::warning('ValidationFailure', 'File validation failed', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage()
                ], $this->channel);
                return UltraError::handle('INVALID_FILE_VALIDATION', [
                    'fileName' => $fileName,
                    'error' => $e->getMessage()
                ], $e);
            }

            // Get the temp folder config value
            $tempFolderPath = config('upload-manager.temp_path', 'app/private/temp');
            $fullPath = strpos($tempFolderPath, '/') === 0 ? $tempFolderPath : storage_path($tempFolderPath);

            UltraLog::info('TemporaryPathDetails', 'Temporary path configuration', [
                'config_path' => $tempFolderPath,
                'full_path' => $fullPath
            ], $this->channel);

            // Ensure directory exists
            if (!file_exists($fullPath)) {
                UltraLog::info('CreateDirectory', 'Creating temporary directory', [
                    'directory' => $fullPath
                ], $this->channel);
                try {
                    $this->createDirectory($fullPath);
                } catch (Exception $e) {
                    UltraLog::error('DirectoryCreationFailed', 'Failed to create temporary directory', [
                        'directory' => $fullPath,
                        'exception' => get_class($e),
                        'message' => $e->getMessage()
                    ], $this->channel);
                    return UltraError::handle('UNABLE_TO_CREATE_DIRECTORY', [
                        'directory' => $fullPath,
                        'fileName' => $fileName
                    ], $e);
                }
            }

            // Attempt to save the file
            $storedFilePath = $fullPath . '/' . $fileName;
            UltraLog::info('SavingFile', 'Attempting to save file', [
                'storedFilePath' => $storedFilePath
            ], $this->channel);

            // Tentativo in ordine di priorità: diretto, dopo cambio permessi, dopo ricreazione directory
            return $this->attemptSaveWithFallbacks($file, $fullPath, $fileName, $storedFilePath);

        } catch (Exception $e) {
            UltraLog::error('UnexpectedError', 'Unexpected error during file upload', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], $this->channel);
            return UltraError::handle('UNEXPECTED_ERROR', [
                'fileName' => $fileName ?? 'unknown',
                'exceptionMessage' => $e->getMessage()
            ], $e);
        }
    }

    /**
     * Attempts to save a file using multiple fallback methods
     *
     * @param \Illuminate\Http\UploadedFile $file The file to save
     * @param string $fullPath The directory path where to save the file
     * @param string $fileName The name of the file
     * @param string $storedFilePath The complete path where the file will be stored
     * @return \Illuminate\Http\JsonResponse Response with operation status
     */
    protected function attemptSaveWithFallbacks($file, $fullPath, $fileName, $storedFilePath)
    {
        $diskName = 'local'; // Default disk for local storage
        $relativePath = $this->getRelativePath($fullPath);

        // Attempt 1: Direct save
        try {
            if ($file->move($fullPath, $fileName)) {
                UltraLog::info('FileSavedSuccessfully', 'File saved successfully using direct move', [
                    'fileName' => $fileName,
                    'fullPath' => $storedFilePath
                ], $this->channel);

                if ($this->verifyFileExists($storedFilePath, $fileName, $fullPath)) {
                    return $this->createSuccessResponse($fileName, $fullPath);
                }
                // If verification fails without throwing exception
                throw new Exception('File verification failed after direct move');
            } else {
                throw new Exception('Failed to move file directly');
            }
        } catch (Exception $e) {
            UltraLog::warning('PrimarySaveAttemptFailed', 'Error saving file. Attempting to change permissions and retry', [
                'error' => $e->getMessage(),
                'fileName' => $fileName
            ], $this->channel);

            // Attempt 2: Change permissions and retry
            try {
                if (!$this->changePermissions($fullPath, 'directory')) {
                    UltraLog::warning('PermissionChangeFailed', 'Failed to change directory permissions', [
                        'directory' => $fullPath
                    ], $this->channel);
                    $permException = new Exception("Failed to change permissions on directory {$fullPath}");
                    return UltraError::handle('UNABLE_TO_CHANGE_PERMISSIONS', [
                        'directory' => $fullPath,
                        'fileName' => $fileName
                    ], $permException);
                }

                UltraLog::info('PermissionsChanged', 'Directory permissions changed successfully', [
                    'directory' => $fullPath
                ], $this->channel);

                if ($file->move($fullPath, $fileName)) {
                    UltraLog::info('FileSavedAfterPermissionsChange', 'File saved successfully after changing directory permissions', [
                        'fileName' => $fileName
                    ], $this->channel);

                    if ($this->verifyFileExists($storedFilePath, $fileName, $fullPath, 'after_permission_change')) {
                        return $this->createSuccessResponse($fileName, $fullPath);
                    }
                    // If verification fails without throwing exception
                    throw new Exception('File verification failed after permission change');
                } else {
                    throw new Exception('Failed to move file after changing permissions');
                }
            } catch (Exception $permEx) {
                UltraLog::warning('FallbackPermissionChangeFailed', 'Error after changing permissions. Attempting to recreate directory', [
                    'error' => $permEx->getMessage()
                ], $this->channel);

                // Attempt 3: Recreate directory and retry (final attempt)
                $this->handleDirectoryError($fullPath);
                UltraLog::info('DirectoryRecreated', 'Temporary directory recreated successfully', [
                    'directory' => $fullPath
                ], $this->channel);

                if ($file->move($fullPath, $fileName)) {
                    UltraLog::info('FileSavedAfterDirectoryRecreation', 'File saved successfully after recreating directory', [
                        'fileName' => $fileName
                    ], $this->channel);

                    if ($this->verifyFileExists($storedFilePath, $fileName, $fullPath, 'after_recreate')) {
                        return $this->createSuccessResponse($fileName, $fullPath);
                    }
                }

                // If we reach here, all attempts have failed
                UltraLog::error('AllFallbacksFailed', 'All file saving attempts failed', [
                    'fileName' => $fileName
                ], $this->channel);

                return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                    'fileName' => $fileName,
                    'path' => $fullPath,
                    'finalError' => 'Failed after all save attempts'
                ], new Exception('Failed after all save attempts'));
            }
        }
    }

    /**
     * Verifies that a file exists after being saved
     *
     * @param string $storedFilePath The full path to the stored file
     * @param string $fileName The name of the file
     * @param string $fullPath The directory path
     * @param string|null $stage Optional stage identifier for debugging
     * @return bool True if file exists, false otherwise
     */
    protected function verifyFileExists($storedFilePath, $fileName, $fullPath, $stage = null)
    {
        if (!file_exists($storedFilePath)) {
            UltraLog::error('FileVerificationFailed' . ($stage ? "After{$stage}" : ''), 'File was not saved correctly', [
                'storedFilePath' => $storedFilePath,
                'stage' => $stage
            ], $this->channel);

            $stageInfo = $stage ? ", stage: {$stage}" : '';
            $verifyException = new Exception("File moved but not accessible at {$storedFilePath}{$stageInfo}");
            UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                'fileName' => $fileName,
                'path' => $fullPath,
                'stage' => $stage
            ], $verifyException);

            return false;
        }
        return true;
    }

    /**
     * Creates a success response for file saving
     *
     * @param string $fileName The name of the saved file
     * @param string $fullPath The path where the file was saved
     * @return \Illuminate\Http\JsonResponse Response with success status
     */
    protected function createSuccessResponse($fileName, $fullPath)
    {
        return response()->json([
            'message' => trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $fileName]),
            'fileName' => $fileName,
            'bucketFolderTemp' => $fullPath
        ], 200);
    }

    /**
     * Extracts the relative path for Laravel storage from a full or relative path
     *
     * @param string $path The path to process
     * @return string The relative path suitable for Laravel's storage functions
     */
    protected function getRelativePath($path)
    {
        if (preg_match('~^app/(?:public/|private/)?(.*)$~', $path, $matches)) {
            return $matches[1];
        }
        return $path;
    }
}
