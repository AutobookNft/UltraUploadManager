<?php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;
use Exception;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\ErrorManager\Facades\TestingConditions;
use Ultra\UltraLogManager\Facades\UltraLog;

/**
 * System Temporary File Controller
 *
 * Provides fallback methods for temporary file storage when the standard storage
 * approach fails. Uses system temporary directories as an alternative storage location.
 */
class SystemTempFileController extends Controller
{
    /**
     * The logging channel name
     *
     * @var string
     */
    protected $channel = 'upload';

    /**
     * Helper method to return a standardized success response for file operations.
     *
     * @param string $message The message to include in the response
     * @param string $fileName The name of the file
     * @param string|null $tempPath The path where the file was saved (optional)
     * @return \Illuminate\Http\JsonResponse The standardized response
     */
    private function successResponse(string $message, string $fileName, ?string $tempPath = null): \Illuminate\Http\JsonResponse
    {
        $response = [
            'message' => $message,
            'fileName' => $fileName,
            'success' => true
        ];
        if ($tempPath) {
            $response['tempPath'] = $tempPath;
        }
        return response()->json($response, 200);
    }

    /**
     * Saves a file to the system temporary directory as a fallback
     * when the standard storage method fails.
     *
     * @param Request $request The HTTP request containing the file to save
     * @return \Illuminate\Http\JsonResponse Response with status of the save operation
     */
    public function saveToSystemTemp(Request $request)
    {
        UltraLog::info(
            'SystemTempSaveStart',
            'Starting fallback system temp save process',
            [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ],
            $this->channel
        );

        // Check if a file was uploaded
        if (!$request->hasFile('file')) {
            UltraLog::error('NoFileUploaded','No file in request for system temp save', [], $this->channel);
            $exception = new Exception("No file uploaded to system temp");
            return UltraError::handle('INVALID_FILE', [
                'fileName' => 'unknown'
            ], $exception);
        }

        try {
            // Get file from request with explicit null check
            $file = $request->file('file');
            if (!$file) {
                UltraLog::error('NoFileProvided', 'File object is null', [], $this->channel);
                $exception = new Exception("File object is null after request");
                return UltraError::handle('INVALID_FILE', ['fileName' => 'unknown'], $exception);
            }

            $fileName = $file->getClientOriginalName();

            UltraLog::info(
                'SystemTempSaveAttempt',
                'Attempting fallback save to system temp',
                [
                    'filename' => $fileName,
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType()
                ],
                $this->channel
            );

            // Simulate FILE_NOT_FOUND error if test condition is active
            if (TestingConditions::isTesting('FILE_NOT_FOUND')) {
                UltraLog::info(
                    'TestSimulation',
                    'Simulating FILE_NOT_FOUND error',
                    [
                        'test_condition' => 'FILE_NOT_FOUND',
                        'filename' => $fileName
                    ],
                    $this->channel
                );

                $simulatedException = new Exception("Simulated FILE_NOT_FOUND for testing");
                return UltraError::handle('FILE_NOT_FOUND', [
                    'fileName' => $fileName
                ], $simulatedException);
            }

            // Get system temp directory
            $systemTempDir = sys_get_temp_dir();

            // Create a dedicated subdirectory for the application to avoid conflicts
            $appTempDir = $systemTempDir . DIRECTORY_SEPARATOR . config('upload-manager.temp_subdir', 'ultra_upload_temp');

            UltraLog::info(
                'TempDirectoryInfo',
                'System temporary directory configuration',
                [
                    'systemTempDir' => $systemTempDir,
                    'appTempDir' => $appTempDir
                ],
                $this->channel
            );

            // Ensure directory exists
            if (!File::exists($appTempDir)) {
                try {
                    File::makeDirectory($appTempDir, 0755, true); // Permessi più sicuri
                    UltraLog::info(
                        'TempDirectoryCreated',
                        'Created application temporary directory',
                        [
                            'directory' => $appTempDir
                        ],
                        $this->channel
                    );
                } catch (Exception $dirEx) {
                    UltraLog::error(
                        'TempDirectoryCreationFailed',
                        'Failed to create application temporary directory',
                        [
                            'directory' => $appTempDir,
                            'error' => $dirEx->getMessage()
                        ],
                        $this->channel
                    );

                    return UltraError::handle('UNABLE_TO_CREATE_DIRECTORY', [
                        'directory' => $appTempDir,
                        'fileName' => $fileName
                    ], $dirEx);
                }
            }

            // Generate a unique filename to avoid overwrites
            $uniqueFilename = uniqid() . '_' . $fileName;
            $fullPath = $appTempDir . DIRECTORY_SEPARATOR . $uniqueFilename;

            UltraLog::info(
                'SavingToSystemTemp',
                'Attempting to save file to system temp directory',
                [
                    'path' => $fullPath
                ],
                $this->channel
            );

            // Move the file to the temporary directory
            if ($file->move($appTempDir, $uniqueFilename)) {
                UltraLog::info(
                    'SystemTempSaveSuccess',
                    'File saved successfully to system temp directory',
                    [
                        'fileName' => $fileName,
                        'tempPath' => $fullPath
                    ],
                    $this->channel
                );

                // Verify file exists and is accessible
                if (!File::exists($fullPath)) {
                    UltraLog::error(
                        'FileVerificationFailed',
                        'File moved but not accessible in system temp',
                        [
                            'fullPath' => $fullPath
                        ],
                        $this->channel
                    );

                    $verificationException = new Exception("File moved but not accessible at {$fullPath}");
                    return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                        'fileName' => $fileName,
                        'path' => $fullPath
                    ], $verificationException);
                }

                // Set correct permissions
                try {
                    chmod($fullPath, 0644);
                    UltraLog::info(
                        'PermissionsSet',
                        'File permissions set successfully',
                        [
                            'fullPath' => $fullPath,
                            'permissions' => '0644'
                        ],
                        $this->channel
                    );
                } catch (Exception $permEx) {
                    UltraLog::warning(
                        'PermissionSetFailed',
                        'Failed to set file permissions but continuing',
                        [
                            'fullPath' => $fullPath,
                            'error' => $permEx->getMessage()
                        ],
                        $this->channel
                    );
                    // Continue since this is not critical, but log it
                }

                return $this->successResponse(
                    trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $fileName]),
                    $fileName,
                    $fullPath
                );
            } else {
                UltraLog::error(
                    'MoveFailed',
                    'Failed to move file to temp directory',
                    [
                        'appTempDir' => $appTempDir,
                        'uniqueFilename' => $uniqueFilename
                    ],
                    $this->channel
                );

                $moveException = new Exception("Failed to move file to system temp directory");
                throw $moveException; // Will be caught in the main catch block
            }
        } catch (Exception $e) {
            UltraLog::error(
                'SystemTempSaveFailed',
                'Error saving file to system temp - attempting last resort method',
                [
                    'error' => $e->getMessage()
                ],
                $this->channel
            );

            // Last resort desperate attempt using file_put_contents
            try {
                $file = $request->file('file');
                if (!$file) {
                    UltraLog::error(
                        'NoFileProvidedLastResort',
                        'File object is null in last resort attempt',
                        [],
                        $this->channel
                    );
                    $exception = new Exception("File object is null in last resort attempt");
                    return UltraError::handle('INVALID_FILE', ['fileName' => 'unknown'], $exception);
                }

                $fileName = $file->getClientOriginalName();
                $contents = file_get_contents($file->getRealPath());

                $lastResortPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid() . '_' . $fileName;

                UltraLog::info(
                    'LastResortAttempt',
                    'Attempting last resort file save method',
                    [
                        'lastResortPath' => $lastResortPath
                    ],
                    $this->channel
                );

                if (file_put_contents($lastResortPath, $contents)) {
                    UltraLog::info(
                        'LastResortSuccess',
                        'File saved using last resort method',
                        [
                            'fileName' => $fileName,
                            'tempPath' => $lastResortPath
                        ],
                        $this->channel
                    );

                    return $this->successResponse(
                        trans('uploadmanager::uploadmanager.file_saved_successfully', ['fileCaricato' => $fileName]),
                        $fileName,
                        $lastResortPath
                    );
                } else {
                    UltraLog::error(
                        'LastResortPutContentsFailed',
                        'file_put_contents failed in last resort attempt',
                        [
                            'lastResortPath' => $lastResortPath
                        ],
                        $this->channel
                    );

                    $lastResortException = new Exception("Last resort file_put_contents failed");
                    throw $lastResortException;
                }
            } catch (Exception $lastResortException) {
                UltraLog::error(
                    'AllSaveAttemptsFailed',
                    'All file saving attempts failed, including last resort',
                    [
                        'error' => $lastResortException->getMessage()
                    ],
                    $this->channel
                );

                return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                    'fileName' => $request->hasFile('file') ? $request->file('file')->getClientOriginalName() : 'unknown',
                    'finalError' => $lastResortException->getMessage()
                ], $lastResortException);
            }
        }
    }

    /**
     * Deletes a temporary file from the system directory.
     *
     * @param Request $request The request containing the path of the file to delete
     * @return \Illuminate\Http\JsonResponse Response with status of the deletion operation
     */
    public function deleteSystemTempFile(Request $request)
    {
        $tempPath = $request->input('tempPath');
        if (empty($tempPath) || !is_string($tempPath)) {
            UltraLog::error(
                'InvalidTempPath',
                'Invalid or missing temp path for deletion',
                [],
                $this->channel
            );
            $exception = new Exception("Invalid or missing temp path for deletion");
            return UltraError::handle('TEMP_FILE_NOT_FOUND', [
                'fileName' => 'unknown'
            ], $exception);
        }

        UltraLog::info(
            'SystemTempDeleteStart',
            'Starting system temp file deletion process',
            [
                'tempPath' => $tempPath
            ],
            $this->channel
        );

        try {
            // Simulate FILE_NOT_FOUND error if test condition is active
            if (TestingConditions::isTesting('FILE_NOT_FOUND')) {
                UltraLog::info(
                    'TestSimulation',
                    'Simulating FILE_NOT_FOUND error during deletion',
                    [
                        'test_condition' => 'FILE_NOT_FOUND',
                        'tempPath' => $tempPath
                    ],
                    $this->channel
                );

                $simulatedException = new Exception("Simulated FILE_NOT_FOUND for testing");
                return UltraError::handle('FILE_NOT_FOUND', [
                    'fileName' => basename($tempPath)
                ], $simulatedException);
            }

            // Check if file exists
            if (!File::exists($tempPath)) {
                UltraLog::warning(
                    'TempFileNotFound',
                    'Temp file not found for deletion',
                    [
                        'tempPath' => $tempPath
                    ],
                    $this->channel
                );

                $fileNotFoundEx = new Exception("Temporary file not found at path: {$tempPath}");
                return UltraError::handle('TEMP_FILE_NOT_FOUND', [
                    'fileName' => basename($tempPath),
                    'path' => $tempPath
                ], $fileNotFoundEx);
            }

            // Delete the file
            if (File::delete($tempPath)) {
                UltraLog::info(
                    'TempFileDeleted',
                    'System temp file deleted successfully',
                    [
                        'tempPath' => $tempPath
                    ],
                    $this->channel
                );

                return $this->successResponse(
                    trans('uploadmanager::uploadmanager.file_deleted_successfully'),
                    basename($tempPath)
                );
            } else {
                UltraLog::error(
                    'TempFileDeletionFailed',
                    'Failed to delete system temp file',
                    [
                        'tempPath' => $tempPath
                    ],
                    $this->channel
                );

                $deletionFailedException = new Exception("Failed to delete temporary file at path: {$tempPath}");
                return UltraError::handle('ERROR_DELETING_LOCAL_TEMP_FILE', [
                    'fileName' => basename($tempPath),
                    'path' => $tempPath
                ], $deletionFailedException);
            }
        } catch (Exception $e) {
            UltraLog::error(
                'UnexpectedDeletionError',
                'Unexpected error during system temp file deletion',
                [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'tempPath' => $tempPath
                ],
                $this->channel
            );

            return UltraError::handle('UNEXPECTED_ERROR', [
                'fileName' => basename($tempPath),
                'path' => $tempPath,
                'exceptionMessage' => $e->getMessage()
            ], $e);
        }
    }
}
