<?php

/**
 * Trait providing file validation logic for the UltraUploadManager.
 *
 * Offers methods to validate uploaded files against rules (extensions, MIME types,
 * size, filename conventions, structure) retrieved dynamically via UltraConfigManager (UCM).
 * Relies on the consuming class to provide LoggerInterface (ULM), ErrorManagerInterface (UEM),
 * TestingConditionsManager, and UltraConfigManager (UCM) instances.
 *
 * @package     Ultra\UploadManager\Traits
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.2.1 // Corrected UCM usage (get instead of getConfigByKey). Oracode v1.5.0 docs. Completed logs.
 * @since       1.0.0
 *
 * @property-read LoggerInterface $logger Injected Logger instance from consuming class (ULM).
 * @property-read ErrorManagerInterface $errorManager Injected ErrorManager instance from consuming class (UEM).
 * @property-read TestingConditionsManager $testingConditions Injected TestingConditions instance from consuming class.
 * @property-read UltraConfigManager $configManager Injected UltraConfigManager instance from consuming class (UCM).
 * @property      string $channel Log channel name (expected to be defined in consuming class).
 */

namespace Ultra\UploadManager\Traits;

// Laravel & PHP Dependencies
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Validation\Validator as IlluminateValidator;
use Psr\Log\LoggerInterface;
use Throwable;
use Exception;
use ImagickException;

// Ultra Ecosystem Dependencies
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface;
use Ultra\ErrorManager\Services\TestingConditionsManager;
use Ultra\UltraConfigManager\UltraConfigManager; // Import UCM

trait HasValidation
{
    /**
     * Validate an uploaded file against a series of checks using UCM for rules.
     *
     * Main entry point for validation. Orchestrates calls to specific validation methods.
     * Retrieves validation parameters (extensions, sizes, patterns) from UltraConfigManager.
     * Throws an Exception on failure, intended to be caught and handled by UEM in the calling context.
     *
     * --- Core Logic ---
     * 1. Logs start. Checks for required dependencies (logger, errorManager, configManager, testingConditions).
     * 2. Calls `baseValidation` (MIME, Ext, Size from UCM).
     * 3. If image, calls `validateImageStructure`.
     * 4. Calls `validateFileName` (Pattern, Length from UCM).
     * 5. If PDF, calls `validatePdfContent`.
     * 6. Logs success.
     * 7. Catches & logs failures, then re-throws Exception.
     * --- End Core Logic ---
     *
     * @param UploadedFile $file The file instance to validate.
     * @param int|string $index Optional index used for testing simulations.
     * @return void
     *
     * @throws Exception If any validation rule fails.
     * @throws \LogicException If required dependencies (logger, uem, ucm, testing) are not available in the consuming class.
     *
     * @sideEffect Reads configuration via UCM ('AllowedFileType.*', 'file_validation.*').
     * @sideEffect Logs validation steps via LoggerInterface (ULM).
     * @sideEffect Interacts with TestingConditionsManager.
     * @see self::baseValidation()
     * @see self::validateImageStructure()
     * @see self::validateFileName()
     * @see self::validatePdfContent()
     * @see \Ultra\UltraConfigManager\UltraConfigManager::get() For configuration retrieval.
     */
    protected function validateFile(UploadedFile $file, int|string $index = 0): void
    {
        // --- Dependency Checks ---
        if (!isset($this->logger) || !$this->logger instanceof LoggerInterface) {
            // Cannot log this error as logger is missing
            throw new \LogicException('Consuming class must provide LoggerInterface $logger.');
        }
         if (!isset($this->errorManager) || !$this->errorManager instanceof ErrorManagerInterface) {
            $this->logger->error('[HasValidation][FATAL] Missing dependency: ErrorManagerInterface $errorManager');
            throw new \LogicException('Consuming class must provide ErrorManagerInterface $errorManager.');
        }
        if (!isset($this->configManager) || !$this->configManager instanceof UltraConfigManager) {
             $this->logger->error('[HasValidation][FATAL] Missing dependency: UltraConfigManager $configManager');
            throw new \LogicException('Consuming class must provide UltraConfigManager $configManager.');
        }
        if (!isset($this->testingConditions) || !$this->testingConditions instanceof TestingConditionsManager) {
             $this->logger->error('[HasValidation][FATAL] Missing dependency: TestingConditionsManager $testingConditions');
             throw new \LogicException('Consuming class must provide TestingConditionsManager $testingConditions.');
        }
        // --- End Dependency Checks ---

        $logChannel = $this->channel ?? 'upload'; // Use channel from consuming class or default
        $fileNameForLog = $file->getClientOriginalName(); // Get filename once for logging

        $this->logger->info(
            '[HasValidation] Starting file validation process (using UCM).',
            [
                'fileName' => $fileNameForLog,
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'index' => $index,
                'channel' => $logChannel // Log the channel being used
            ]
        );

        try {
            // Perform base validation (MIME, Extension, Size) using rules from UCM
            $this->baseValidation($file, $index);

            // Perform image-specific validation if applicable
            if ($this->isImageMimeType($file)) {
                $this->validateImageStructure($file);
            }

            // Validate filename conventions using rules from UCM
            $this->validateFileName($file);

            // Perform PDF-specific validation if applicable
            if ($this->isPdf($file)) {
                $this->validatePdfContent($file);
            }

            $this->logger->info(
                '[HasValidation] File validation completed successfully.',
                ['fileName' => $fileNameForLog]
            );
        } catch (Throwable $e) { // Catch Throwable for broader coverage
            $this->logger->error(
                '[HasValidation] File validation failed.',
                [
                    'fileName' => $fileNameForLog,
                    'error' => $e->getMessage(),
                    'exception_class' => get_class($e),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine()
                ]
            );

            // Re-throw the original exception to be handled by the calling code (e.g., BaseUploadHandler using UEM)
            throw $e;
        }
    }

    /**
     * Perform base file validation (MIME type, extension, size) using rules from UCM.
     *
     * @param UploadedFile $file The file to validate.
     * @param int|string $index Index for testing simulations.
     * @return void
     *
     * @throws Exception If validation fails.
     * @throws \LogicException If required dependencies (logger, configManager, testingConditions, validator) are missing.
     * @sideEffect Reads 'AllowedFileType.collection.allowed_extensions', 'AllowedFileType.collection.max_size' config via UCM.
     * @configReads AllowedFileType.collection.allowed_extensions UCM Key for allowed extensions (Array expected).
     * @configReads AllowedFileType.collection.max_size UCM Key for max file size in bytes (Int expected).
     * @see \Illuminate\Contracts\Validation\Factory For validator creation.
     */
    protected function baseValidation(UploadedFile $file, int|string $index): void
    {
         // Assume dependencies checked in validateFile()
         $logChannel = $this->channel ?? 'upload';
         $fileNameForLog = $file->getClientOriginalName();

         // Resolve necessary Laravel services via helper
         try {
            /** @var ValidationFactory $validatorFactory */
            $validatorFactory = app('validator');
            /** @var \Illuminate\Contracts\Config\Repository $configRepo */
            // Note: We still use UCM instance, but keep app('config') for potential direct access if needed elsewhere
         } catch(Throwable $e) {
              $this->logger->critical('[HasValidation] Failed to resolve core Laravel services (validator/config).', ['error' => $e->getMessage()]);
              throw new \LogicException('Core Laravel services unavailable in HasValidation trait.', 0, $e);
         }


        // --- Get validation rules from UCM ---
        $allowedExtensions = $this->configManager->get('AllowedFileType.collection.allowed_extensions', []); // Correct method: get()
        $maxSizeInBytes = $this->configManager->get('AllowedFileType.collection.max_size', 100 * 1024 * 1024); // Correct method: get()
        // --- End UCM Usage ---

        // Ensure retrieved values have correct types with logging
        if (!is_array($allowedExtensions)) {
            $this->logger->warning('[HasValidation] Invalid format retrieved from UCM for AllowedFileType.collection.allowed_extensions, expected array. Using fallback.', ['value_type' => gettype($allowedExtensions)]);
            $allowedExtensions = []; // Fallback
        }
         if (!is_int($maxSizeInBytes) || $maxSizeInBytes <= 0) {
            $this->logger->warning('[HasValidation] Invalid or non-positive format retrieved from UCM for AllowedFileType.collection.max_size, expected positive integer. Using fallback.', ['value' => $maxSizeInBytes]);
            $maxSizeInBytes = 100 * 1024 * 1024; // Fallback
        }

        $maxSizeInKilobytes = (int)($maxSizeInBytes / 1024); // Validator expects KB

        $this->logger->debug(
            '[HasValidation] Starting base validation (MIME, Ext, Size) using UCM rules.',
             [
                 'fileName' => $fileNameForLog,
                 'sizeBytes' => $file->getSize(),
                 'maxSizeKB' => $maxSizeInKilobytes,
                 'mimeType' => $file->getMimeType(),
                 'allowedExtensionsCount' => count($allowedExtensions),
                 'channel' => $logChannel
             ]
        );

        // Use more specific default messages or rely on UEM mapping later
        $messages = [
             'file.max' => "File exceeds maximum allowed size ({$maxSizeInKilobytes} KB).",
             'file.extensions' => 'File type (extension) is not allowed.',
             'file.required' => 'A file is required.',
             'file.file' => 'The uploaded item is not a valid file.'
         ];

        /** @var IlluminateValidator $validator */
        $validator = $validatorFactory->make(
            ['file' => $file],
            // Validate it's a required file first, then size and extensions
            ['file' => ['required', 'file', 'max:' . $maxSizeInKilobytes, 'extensions:' . implode(',', $allowedExtensions)]],
            $messages
        );

        // Add simulated errors if applicable (uses $this->testingConditions)
        $this->addTestingErrors($validator, $index);

        // Check for validation failures
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('file'); // Get the first specific error
            $this->logger->warning(
                '[HasValidation] Base validation failed.',
                [
                    'fileName' => $fileNameForLog,
                    'errors' => $validator->errors()->toArray(), // Log all validation errors
                    'first_error' => $errorMessage
                ]
            );
            // Throw generic exception; BaseUploadHandler will catch and map to UEM code
            throw new Exception("Base validation failed: {$errorMessage}");
        }

        $this->logger->debug(
            '[HasValidation] Base validation passed.',
            ['fileName' => $fileNameForLog]
        );
    }

    /**
     * Check if the file's MIME type indicates it is an image.
     *
     * @param UploadedFile $file The file to check.
     * @return bool True if the MIME type starts with 'image/', false otherwise.
     */
    protected function isImageMimeType(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        return is_string($mimeType) && str_starts_with($mimeType, 'image/');
    }

    /**
     * Check if the file's MIME type indicates it is a PDF.
     *
     * @param UploadedFile $file The file to check.
     * @return bool True if the MIME type is 'application/pdf', false otherwise.
     */
    protected function isPdf(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'application/pdf';
    }

    /**
     * Add simulated test errors to the Laravel Validator instance based on active testing conditions.
     *
     * @param IlluminateValidator $validator The Laravel Validator instance.
     * @param int|string $index The index of the file being processed.
     * @return void
     * @throws \LogicException If required dependencies (logger, testingConditions) are missing.
     * @sideEffect May add errors to the $validator. Logs simulation info.
     * @see \Ultra\ErrorManager\Services\TestingConditionsManager::isTesting()
     */
    protected function addTestingErrors(IlluminateValidator $validator, int|string $index): void
    {
        // Assume dependencies checked by validateFile()
        if (!isset($this->logger) || !isset($this->testingConditions)) {
             throw new \LogicException('Logger or TestingConditionsManager not available in addTestingErrors.');
        }
        $logChannel = $this->channel ?? 'upload';

        // Apply simulations usually only for the first file (index 0) in test scenarios
        if ($index !== 0 && $index !== '0') {
            return;
        }

        $this->logger->debug(
            '[HasValidation] Checking testing conditions for error simulation.',
            ['index' => $index]
        );

        // Map Testing Condition Key -> [Validator Rule Key, Simulated Message]
        $testConditionsMap = [
            'MAX_FILE_SIZE'          => ['rule' => 'file.max',        'message' => 'Simulated MAX_FILE_SIZE failure.'],
            'INVALID_FILE_EXTENSION' => ['rule' => 'file.extensions', 'message' => 'Simulated INVALID_FILE_EXTENSION failure.'],
            'MIME_TYPE_NOT_ALLOWED'  => ['rule' => 'file.mimes',      'message' => 'Simulated MIME_TYPE_NOT_ALLOWED failure.'],
            // Note: filename, image structure, PDF content simulations should throw exceptions in their respective methods
        ];

        foreach ($testConditionsMap as $conditionKey => $errorDetails) {
            if ($this->testingConditions->isTesting($conditionKey)) {
                $this->logger->info(
                    '[HasValidation] Simulating validation error via TestingConditions.',
                    [
                        'test_condition' => $conditionKey,
                        'rule_key' => $errorDetails['rule'] // Log which rule we are faking
                    ]
                );
                // Add the error directly to the validator's message bag for the 'file' attribute
                $validator->errors()->add('file', $errorDetails['message']);
            }
        }
    }

    /**
     * Validate the structural integrity of an image file using Imagick.
     *
     * @param UploadedFile $file The image file to validate.
     * @return void
     * @throws Exception If Imagick is unavailable or image is invalid.
     * @throws ImagickException If Imagick processing fails.
     * @throws \LogicException If logger dependency is missing.
     * @sideEffect Logs validation steps. Reads file content.
     */
    protected function validateImageStructure(UploadedFile $file): void
    {
         // Assume logger checked by validateFile()
         $logChannel = $this->channel ?? 'upload';
         $fileNameForLog = $file->getClientOriginalName();

         $this->logger->info(
            '[HasValidation] Starting image structure validation.',
            ['fileName' => $fileNameForLog]
         );

        // Check if Imagick extension is loaded
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            $this->logger->error(
                '[HasValidation] Imagick extension not available. Cannot validate image structure.',
                ['fileName' => $fileNameForLog]
            );
            // Let caller handle with UEM code 'IMAGICK_NOT_AVAILABLE'
            throw new Exception("Required Imagick extension is not loaded or class Imagick not found.");
        }

        $filePath = $file->getRealPath();
        // Check if path is valid and file exists
        if ($filePath === false || !file_exists($filePath)) {
           $this->logger->error(
                '[HasValidation] Image file path invalid or file not found for structure validation.',
                ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']
            );
            throw new Exception("Image file path could not be determined or file does not exist.");
        }

        $imagick = null; // Initialize variable
        try {
            $imagick = new \Imagick();
            // Use pingImage for a quick check without loading full image data
            if (!$imagick->pingImage($filePath)) {
                 // pingImage returning false indicates a potential issue
                 throw new ImagickException("Imagick::pingImage returned false, file might be corrupt or unsupported format.");
            }
            // If pingImage passes, structure is likely okay for basic purposes
             $this->logger->info(
                '[HasValidation] Image structure validation passed (pingImage successful).',
                ['fileName' => $fileNameForLog]
            );

        } catch (ImagickException $e) {
            $this->logger->error(
                '[HasValidation] Image structure validation failed (ImagickException).',
                ['fileName' => $fileNameForLog, 'error' => $e->getMessage(), 'code' => $e->getCode()]
            );
             // Let caller handle with UEM code 'INVALID_IMAGE_STRUCTURE'
            throw new Exception("Invalid image structure detected by Imagick: " . $e->getMessage(), 0, $e);
        } catch (Throwable $e) {
             // Catch any other unexpected errors during Imagick usage
             $this->logger->error(
                '[HasValidation] Unexpected error during image structure validation.',
                 ['fileName' => $fileNameForLog, 'exception_class' => get_class($e), 'error' => $e->getMessage()]
            );
             throw new Exception("Unexpected error during Imagick validation: " . $e->getMessage(), 0, $e);
        } finally {
            // Ensure Imagick object is destroyed even if errors occur
            if ($imagick instanceof \Imagick) {
                $imagick->clear();
                $imagick->destroy();
            }
        }
    }

    /**
     * Validate the filename against length and pattern rules retrieved from UCM.
     *
     * @param UploadedFile $file The file whose name needs validation.
     * @return void
     * @throws Exception If the filename is invalid.
     * @throws \LogicException If logger or configManager dependencies are missing.
     * @sideEffect Reads 'file_validation.*' configuration via UCM. Logs validation steps.
     * @configReads file_validation.images.max_name_length UCM Key for max length.
     * @configReads file_validation.min_name_length UCM Key for min length.
     * @configReads file_validation.allowed_name_pattern UCM Key for regex pattern.
     */
    protected function validateFileName(UploadedFile $file): void
    {
        // Assume dependencies checked by validateFile()
        $logChannel = $this->channel ?? 'upload';
        $fileName = $file->getClientOriginalName();

        $this->logger->info(
            '[HasValidation] Starting filename validation using UCM rules.',
            ['fileName' => $fileName]
        );

        // --- Use UCM to get validation rules ---
        $maxLength = $this->configManager->get('file_validation.images.max_name_length', 255);
        $minLength = $this->configManager->get('file_validation.min_name_length', 1);
        $allowedPattern = $this->configManager->get('file_validation.allowed_name_pattern', '/^[\w\-. ]+$/u'); // Added 'u' modifier default for UTF-8
        // --- End UCM Usage ---

        // Validate retrieved config types
        if (!is_int($maxLength) || $maxLength <= 0) {
            $this->logger->warning('[HasValidation] Invalid config value for file_validation.images.max_name_length, using default.', ['retrieved_value' => $maxLength]);
            $maxLength = 255;
        }
        if (!is_int($minLength) || $minLength < 0) { // minLength can be 0 if needed, adjust logic if 1 is absolute minimum
            $this->logger->warning('[HasValidation] Invalid config value for file_validation.min_name_length, using default.', ['retrieved_value' => $minLength]);
            $minLength = 1;
        }
        if (!is_string($allowedPattern) || empty($allowedPattern)) {
            $this->logger->warning('[HasValidation] Invalid config value for file_validation.allowed_name_pattern, using default.', ['retrieved_value' => $allowedPattern]);
            $allowedPattern = '/^[\w\-. ]+$/u';
        }

        // Check Length using mb_strlen for multibyte characters
        $nameLength = mb_strlen($fileName, 'UTF-8');
        if ($nameLength < $minLength || $nameLength > $maxLength) {
             $this->logger->error(
                '[HasValidation] Filename length validation failed.',
                 ['fileName' => $fileName, 'length' => $nameLength, 'minLength' => $minLength, 'maxLength' => $maxLength]
            );
            // Caller handles with UEM code 'INVALID_FILE_NAME'
            throw new Exception("Invalid filename: Length must be between {$minLength} and {$maxLength} characters.");
        }

        // Check Pattern using preg_match
        if (!preg_match($allowedPattern, $fileName)) {
            $this->logger->error(
                '[HasValidation] Filename pattern validation failed.',
                ['fileName' => $fileName, 'pattern' => $allowedPattern]
            );
            // Caller handles with UEM code 'INVALID_FILE_NAME'
            throw new Exception("Invalid filename: Contains disallowed characters or does not match required pattern '{$allowedPattern}'.");
        }

        $this->logger->info(
            '[HasValidation] Filename validation passed.',
            ['fileName' => $fileName]
        );
    }

    /**
     * Validate the basic structure of a PDF file by checking for the '%PDF' magic header.
     *
     * @param UploadedFile $file The PDF file to validate.
     * @return void
     * @throws Exception If the file is not found, cannot be read, or lacks the '%PDF' header.
     * @throws \LogicException If logger dependency is missing.
     * @sideEffect Reads the beginning of the file content. Logs validation steps.
     */
    protected function validatePdfContent(UploadedFile $file): void
    {
        // Assume logger checked by validateFile()
        $logChannel = $this->channel ?? 'upload';
        $fileNameForLog = $file->getClientOriginalName();

        $this->logger->info(
            '[HasValidation] Starting PDF content validation.',
            ['fileName' => $fileNameForLog]
        );

        $filePath = $file->getRealPath();
        // Validate file path
        if ($filePath === false || !file_exists($filePath)) {
           $this->logger->error(
               '[HasValidation] PDF file path invalid or file not found for content validation.',
               ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']
           );
            throw new Exception("PDF file path could not be determined or file does not exist.");
        }

        // Read only the first few bytes (e.g., 10 bytes) to check for '%PDF'
        $fileStart = file_get_contents($filePath, false, null, 0, 10);

        // Check if reading failed
        if ($fileStart === false) {
             $this->logger->error(
                '[HasValidation] Failed to read start of PDF file.',
                ['fileName' => $fileNameForLog, 'path' => $filePath]
            );
             // Let caller handle with UEM code 'FILE_READ_ERROR' or similar
            throw new Exception("Could not read PDF file content for validation.");
        }

        // Check if the file starts with the PDF magic number
        if (!str_starts_with($fileStart, '%PDF')) {
             $this->logger->error(
                '[HasValidation] PDF content validation failed (Missing %PDF header).',
                 ['fileName' => $fileNameForLog, 'startBytesHex' => bin2hex($fileStart)]
            );
             // Caller handles with UEM code 'INVALID_FILE_PDF'
            throw new Exception("Invalid PDF file content: Missing '%PDF' header.");
        }

        $this->logger->info(
            '[HasValidation] PDF content validation passed.',
            ['fileName' => $fileNameForLog]
        );
    }
} // End trait HasValidation