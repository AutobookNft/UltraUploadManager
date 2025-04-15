<?php

namespace Ultra\UploadManager\Controllers;

use Ultra\UploadManager\Events\FileProcessingUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Symfony\Component\Process\Process;
use Illuminate\Routing\Controller;
use Ultra\UploadManager\Traits\HasUtilitys;
use Ultra\UploadManager\Traits\HasValidation;
use Ultra\UploadManager\Traits\TestingTrait;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\ErrorManager\Facades\TestingConditions;
use Ultra\UltraLogManager\Facades\UltraLog;

/**
 * Virus Scanning Controller
 *
 * Handles antivirus scanning of uploaded files using ClamAV.
 * Provides real-time feedback through broadcasting events,
 * helping users track scanning progress and results.
 */
class ScanVirusController extends Controller
{
    use HasValidation, HasUtilitys, TestingTrait;

    /**
     * User ID for the current request
     *
     * @var int|null
     */
    protected $user_id;

    /**
     * Current team of the authenticated user
     *
     * @var object|null
     */
    protected $current_team;

    /**
     * Image path for storage
     *
     * @var string|null
     */
    protected $path_image;

    /**
     * Floor price from the current team
     *
     * @var float|null
     */
    protected $floorPrice;

    /**
     * Team ID of the current team
     *
     * @var int|null
     */
    protected $team_id;

    /**
     * The logging channel name
     *
     * @var string
     */
    protected $channel = 'upload';

    /**
     * Helper method to return a standardized "scan skipped" response.
     * Used when scanning cannot be performed but the upload process should continue.
     *
     * @param string $fileName The name of the file that was not scanned
     * @param int $someInfectedFiles Count of infected files found so far
     * @return \Illuminate\Http\JsonResponse The standardized response
     */
    private function scanSkippedResponse(string $fileName, int $someInfectedFiles): \Illuminate\Http\JsonResponse
    {
        $message = trans('uploadmanager::uploadmanager.scan_skipped_but_upload_continues');

        UltraLog::warning(
            'ScanSkipped',
            'Antivirus scan skipped but upload process continues',
            [
                'fileName' => $fileName,
                'someInfectedFiles' => $someInfectedFiles
            ],
            $this->channel
        );

        FileProcessingUpload::dispatch("$message: $fileName", 'endVirusScan', Auth::id(), 0);
        return response()->json([
            'state' => 'endVirusScan',
            'userMessage' => $message,
            'file' => $fileName,
            'virusFound' => false,
            'someInfectedFiles' => $someInfectedFiles,
        ], 200);
    }

    /**
     * Initiates a virus scan on an uploaded file.
     *
     * This method performs a virus scan using ClamAV on the specified file.
     * It dispatches events to update the client about the scanning progress.
     *
     * Process flow:
     * 1. Locate the file to scan (either from system temp or standard location)
     * 2. Run the ClamAV scan
     * 3. Process the scan results
     * 4. Return appropriate response based on scan results
     *
     * @param Request $request The request containing file information
     * @return \Illuminate\Http\JsonResponse Response with scan results
     */
    public function startVirusScan(Request $request)
    {
        $fileName = $request->input('fileName');
        if (empty($fileName) || !is_string($fileName)) {
            $exception = new Exception('Invalid or missing fileName in request');
            return UltraError::handle('INVALID_INPUT', ['param' => 'fileName'], $exception);
        }
        $fileName = basename($fileName); // Sanitize fileName to prevent directory traversal

        $index = $request->input('index');
        $customTempPath = $request->input('customTempPath'); // Parameter for the alternative path

        UltraLog::info(
            'VirusScanStart',
            'Starting antivirus scan process',
            [
                'fileName' => $fileName,
                'index' => $index,
                'customTempPath' => $customTempPath
            ],
            $this->channel
        );

        // Convert 'finished' to boolean, ensuring correct interpretation from FormData
        $finished = filter_var($request->input('finished'), FILTER_VALIDATE_BOOLEAN);

        UltraLog::info(
            'ScanParams',
            'Scan process parameters',
            [
                'finished' => $finished
            ],
            $this->channel
        );

        // Validate and convert someInfectedFiles to an integer with a default of 0
        $someInfectedFiles = (int) $request->input('someInfectedFiles', 0);

        UltraLog::info(
            'InfectedFilesCount',
            'Number of infected files so far',
            [
                'someInfectedFiles' => $someInfectedFiles
            ],
            $this->channel
        );

        // Determine the file path to scan
        if ($customTempPath && file_exists($customTempPath)) {
            $filePath = $customTempPath;
            UltraLog::info(
                'CustomPathUsed',
                'Using alternative temporary file path',
                [
                    'filePath' => $filePath
                ],
                $this->channel
            );
        } else {
            $filePath = get_temp_file_path($fileName);
            UltraLog::info(
                'StandardPathUsed',
                'Using standard temporary file path',
                [
                    'filePath' => $filePath
                ],
                $this->channel
            );
        }

        $scanningIsRunning = trans('uploadmanager::uploadmanager.antivirus_scan_in_progress');
        FileProcessingUpload::dispatch("$scanningIsRunning: $fileName", 'virusScan', Auth::id(), 0);

        // Check if the file exists
        if (!$fileName || !file_exists($filePath)) {
            UltraLog::warning(
                'FileNotFound',
                'File not found for antivirus scan',
                [
                    'fileName' => $fileName,
                    'filePath' => $filePath
                ],
                $this->channel
            );

            // Test scenario for FILE_NOT_FOUND
            if (TestingConditions::isTesting('FILE_NOT_FOUND')) {
                UltraLog::info(
                    'TestSimulation',
                    'Simulating FILE_NOT_FOUND error',
                    [
                        'test_condition' => 'FILE_NOT_FOUND'
                    ],
                    $this->channel
                );

                $simulatedException = new Exception("Simulated FILE_NOT_FOUND for testing");
                return UltraError::handle('FILE_NOT_FOUND', [
                    'fileName' => $fileName
                ], $simulatedException);
            }

            // If the request contains the file, try to save it directly before scanning
            if ($request->hasFile('file')) {
                try {
                    $uploadedFile = $request->file('file');
                    $tempDir = dirname($filePath);

                    UltraLog::info(
                        'DirectFileSave',
                        'Attempting to save file directly before scanning',
                        [
                            'tempDir' => $tempDir,
                            'fileName' => $fileName
                        ],
                        $this->channel
                    );

                    if (!file_exists($tempDir)) {
                        mkdir($tempDir, 0755, true);
                        UltraLog::info(
                            'DirectoryCreated',
                            'Created temporary directory',
                            [
                                'tempDir' => $tempDir
                            ],
                            $this->channel
                        );
                    }

                    if ($uploadedFile->move($tempDir, basename($filePath))) {
                        UltraLog::info(
                            'DirectFileSaveSuccess',
                            'File saved successfully before scanning',
                            [
                                'filePath' => $filePath
                            ],
                            $this->channel
                        );
                    } else {
                        UltraLog::error(
                            'DirectFileSaveFailed',
                            'Unable to save the file before scanning',
                            [
                                'fileName' => $fileName
                            ],
                            $this->channel
                        );

                        $saveException = new Exception("Failed to move uploaded file to temporary directory");
                        return UltraError::handle('IMPOSSIBLE_SAVE_FILE', [
                            'fileName' => $fileName,
                            'path' => $tempDir
                        ], $saveException);
                    }
                } catch (Exception $e) {
                    UltraLog::error(
                        'FilesystemException',
                        'Exception during direct save attempt',
                        [
                            'error' => $e->getMessage()
                        ],
                        $this->channel
                    );

                    return UltraError::handle('ERROR_DURING_FILE_UPLOAD', [
                        'fileName' => $fileName,
                        'error' => $e->getMessage()
                    ], $e);
                }
            } else {
                return $this->scanSkippedResponse($fileName, $someInfectedFiles);
            }
        }

        try {
            // Test scenario for SCAN_ERROR
            if (TestingConditions::isTesting('SCAN_ERROR') && $index === '0') {
                UltraLog::info(
                    'TestSimulation',
                    'Simulating SCAN_ERROR condition',
                    [
                        'test_condition' => 'SCAN_ERROR',
                        'index' => $index
                    ],
                    $this->channel
                );

                $simulatedScanEx = new Exception("Simulated scan error for testing");
                return UltraError::handle('SCAN_ERROR', [
                    'fileName' => $fileName
                ], $simulatedScanEx);
            }

            // Run ClamAV scan on the file
            $binary = config('upload-manager.antivirus.binary', 'clamscan');
            // Verifica se il binary esiste ed è eseguibile, altrimenti usa 'clamscan' nel PATH
            $binaryPath = (file_exists($binary) && is_executable($binary)) ? $binary : 'clamscan';
            UltraLog::info(
                'ScanStart',
                'Starting ClamAV scan process',
                [
                    'fileName' => $fileName,
                    'filePath' => $filePath,
                    'binary' => $binaryPath
                ],
                $this->channel
            );

            $process = new Process([$binaryPath, '--no-summary', '--stdout', $filePath]);
            $process->run();

            if (!$process->isSuccessful()) {
                UltraLog::error(
                    'ScanProcessFailed',
                    'Error during antivirus scan process',
                    [
                        'fileName' => $fileName,
                        'error' => $process->getErrorOutput()
                    ],
                    $this->channel
                );

                $scanException = new Exception($process->getErrorOutput() ?: "ClamAV scan process failed");
                return UltraError::handle('SCAN_ERROR', [
                    'fileName' => $fileName,
                    'error' => $process->getErrorOutput()
                ], $scanException);
            }

            $output = $process->getOutput();
            UltraLog::info(
                'ScanComplete',
                'Antivirus scan completed',
                [
                    'fileName' => $fileName,
                    'output' => $output
                ],
                $this->channel
            );

            // Test scenario for VIRUS_FOUND
            if (TestingConditions::isTesting('VIRUS_FOUND') && $index === '0') {
                UltraLog::info(
                    'TestSimulation',
                    'Simulating VIRUS_FOUND condition',
                    [
                        'test_condition' => 'VIRUS_FOUND',
                        'index' => $index
                    ],
                    $this->channel
                );

                $simulatedVirusEx = new Exception("Simulated virus detection for testing");
                return UltraError::handle('VIRUS_FOUND', [
                    'fileName' => $fileName
                ], $simulatedVirusEx);
            }

            // Process scan results with different logic for final file vs. intermediate file
            if ($finished) {
                if ($someInfectedFiles) {
                    // Some files were already found infected in previous scans
                    $message = trans('uploadmanager::uploadmanager.one_or_more_files_were_found_infected');
                    FileProcessingUpload::dispatch($message, 'allFileScannedSomeInfected', Auth::id(), 0);

                    UltraLog::warning(
                        'InfectedFilesFound',
                        'One or more files were found infected',
                        [
                            'fileName' => $fileName,
                            'someInfectedFiles' => $someInfectedFiles
                        ],
                        $this->channel
                    );

                    if (strpos($output, 'FOUND') !== false) {
                        $virusException = new Exception("Virus signature found in file: " . $fileName);
                        return UltraError::handle('VIRUS_FOUND', [
                            'fileName' => $fileName
                        ], $virusException);
                    } else {
                        $responseCode = 200;
                        $message = trans('uploadmanager::uploadmanager.one_or_more_files_were_found_infected');

                        UltraLog::info(
                            'ScanFinishedWithInfections',
                            'All files are scanned and one or more were found infected',
                            [
                                'fileName' => $fileName,
                                'someInfectedFiles' => $someInfectedFiles
                            ],
                            $this->channel
                        );

                        return response()->json([
                            'userMessage' => $message,
                            'state' => 'allFileScannedSomeInfected',
                            'file' => $fileName,
                            'virusFound' => false,
                            'someInfectedFiles' => $someInfectedFiles,
                        ], $responseCode);
                    }
                } else {
                    // No infected files found in any scan
                    $responseCode = 200;
                    $message = trans('uploadmanager::uploadmanager.all_files_were_scanned_no_infected_files');
                    FileProcessingUpload::dispatch($message, 'allFileScannedNotInfected', Auth::id(), 0);

                    UltraLog::info(
                        'ScanFinishedClean',
                        'All files are scanned and no infected files were found',
                        [
                            'fileName' => $fileName
                        ],
                        $this->channel
                    );

                    return response()->json([
                        'state' => 'allFileScannedNotInfected',
                        'userMessage' => $message,
                        'file' => $fileName,
                        'virusFound' => false,
                        'someInfectedFiles' => $someInfectedFiles,
                    ], $responseCode);
                }
            } else {
                // Processing an individual file (not the final one)
                if (strpos($output, 'FOUND') !== false) {
                    // Current file is infected
                    UltraLog::warning(
                        'VirusFound',
                        'The uploaded file was detected as infected',
                        [
                            'fileName' => $fileName
                        ],
                        $this->channel
                    );

                    $message = trans('uploadmanager::uploadmanager.the_uploaded_file_was_detected_as_infected');
                    $statusScan = 'infected';
                    FileProcessingUpload::dispatch($message, $statusScan, Auth::id(), 0);

                    $virusException = new Exception("Virus signature found in file: " . $fileName);
                    return UltraError::handle('VIRUS_FOUND', [
                        'fileName' => $fileName
                    ], $virusException);
                } else {
                    // Current file is clean
                    $statusScan = 'virusScan';
                    $message = trans('uploadmanager::uploadmanager.scanning_success', ['fileCaricato' => $fileName]);
                    $responseCode = 200;

                    UltraLog::info(
                        'FileScanSuccess',
                        'Scan completed successfully for the file',
                        [
                            'fileName' => $fileName
                        ],
                        $this->channel
                    );

                    FileProcessingUpload::dispatch($message, $statusScan, Auth::id(), 0);

                    return response()->json([
                        'state' => $statusScan,
                        'userMessage' => $message,
                        'file' => $fileName,
                        'virusFound' => false,
                        'someInfectedFiles' => $someInfectedFiles,
                    ], $responseCode);
                }
            }
        } catch (Exception $e) {
            UltraLog::error(
                'UnexpectedError',
                'Unexpected error during file scan',
                [
                    'fileName' => $fileName,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                $this->channel
            );

            // Handle with Ultra Error Manager
            return UltraError::handle('UNEXPECTED_ERROR', [
                'fileName' => $fileName,
                'exceptionMessage' => $e->getMessage()
            ], $e);
        }
    }
}
