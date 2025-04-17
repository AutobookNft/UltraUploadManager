<?php

/**
 * Trait providing file validation logic for the UltraUploadManager.
 *
 * Offers methods to validate uploaded files against configured rules for
 * extensions, MIME types, size, filename conventions, and basic structural
 * integrity for specific types like images (Imagick) and PDFs.
 * Relies on the consuming class to provide Logger, ErrorManager, and TestingConditions instances.
 *
 * @package     Ultra\UploadManager\Traits
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.1.0 // Refactored for DI (via consuming class), Oracode v1.5.0, Ultra Integration.
 * @since       1.0.0
 *
 * @property-read LoggerInterface $logger Injected Logger instance from consuming class.
 * @property-read ErrorManagerInterface $errorManager Injected ErrorManager instance from consuming class.
 * @property-read TestingConditionsManager $testingConditions Injected TestingConditions instance from consuming class.
 * @property      string $channel Log channel name (expected to be defined in consuming class).
 */

namespace Ultra\UploadManager\Traits;

// Laravel & PHP Dependencies
use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Validation\Factory as ValidationFactory; // For Validator::make alternative
use Illuminate\Validation\Validator as IlluminateValidator; // Type hint for validator instance
use Psr\Log\LoggerInterface; // ULM Interface
use Throwable;
use Exception; // Standard Exception for now
use ImagickException; // Specific Imagick exception

// Ultra Ecosystem Dependencies
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface; // UEM Interface
use Ultra\ErrorManager\Services\TestingConditionsManager; // Testing Service

trait HasValidation
{
    /**
     * Validate an uploaded file against a series of checks.
     *
     * This is the main entry point for validation within the trait. It orchestrates
     * calls to more specific validation methods (base, image structure, filename, PDF content).
     * Throws an Exception if any validation step fails, which should be caught and
     * handled by the calling context (e.g., using UEM).
     *
     * --- Core Logic ---
     * 1. Logs the start of the validation process.
     * 2. Calls `baseValidation` for MIME, extension, and size checks.
     * 3. If the file is an image, calls `validateImageStructure`.
     * 4. Calls `validateFileName` for naming convention checks.
     * 5. If the file is a PDF, calls `validatePdfContent`.
     * 6. Logs successful completion.
     * 7. Catches any Exception during validation, logs the failure, and re-throws it.
     * --- End Core Logic ---
     *
     * @param UploadedFile $file The file instance to validate.
     * @param int|string $index Optional index used for testing simulations.
     * @return void
     *
     * @throws Exception If any validation rule fails. The exception message provides details.
     *
     * @sideEffect Logs validation steps and outcomes via the consuming class's logger.
     * @see self::baseValidation()
     * @see self::isImageMimeType()
     * @see self::validateImageStructure()
     * @see self::validateFileName()
     * @see self::isPdf()
     * @see self::validatePdfContent()
     */
    protected function validateFile(UploadedFile $file, int|string $index = 0): void
    {
        // Ensure logger is available from the consuming class
        if (!isset($this->logger) || !$this->logger instanceof LoggerInterface) {
            throw new \LogicException('Consuming class must provide a LoggerInterface property named $logger.');
        }
        $logChannel = $this->channel ?? 'upload'; // Use channel from consuming class or default

        $fileNameForLog = $file->getClientOriginalName(); // Get filename once for logging

        $this->logger->info(
            '[HasValidation] Starting file validation process.',
            [
                'fileName' => $fileNameForLog,
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'index' => $index,
                'channel' => $logChannel // Log the channel being used
            ]
        );

        try {
            // Perform base validation (MIME, Extension, Size)
            $this->baseValidation($file, $index);

            // Perform image-specific validation if applicable
            if ($this->isImageMimeType($file)) {
                $this->validateImageStructure($file, $index);
            }

            // Validate filename conventions
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
     * Perform base file validation (MIME type, extension, size).
     *
     * Uses Laravel's Validator component (resolved via helper) to check against
     * rules defined in the 'AllowedFileType.collection' configuration.
     * Also integrates with TestingConditionsManager to simulate errors.
     *
     * @param UploadedFile $file The file to validate.
     * @param int|string $index Index for testing simulations.
     * @return void
     *
     * @throws Exception If validation fails (rule violation or simulated error).
     * @throws \LogicException If necessary dependencies (logger, testingConditions) are missing.
     *
     * @sideEffect Reads 'AllowedFileType.collection.allowed_extensions', 'AllowedFileType.collection.max_size' config.
     * @sideEffect Interacts with TestingConditionsManager.
     * @sideEffect Logs validation steps and outcomes.
     *
     * @configReads AllowedFileType.collection.allowed_extensions Defines allowed extensions.
     * @configReads AllowedFileType.collection.max_size Defines max file size in bytes.
     * @see \Illuminate\Contracts\Validation\Factory::make() Creates the validator instance.
     * @see \Ultra\ErrorManager\Services\TestingConditionsManager::isTesting() Checks for simulated errors.
     */
    protected function baseValidation(UploadedFile $file, int|string $index): void
    {
        // Dependency checks (should be present in consuming class)
        if (!isset($this->logger) || !$this->logger instanceof LoggerInterface) {
            throw new \LogicException('Consuming class must provide a LoggerInterface property named $logger.');
        }
        if (!isset($this->testingConditions) || !$this->testingConditions instanceof TestingConditionsManager) {
             throw new \LogicException('Consuming class must provide a TestingConditionsManager property named $testingConditions.');
        }
         $logChannel = $this->channel ?? 'upload';
         $fileNameForLog = $file->getClientOriginalName();

        // Get validation rules from configuration
        // Use app('config') or config() helper - assumes config service is available
        $allowedExtensions = app('config')->get('AllowedFileType.collection.allowed_extensions', []);
        $maxSizeInKilobytes = (int)(app('config')->get('AllowedFileType.collection.max_size', 100 * 1024 * 1024) / 1024); // Validator expects KB

        $this->logger->debug(
            '[HasValidation] Starting base validation (MIME, Ext, Size).',
            [
                'fileName' => $fileNameForLog,
                'sizeBytes' => $file->getSize(),
                'maxSizeKB' => $maxSizeInKilobytes,
                'mimeType' => $file->getMimeType(),
                'allowedExtensions' => $allowedExtensions,
                'channel' => $logChannel
            ]
        );

        // Define custom error messages or rely on Laravel defaults + UEM mapping later
        $messages = [
            'file.mimes' => 'MIME type not allowed.',
            'file.max' => 'File exceeds maximum size.',
            // 'file.extensions' => 'File extension not allowed.', // Laravel default might be better
        ];

        // Resolve Validator Factory
        /** @var ValidationFactory $validatorFactory */
        $validatorFactory = app('validator'); // Resolve via app helper

        // Perform validation using Laravel's validator
        /** @var IlluminateValidator $validator */
        $validator = $validatorFactory->make(
            ['file' => $file],
            // Use 'extensions' rule which is generally more reliable than 'mimes' alone
            // Size rule expects kilobytes
            ['file' => ['required', 'file', 'max:' . $maxSizeInKilobytes, 'extensions:' . implode(',', $allowedExtensions)]],
            $messages
        );

        // Add any simulated test errors (uses $this->testingConditions)
        $this->addTestingErrors($validator, $index);

        // If validation fails, log and throw exception
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('file'); // Get the first error message for 'file'

            $this->logger->warning(
                '[HasValidation] Base validation failed.',
                [
                    'fileName' => $fileNameForLog,
                    'errors' => $validator->errors()->toArray(), // Log all errors
                    'failedRule' => $errorMessage // Log first message
                ]
            );

            // Throw a generic exception - the caller (BaseUploadHandler) will map this
            // to the specific UEM code 'INVALID_FILE_VALIDATION' and add context.
            // We include the validation message for better debugging in the handler.
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
        // Ensure getMimeType() returned a string before checking
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
     * This method checks various testing conditions (e.g., 'MAX_FILE_SIZE') using the injected
     * TestingConditionsManager and adds corresponding errors to the validator if a condition is active.
     * This allows simulating validation failures during tests without needing invalid actual files.
     *
     * @param IlluminateValidator $validator The Laravel Validator instance to modify.
     * @param int|string $index The index of the file being processed (used to target simulations).
     * @return void
     *
     * @sideEffect May add error messages to the provided $validator instance.
     * @sideEffect Logs messages when simulating errors via the consuming class's logger.
     * @see \Ultra\ErrorManager\Services\TestingConditionsManager::isTesting()
     */
    protected function addTestingErrors(IlluminateValidator $validator, int|string $index): void
    {
        // Ensure logger and testingConditions are available
        if (!isset($this->logger) || !isset($this->testingConditions)) return;
         $logChannel = $this->channel ?? 'upload';

        // Only apply simulations for the first file in a batch (index 0) typically
        if ($index !== 0 && $index !== '0') {
            return;
        }

        $this->logger->debug(
            '[HasValidation] Checking testing conditions for error simulation.',
            ['index' => $index]
        );

        // Map testing condition keys to validator rule keys/messages
        $testConditionsMap = [
            'MAX_FILE_SIZE' => ['rule' => 'file.max', 'message' => 'Simulated MAX_FILE_SIZE failure.'],
            'INVALID_FILE_EXTENSION' => ['rule' => 'file.extensions', 'message' => 'Simulated INVALID_FILE_EXTENSION failure.'],
            // 'INVALID_FILE_NAME' => ['rule' => 'file.name', 'message' => 'Simulated INVALID_FILE_NAME failure.'], // Note: file.name isn't a standard validator rule key
            'MIME_TYPE_NOT_ALLOWED' => ['rule' => 'file.mimes', 'message' => 'Simulated MIME_TYPE_NOT_ALLOWED failure.'],
             // These need to be simulated by throwing exceptions in their respective methods
            // 'INVALID_IMAGE_STRUCTURE' => ['rule' => 'file.structure', 'message' => 'Simulated INVALID_IMAGE_STRUCTURE failure.'],
            // 'IMAGICK_NOT_AVAILABLE' => ['rule' => 'imagic_failed', 'message' => 'Simulated IMAGICK_NOT_AVAILABLE failure.'],
            // 'INVALID_FILE_PDF' => ['rule' => 'file.pdf', 'message' => 'Simulated INVALID_FILE_PDF failure.'],
        ];

        foreach ($testConditionsMap as $conditionKey => $errorDetails) {
            if ($this->testingConditions->isTesting($conditionKey)) {
                $this->logger->info(
                    '[HasValidation] Simulating validation error via TestingConditions.',
                    [
                        'test_condition' => $conditionKey,
                        'rule_key' => $errorDetails['rule']
                    ]
                );
                // Add the error directly to the validator's message bag
                $validator->errors()->add('file', $errorDetails['message']);
                // No need to use $validator->after() here, add directly
            }
        }
    }

    /**
     * Validate the structural integrity of an image file using Imagick.
     *
     * Checks if the Imagick extension is loaded and attempts a lightweight
     * validation (`pingImage`) on the file.
     *
     * @param UploadedFile $file The image file to validate.
     * @param int|string $index Optional index for testing simulations (not used here currently).
     * @return void
     *
     * @throws Exception If Imagick is not available or if the image structure is invalid.
     * @throws \ImagickException If `pingImage` fails.
     * @throws \LogicException If logger dependency is missing.
     *
     * @sideEffect Logs validation steps and outcomes. May interact with filesystem to read the file.
     * @see https://www.php.net/manual/en/class.imagick.php
     */
    protected function validateImageStructure(UploadedFile $file, int|string $index = 0): void
    {
        if (!isset($this->logger)) throw new \LogicException('Logger not available in HasValidation trait.');
        $logChannel = $this->channel ?? 'upload';
        $fileNameForLog = $file->getClientOriginalName();

        $this->logger->info(
            '[HasValidation] Starting image structure validation.',
            ['fileName' => $fileNameForLog]
        );

        // Check if Imagick extension is loaded
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            $this->logger->error(
                '[HasValidation] Imagick extension not available.',
                ['fileName' => $fileNameForLog]
            );
            // Let the caller handle this with UEM code 'IMAGICK_NOT_AVAILABLE'
            throw new Exception("Required Imagick extension is not loaded or class Imagick not found.");
        }

        $filePath = $file->getRealPath();
        if ($filePath === false || !file_exists($filePath)) {
            $this->logger->error(
                '[HasValidation] Image file not found for structure validation.',
                ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']
            );
             // Let the caller handle this with UEM code 'FILE_NOT_FOUND' or similar
            throw new Exception("Image file path could not be determined or file does not exist.");
        }

        try {
            // Use Imagick to perform a basic validity check
            $image = new \Imagick();
            // pingImage is faster as it doesn't load the full image data
            if (!$image->pingImage($filePath)) {
                 // pingImage returning false usually indicates an issue
                 throw new ImagickException("Imagick::pingImage returned false, indicating potential issue.");
            }
             // Optional: A more thorough check, but slower
             // $image->readImage($filePath); // This would throw if format is unsupported/corrupt

            $this->logger->info(
                '[HasValidation] Image structure validation passed.',
                ['fileName' => $fileNameForLog]
            );
            // Destroy Imagick object explicitly (good practice)
            $image->clear();
            $image->destroy();

        } catch (ImagickException $e) {
            $this->logger->error(
                '[HasValidation] Image structure validation failed (ImagickException).',
                [
                    'fileName' => $fileNameForLog,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]
            );
             // Let the caller handle this with UEM code 'INVALID_IMAGE_STRUCTURE'
            throw new Exception("Invalid image structure detected by Imagick: " . $e->getMessage(), 0, $e);
        } catch (Throwable $e) {
             // Catch any other unexpected errors during Imagick usage
             $this->logger->error(
                '[HasValidation] Unexpected error during image structure validation.',
                [
                    'fileName' => $fileNameForLog,
                    'exception_class' => get_class($e),
                    'error' => $e->getMessage(),
                ]
            );
             throw new Exception("Unexpected error during Imagick validation: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate the filename against configured length and pattern rules.
     *
     * @param UploadedFile $file The file whose name needs validation.
     * @return void
     *
     * @throws Exception If the filename length is invalid or contains disallowed characters.
     * @throws \LogicException If logger dependency is missing.
     *
     * @sideEffect Reads 'file_validation' configuration (custom config assumed).
     * @sideEffect Logs validation steps and outcomes.
     * @configReads file_validation.images.max_name_length Assumed config key for max length.
     * @configReads file_validation.min_name_length Assumed config key for min length.
     * @configReads file_validation.allowed_name_pattern Assumed config key for regex pattern.
     */
    protected function validateFileName(UploadedFile $file): void
    {
        if (!isset($this->logger)) throw new \LogicException('Logger not available in HasValidation trait.');
        $logChannel = $this->channel ?? 'upload';
        $fileName = $file->getClientOriginalName();

        $this->logger->info(
            '[HasValidation] Starting filename validation.',
            ['fileName' => $fileName]
        );

        // Retrieve validation rules from config (assuming a 'file_validation' config file exists)
        // Provide sensible defaults if config is missing
        $maxLength = config('file_validation.images.max_name_length', 255);
        $minLength = config('file_validation.min_name_length', 1);
        // Default pattern allows alphanumeric, underscore, hyphen, period, space
        $allowedPattern = config('file_validation.allowed_name_pattern', '/^[\w\-. ]+$/u'); // Added 'u' modifier for UTF-8

        // Check Length
        $nameLength = mb_strlen($fileName, 'UTF-8'); // Use mb_strlen for UTF-8 support
        if ($nameLength < $minLength || $nameLength > $maxLength) {
            $this->logger->error(
                '[HasValidation] Filename length validation failed.',
                [
                    'fileName' => $fileName,
                    'length' => $nameLength,
                    'minLength' => $minLength,
                    'maxLength' => $maxLength
                ]
            );
            // Let caller handle with UEM code 'INVALID_FILE_NAME'
            throw new Exception("Invalid filename: Length must be between {$minLength} and {$maxLength} characters.");
        }

        // Check Pattern
        if (!preg_match($allowedPattern, $fileName)) {
            $this->logger->error(
                '[HasValidation] Filename pattern validation failed.',
                [
                    'fileName' => $fileName,
                    'pattern' => $allowedPattern
                ]
            );
             // Let caller handle with UEM code 'INVALID_FILE_NAME'
            throw new Exception("Invalid filename: Contains disallowed characters matching pattern '{$allowedPattern}'.");
        }

        $this->logger->info(
            '[HasValidation] Filename validation passed.',
            ['fileName' => $fileName]
        );
    }

    /**
     * Validate the basic structure of a PDF file.
     *
     * Checks if the file exists and if its initial content contains the standard '%PDF' magic bytes.
     *
     * @param UploadedFile $file The PDF file to validate.
     * @return void
     *
     * @throws Exception If the file is not found or does not start with '%PDF'.
     * @throws \LogicException If logger dependency is missing.
     *
     * @sideEffect Reads the beginning of the file content from the filesystem.
     * @sideEffect Logs validation steps and outcomes.
     */
    protected function validatePdfContent(UploadedFile $file): void
    {
        if (!isset($this->logger)) throw new \LogicException('Logger not available in HasValidation trait.');
        $logChannel = $this->channel ?? 'upload';
        $fileNameForLog = $file->getClientOriginalName();

        $this->logger->info(
            '[HasValidation] Starting PDF content validation.',
            ['fileName' => $fileNameForLog]
        );

        $filePath = $file->getRealPath();
        if ($filePath === false || !file_exists($filePath)) {
            $this->logger->error(
                '[HasValidation] PDF file not found for content validation.',
                ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']
            );
             // Let caller handle with UEM code 'FILE_NOT_FOUND' or similar
            throw new Exception("PDF file path could not be determined or file does not exist.");
        }

        // Read only the first few bytes to check for the magic number
        $fileStart = file_get_contents($filePath, false, null, 0, 10); // Read first 10 bytes

        if ($fileStart === false) {
             $this->logger->error(
                '[HasValidation] Failed to read start of PDF file.',
                ['fileName' => $fileNameForLog, 'path' => $filePath]
            );
             // Let caller handle with UEM code 'FILE_READ_ERROR' or similar
            throw new Exception("Could not read PDF file content.");
        }

        // Check if the file starts with '%PDF'
        if (!str_starts_with($fileStart, '%PDF')) {
            $this->logger->error(
                '[HasValidation] PDF content validation failed (Missing %PDF header).',
                ['fileName' => $fileNameForLog, 'startBytes' => bin2hex($fileStart)] // Log hex for non-printables
            );
             // Let caller handle with UEM code 'INVALID_FILE_PDF'
            throw new Exception("Invalid PDF file content: Missing '%PDF' header.");
        }

        $this->logger->info(
            '[HasValidation] PDF content validation passed.',
            ['fileName' => $fileNameForLog]
        );
    }
}