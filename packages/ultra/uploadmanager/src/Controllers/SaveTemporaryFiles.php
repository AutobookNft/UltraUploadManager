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
     * This method attempts to save an uploaded file to a temporary storage location.
     * If the initial save attempt fails, it tries multiple fallback strategies:
     * 1. Change directory permissions and retry
     * 2. Recreate the directory and retry
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
            return UltraError::handle('INVALID_FILE', [
                'fileName' => 'unknown'
            ], $exception);
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
            } catch (\Exception $e) {
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
            $tempFolderPath = config('app.bucket_temp_file_folder', 'app/private/temp');
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
                } catch (\Exception $e) {
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

            $diskName = 'local'; // Default disk for local storage
            $relativePath = $this->getRelativePath($tempFolderPath);

            try {
                // Primary save attempt
                if ($file->move($fullPath, $fileName)) {
                    UltraLog::info('FileSavedSuccessfully', 'File saved successfully using direct move', [
                        'fileName' => $fileName,
                        'fullPath' => $storedFilePath
                    ], $this->channel);
                } else {
                    throw new Exception('Failed to move file directly');
                }

                // Verify file was saved
                if (!file_exists($storedFilePath)) {
                    UltraLog::error('FileVerificationFailed', 'File was not saved correctly', [
                        'storedFilePath' => $storedFilePath
                    ], $this->channel);
                    $verifyException = new Exception("File moved but not accessible at {$storedFilePath}");
                    return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                        'fileName' => $fileName,
                        'path' => $fullPath
                    ], $verifyException);
                }

                UltraLog::info('TemporaryFileUploadComplete', 'File saved temporarily with success', [
                    'fileName' => $fileName,
                    'path' => $fullPath
                ], $this->channel);

                return response()->json([
                    'message' => trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $fileName]),
                    'fileName' => $fileName,
                    'bucketFolderTemp' => $fullPath
                ], 200);

            } catch (Exception $e) {
                UltraLog::warning('PrimarySaveAttemptFailed', 'Error saving file. Attempting to change permissions and retry', [
                    'error' => $e->getMessage(),
                    'fileName' => $fileName
                ], $this->channel);

                // Fallback 1: Change permissions
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
                    } else {
                        throw new Exception('Failed to move file after changing permissions');
                    }

                    if (!file_exists($storedFilePath)) {
                        UltraLog::error('FileVerificationFailedAfterPermissionChange', 'File was not saved correctly after permission change', [
                            'storedFilePath' => $storedFilePath
                        ], $this->channel);
                        $verifyPermEx = new Exception("File moved but not accessible after permission change");
                        return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                            'fileName' => $fileName,
                            'path' => $fullPath,
                            'stage' => 'after_permission_change'
                        ], $verifyPermEx);
                    }

                    return response()->json([
                        'message' => trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $fileName]),
                        'fileName' => $fileName,
                        'bucketFolderTemp' => $fullPath
                    ], 200);

                } catch (Exception $permEx) {
                    UltraLog::warning('FallbackPermissionChangeFailed', 'Error after changing permissions. Attempting to recreate directory', [
                        'error' => $permEx->getMessage()
                    ], $this->channel);

                    // Fallback 2: Recreate directory
                    try {
                        $this->handleDirectoryError($fullPath);
                        UltraLog::info('DirectoryRecreated', 'Temporary directory recreated successfully', [
                            'directory' => $fullPath
                        ], $this->channel);

                        if ($file->move($fullPath, $fileName)) {
                            UltraLog::info('FileSavedAfterDirectoryRecreation', 'File saved successfully after recreating directory', [
                                'fileName' => $fileName
                            ], $this->channel);
                        } else {
                            throw new Exception('Failed to move file after recreating directory');
                        }

                        if (!file_exists($storedFilePath)) {
                            UltraLog::error('FileVerificationFailedAfterDirectoryRecreation', 'File was not saved correctly after recreating directory', [
                                'storedFilePath' => $storedFilePath
                            ], $this->channel);
                            $verifyRecreateEx = new Exception("File moved but not accessible after directory recreation");
                            return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                                'fileName' => $fileName,
                                'path' => $fullPath,
                                'stage' => 'after_recreate'
                            ], $verifyRecreateEx);
                        }

                        return response()->json([
                            'message' => trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $fileName]),
                            'fileName' => $fileName,
                            'bucketFolderTemp' => $fullPath
                        ], 200);

                    } catch (Exception $dirEx) {
                        UltraLog::error('AllFallbacksFailed', 'All file saving attempts failed', [
                            'error' => $dirEx->getMessage(),
                            'fileName' => $fileName
                        ], $this->channel);
                        return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                            'fileName' => $fileName,
                            'path' => $fullPath,
                            'finalError' => $dirEx->getMessage()
                        ], $dirEx);
                    }
                }
            }
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
