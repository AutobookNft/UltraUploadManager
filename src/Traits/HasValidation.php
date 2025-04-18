<?php

/**
 * 📜 Oracode Trait: HasValidation
 * Provides file validation logic for UltraUploadManager handlers.
 *
 * @package     Ultra\UploadManager\Traits
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.3.0 // Uses UCM getOrFail(), Oracode v1.5.0 docs, refined logic.
 * @since       1.0.0
 *
 * @property-read LoggerInterface $logger Injected Logger instance (ULM). Consuming class MUST provide this.
 * @property-read ErrorManagerInterface $errorManager Injected ErrorManager instance (UEM). Consuming class MUST provide this.
 * @property-read TestingConditionsManager $testingConditions Injected TestingConditions instance. Consuming class MUST provide this.
 * @property-read UltraConfigManager $configManager Injected UCM instance. Consuming class MUST provide this.
 * @property      string $channel Log channel name (expected in consuming class). Defaults to 'upload'.
 */

namespace Ultra\UploadManager\Traits;

// Laravel & PHP Dependencies
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Validator as IlluminateValidator;
use Psr\Log\LoggerInterface;
use Throwable;
use Exception; // Used for validation rule failures (can be replaced by custom exception)
use ImagickException;
use LogicException; // Used for dependency/setup errors

// Ultra Ecosystem Dependencies
use Ultra\ErrorManager\Interfaces\ErrorManagerInterface;
use Ultra\ErrorManager\Services\TestingConditionsManager;
use Ultra\UltraConfigManager\UltraConfigManager;
use Ultra\UltraConfigManager\Exceptions\ConfigNotFoundException; // Specific UCM exception

trait HasValidation
{
    /**
     * 🎯 Validate an uploaded file against configured rules.
     * Main entry point for file validation within a handler using this trait.
     * Orchestrates calls to specific validation methods (`baseValidation`, `validateImageStructure`, etc.).
     * Retrieves essential validation rules (extensions, size) via UCM's `getOrFail()` and
     * optional rules (filename patterns) via `get()`.
     *
     * --- Core Logic ---
     * 1. Perform dependency checks (logger, UEM, UCM, testing). Throw LogicException if missing.
     * 2. Log validation start.
     * 3. Execute specific validation steps in a try-catch block:
     *    - `baseValidation()`: Checks MIME/Ext/Size using required UCM config. Throws LogicException if config missing, Exception if rules fail.
     *    - `validateImageStructure()`: Checks image integrity if applicable. Throws Exception on failure.
     *    - `validateFileName()`: Checks filename rules using optional UCM config. Throws Exception on failure.
     *    - `validatePdfContent()`: Checks basic PDF structure if applicable. Throws Exception on failure.
     * 4. Log success if all steps pass.
     * 5. Catch any Throwable during validation, log the error, and re-throw it to be handled by the caller (e.g., `BaseUploadHandler` mapping it to a UEM code).
     * --- End Core Logic ---
     *
     * @param UploadedFile $file The file instance to validate.
     * @param int|string $index Optional index used for testing simulations (typically 0).
     * @return void Returns nothing; throws exception on validation failure.
     *
     * @throws Exception If any validation rule fails (e.g., size, extension, pattern, structure). This should be caught by the caller and mapped to a UEM code like `INVALID_FILE_VALIDATION`.
     * @throws LogicException If required dependencies (`logger`, `errorManager`, `configManager`, `testingConditions`) are not available in the consuming class, or if essential configuration keys are missing from UCM. This should be caught by the caller and mapped to a UEM code like `UUM_DEPENDENCY_MISSING` or `UCM_REQUIRED_KEY_MISSING`.
     * @throws ConfigNotFoundException If `getOrFail()` fails to find a required key in UCM (caught within `baseValidation` or `validateFileName` and re-thrown as LogicException).
     *
     * @sideEffect Reads configuration via UCM (`AllowedFileType.collection.*`, `file_validation.*`).
     * @sideEffect Logs validation steps and errors via injected LoggerInterface (ULM).
     * @sideEffect Interacts with TestingConditionsManager for error simulation.
     * @see self::baseValidation()
     * @see self::validateImageStructure()
     * @see self::validateFileName()
     * @see self::validatePdfContent()
     * @see \Ultra\UltraConfigManager\UltraConfigManager::getOrFail() For required configuration retrieval.
     * @see \Ultra\UltraConfigManager\UltraConfigManager::get() For optional configuration retrieval.
     */
    protected function validateFile(UploadedFile $file, int|string $index = 0): void
    {
        // --- Dependency Checks ---
        if (!isset($this->logger) || !$this->logger instanceof LoggerInterface) {
            throw new LogicException('Consuming class must provide LoggerInterface $logger.');
        }
         if (!isset($this->errorManager) || !$this->errorManager instanceof ErrorManagerInterface) {
            $this->logger->error('[HasValidation][FATAL] Missing dependency: ErrorManagerInterface $errorManager');
            throw new LogicException('Consuming class must provide ErrorManagerInterface $errorManager.');
        }
        if (!isset($this->configManager) || !$this->configManager instanceof UltraConfigManager) {
             $this->logger->error('[HasValidation][FATAL] Missing dependency: UltraConfigManager $configManager');
            throw new LogicException('Consuming class must provide UltraConfigManager $configManager.');
        }
        if (!isset($this->testingConditions) || !$this->testingConditions instanceof TestingConditionsManager) {
             $this->logger->error('[HasValidation][FATAL] Missing dependency: TestingConditionsManager $testingConditions');
             throw new LogicException('Consuming class must provide TestingConditionsManager $testingConditions.');
        }
        // --- End Dependency Checks ---

        $logChannel = $this->channel ?? 'upload';
        $fileNameForLog = $file->getClientOriginalName();

        $this->logger->info(
            '[HasValidation] Starting file validation process (using UCM).',
            [
                'fileName' => $fileNameForLog,
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'index' => $index,
                'channel' => $logChannel
            ]
        );

        try {
            // 1. Base Validation (uses getOrFail for critical config)
            $this->baseValidation($file, $index);

            // 2. Image Structure Validation (if applicable)
            if ($this->isImageMimeType($file)) {
                $this->validateImageStructure($file);
            }

            // 3. Filename Validation (uses get with defaults)
            $this->validateFileName($file);

            // 4. PDF Content Validation (if applicable)
            if ($this->isPdf($file)) {
                $this->validatePdfContent($file);
            }

            $this->logger->info(
                '[HasValidation] File validation completed successfully.',
                ['fileName' => $fileNameForLog]
            );
        } catch (Throwable $e) { // Catch any exception from validation steps
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
            // Re-throw the caught exception (LogicException or Exception)
            // The caller (BaseUploadHandler) will catch this and map to the appropriate UEM code.
            throw $e;
        }
    }

    /**
     * 🎯 Perform base file validation (MIME type, extension, size) using required rules from UCM.
     * Uses `getOrFail` for critical configuration keys (extensions, max_size).
     *
     * @param UploadedFile $file The file to validate.
     * @param int|string $index Index for testing simulations.
     * @return void
     *
     * @throws Exception If Laravel's validation rules fail (e.g., size exceeded, invalid extension/MIME).
     * @throws LogicException If essential configuration keys (`AllowedFileType.collection.allowed_extensions`, `AllowedFileType.collection.max_size`) are missing from UCM or if core Laravel services are unavailable.
     * @throws ConfigNotFoundException If `getOrFail` cannot find the required keys (caught and re-thrown as LogicException).
     *
     * @sideEffect Reads configuration via UCM. Logs steps and errors. Interacts with TestingConditions.
     * @configReads Required: AllowedFileType.collection.allowed_extensions (Array)
     * @configReads Required: AllowedFileType.collection.max_size (Int)
     */
    protected function baseValidation(UploadedFile $file, int|string $index): void
    {
         // Dependencies assumed checked by validateFile()
         $logChannel = $this->channel ?? 'upload';
         $fileNameForLog = $file->getClientOriginalName();

         // Resolve Laravel validator factory
         try {
            /** @var ValidationFactory $validatorFactory */
            $validatorFactory = app('validator');
         } catch(Throwable $e) {
              $this->logger->critical('[HasValidation] Failed to resolve core Laravel validator service.', ['error' => $e->getMessage()]);
              throw new LogicException('Core Laravel validator service unavailable in HasValidation trait.', 0, $e);
         }

         $allowedExtensions = [];
         $maxSizeInBytes = 0;

         // --- Get REQUIRED validation rules from UCM ---
         try {
             // Use getOrFail for essential configurations
             $allowedExtensions = $this->configManager->getOrFail('AllowedFileType.collection.allowed_extensions');
             $maxSizeInBytes = $this->configManager->getOrFail('AllowedFileType.collection.max_size');

             // Validate the *type* of the retrieved config values
             if (!is_array($allowedExtensions)) {
                 throw new LogicException("Invalid configuration format: 'AllowedFileType.collection.allowed_extensions' must be an array.");
             }
             if (!is_int($maxSizeInBytes) || $maxSizeInBytes <= 0) {
                 throw new LogicException("Invalid configuration format: 'AllowedFileType.collection.max_size' must be a positive integer.");
             }
              $this->logger->debug('[HasValidation] Required base validation config retrieved from UCM.', [
                  'extensions_count' => count($allowedExtensions),
                  'max_size_bytes' => $maxSizeInBytes
              ]);

         } catch (ConfigNotFoundException $e) {
             // Catch specific UCM exception for missing REQUIRED keys
             $this->logger->critical('[HasValidation] Essential configuration key missing from UCM for base validation.', ['error' => $e->getMessage()]);
             // Re-throw as LogicException to signal a setup/configuration error
             throw new LogicException("Base validation cannot proceed due to missing essential UCM configuration: " . $e->getMessage(), 0, $e);
         } catch (LogicException $le) { // Catch type validation errors from above
              $this->logger->critical('[HasValidation] Invalid format for essential UCM configuration.', ['error' => $le->getMessage()]);
              throw $le; // Re-throw the LogicException
         }
         // --- End UCM Usage ---

        $maxSizeInKilobytes = (int)($maxSizeInBytes / 1024); // Validator expects KB

        $this->logger->debug(
            '[HasValidation] Starting base validation check (MIME, Ext, Size).',
             [
                 'fileName' => $fileNameForLog,
                 'sizeBytes' => $file->getSize(),
                 'maxSizeKB' => $maxSizeInKilobytes,
                 'mimeType' => $file->getMimeType(),
                 'allowedExtensions' => $allowedExtensions, // Log retrieved extensions
                 'channel' => $logChannel
             ]
        );

        // Laravel validation messages
        $messages = [
             'file.max' => "File exceeds maximum allowed size ({$maxSizeInKilobytes} KB).", // TODO: Use UTM key
             'file.extensions' => 'File type (extension) is not allowed.', // TODO: Use UTM key
             'file.required' => 'A file is required.', // TODO: Use UTM key
             'file.file' => 'The uploaded item is not a valid file.' // TODO: Use UTM key
         ];

        // Create validator instance
        /** @var IlluminateValidator $validator */
        $validator = $validatorFactory->make(
            ['file' => $file],
            ['file' => ['required', 'file', 'max:' . $maxSizeInKilobytes, 'extensions:' . implode(',', $allowedExtensions)]],
            $messages
        );

        // Add simulated errors if testing
        $this->addTestingErrors($validator, $index);

        // --- Perform Validation ---
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('file');
            $this->logger->warning(
                '[HasValidation] Base validation failed.',
                [
                    'fileName' => $fileNameForLog,
                    'errors' => $validator->errors()->toArray(),
                    'first_error' => $errorMessage
                ]
            );
            // Throw standard Exception for validation *rule* failure
            // The caller (BaseUploadHandler) will catch this and map to INVALID_FILE_VALIDATION UEM code.
            throw new Exception("Base validation failed: {$errorMessage}");
        }

        $this->logger->debug(
            '[HasValidation] Base validation passed.',
            ['fileName' => $fileNameForLog]
        );
    }

    /**
     * 🎯 Check if the file's MIME type indicates it is an image.
     * Helper method for conditional validation.
     *
     * @param UploadedFile $file The file to check.
     * @return bool True if the MIME type starts with 'image/', false otherwise.
     */
    protected function isImageMimeType(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();
        // Ensure mimeType is a string before checking
        return is_string($mimeType) && str_starts_with(strtolower($mimeType), 'image/');
    }

    /**
     * 🎯 Check if the file's MIME type indicates it is a PDF.
     * Helper method for conditional validation.
     *
     * @param UploadedFile $file The file to check.
     * @return bool True if the MIME type is 'application/pdf', false otherwise.
     */
    protected function isPdf(UploadedFile $file): bool
    {
        // Ensure mimeType is a string before comparing
        return is_string($file->getMimeType()) && strtolower($file->getMimeType()) === 'application/pdf';
    }

    /**
     * 🎯 Add simulated test errors to the Laravel Validator instance based on active testing conditions.
     * Used for testing specific validation failure paths.
     *
     * @internal For testing purposes only.
     * @param IlluminateValidator $validator The Laravel Validator instance.
     * @param int|string $index The index of the file being processed (simulation usually applies only to index 0).
     * @return void
     * @throws LogicException If required dependencies (logger, testingConditions) are missing.
     * @sideEffect May add errors to the `$validator`'s message bag. Logs simulation info.
     * @see \Ultra\ErrorManager\Services\TestingConditionsManager::isTesting()
     */
    protected function addTestingErrors(IlluminateValidator $validator, int|string $index): void
    {
        // Dependencies assumed checked by validateFile()
        if (!isset($this->logger) || !isset($this->testingConditions)) {
             throw new LogicException('Logger or TestingConditionsManager not available in addTestingErrors.');
        }
        $logChannel = $this->channel ?? 'upload';

        // Only apply simulations to the first file typically
        if ($index !== 0 && $index !== '0') {
            return;
        }

        $this->logger->debug('[HasValidation] Checking testing conditions for error simulation.', ['index' => $index]);

        $testConditionsMap = [
            'MAX_FILE_SIZE'          => ['rule' => 'file.max',        'message' => 'Simulated MAX_FILE_SIZE failure.'],
            'INVALID_FILE_EXTENSION' => ['rule' => 'file.extensions', 'message' => 'Simulated INVALID_FILE_EXTENSION failure.'],
            'MIME_TYPE_NOT_ALLOWED'  => ['rule' => 'file.mimes',      'message' => 'Simulated MIME_TYPE_NOT_ALLOWED failure.'],
            // Other simulations (filename, image, pdf) throw exceptions in their methods
        ];

        foreach ($testConditionsMap as $conditionKey => $errorDetails) {
            if ($this->testingConditions->isTesting($conditionKey)) {
                $this->logger->info('[HasValidation] Simulating validation error via TestingConditions.', [
                        'test_condition' => $conditionKey,
                        'rule_key' => $errorDetails['rule']
                    ]
                );
                // Add error to the validator for the 'file' attribute
                $validator->errors()->add('file', $errorDetails['message']);
            }
        }
    }

    /**
     * 🎯 Validate the structural integrity of an image file using Imagick.
     * Checks if the Imagick extension is loaded and attempts a quick `pingImage`.
     *
     * @param UploadedFile $file The image file to validate.
     * @return void
     * @throws Exception If Imagick extension is unavailable, file is unreadable, or `pingImage` fails, indicating potential corruption or unsupported format.
     * @throws LogicException If logger dependency is missing.
     * @sideEffect Logs validation steps and errors. Reads file path. May instantiate Imagick object.
     */
    protected function validateImageStructure(UploadedFile $file): void
    {
         // Dependencies assumed checked by validateFile()
         $logChannel = $this->channel ?? 'upload';
         $fileNameForLog = $file->getClientOriginalName();

         $this->logger->info('[HasValidation] Starting image structure validation.', ['fileName' => $fileNameForLog]);

        // 1. Check Imagick availability
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            $this->logger->error('[HasValidation] Imagick extension not available. Cannot validate image structure.', ['fileName' => $fileNameForLog]);
            // Let caller map to UEM code 'IMAGICK_NOT_AVAILABLE'
            throw new Exception("Required Imagick extension is not loaded or class Imagick not found.");
        }

        // 2. Get file path and check existence
        $filePath = $file->getRealPath();
        if ($filePath === false || !file_exists($filePath)) {
           $this->logger->error('[HasValidation] Image file path invalid or file not found for structure validation.', ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']);
            throw new Exception("Image file path could not be determined or file does not exist."); // Let caller map to UEM code
        }

        $imagick = null;
        try {
            // 3. Attempt basic image ping
            $imagick = new \Imagick();
            if (!$imagick->pingImage($filePath)) {
                 // pingImage returning false is a strong indicator of an issue
                 throw new ImagickException("Imagick::pingImage returned false, file might be corrupt or an unsupported format.");
            }
             $this->logger->info('[HasValidation] Image structure validation passed (pingImage successful).', ['fileName' => $fileNameForLog]);

        } catch (ImagickException $e) { // Catch specific Imagick errors
            $this->logger->error('[HasValidation] Image structure validation failed (ImagickException).', ['fileName' => $fileNameForLog, 'error' => $e->getMessage(), 'code' => $e->getCode()]);
            // Let caller map to UEM code 'INVALID_IMAGE_STRUCTURE'
            throw new Exception("Invalid image structure detected by Imagick: " . $e->getMessage(), 0, $e);
        } catch (Throwable $e) { // Catch other unexpected errors
             $this->logger->error('[HasValidation] Unexpected error during image structure validation.', ['fileName' => $fileNameForLog, 'exception_class' => get_class($e), 'error' => $e->getMessage()]);
             throw new Exception("Unexpected error during Imagick validation: " . $e->getMessage(), 0, $e); // Let caller map to UEM code
        } finally {
            // 4. Clean up Imagick resource
            if ($imagick instanceof \Imagick) {
                $imagick->clear();
                $imagick->destroy();
            }
        }
    }

    /**
     * 🎯 Validate the filename against length and pattern rules retrieved from UCM (using defaults).
     *
     * @param UploadedFile $file The file whose name needs validation.
     * @return void
     * @throws Exception If the filename fails length or pattern validation.
     * @throws LogicException If required dependencies (logger, configManager) are missing.
     * @throws ConfigNotFoundException If `getOrFail` is used for a required key and it's missing (currently uses `get`).
     * @sideEffect Reads optional 'file_validation.*' configuration via UCM `get()`. Logs steps/errors.
     * @configReads Optional: file_validation.images.max_name_length (Int, default 255)
     * @configReads Optional: file_validation.min_name_length (Int, default 1)
     * @configReads Optional: file_validation.allowed_name_pattern (String regex, default '/^[\w\-. ]+$/u')
     */
    protected function validateFileName(UploadedFile $file): void
    {
        // Dependencies assumed checked by validateFile()
        $logChannel = $this->channel ?? 'upload';
        $fileName = $file->getClientOriginalName();

        $this->logger->info('[HasValidation] Starting filename validation using UCM rules (with defaults).', ['fileName' => $fileName]);

        // --- Use UCM's get() with defaults for these rules ---
        $maxLength = $this->configManager->get('file_validation.images.max_name_length', 255);
        $minLength = $this->configManager->get('file_validation.min_name_length', 1);
        $allowedPattern = $this->configManager->get('file_validation.allowed_name_pattern', '/^[\w\-. ]+$/u');
        // --- End UCM Usage ---

        // Validate retrieved config types (log warnings if invalid, use defaults)
        if (!is_int($maxLength) || $maxLength <= 0) {
            $this->logger->warning('[HasValidation] Invalid config value for file_validation.images.max_name_length, using default.', ['retrieved_value' => $maxLength]);
            $maxLength = 255;
        }
        if (!is_int($minLength) || $minLength < 0) {
            $this->logger->warning('[HasValidation] Invalid config value for file_validation.min_name_length, using default.', ['retrieved_value' => $minLength]);
            $minLength = 1;
        }
        if (!is_string($allowedPattern) || empty($allowedPattern)) {
            $this->logger->warning('[HasValidation] Invalid config value for file_validation.allowed_name_pattern, using default.', ['retrieved_value' => $allowedPattern]);
            $allowedPattern = '/^[\w\-. ]+$/u';
        }
         $this->logger->debug('[HasValidation] Filename validation rules applied.', ['minLength' => $minLength, 'maxLength' => $maxLength, 'pattern' => $allowedPattern]);

        // 1. Check Length (using mb_strlen for UTF-8 safety)
        $nameLength = mb_strlen($fileName, 'UTF-8');
        if ($nameLength < $minLength || $nameLength > $maxLength) {
             $this->logger->error('[HasValidation] Filename length validation failed.', ['fileName' => $fileName, 'length' => $nameLength, 'minLength' => $minLength, 'maxLength' => $maxLength]);
            // Let caller map to UEM code 'INVALID_FILE_NAME'
            throw new Exception("Invalid filename: Length must be between {$minLength} and {$maxLength} characters.");
        }

        // 2. Check Pattern
        if (!preg_match($allowedPattern, $fileName)) {
            $this->logger->error('[HasValidation] Filename pattern validation failed.', ['fileName' => $fileName, 'pattern' => $allowedPattern]);
            // Let caller map to UEM code 'INVALID_FILE_NAME'
            throw new Exception("Invalid filename: Contains disallowed characters or does not match required pattern '{$allowedPattern}'.");
        }

        $this->logger->info('[HasValidation] Filename validation passed.', ['fileName' => $fileName]);
    }

    /**
     * 🎯 Validate the basic structure of a PDF file by checking for the '%PDF' magic header.
     *
     * @param UploadedFile $file The PDF file to validate.
     * @return void
     * @throws Exception If the file is not found, cannot be read, or lacks the '%PDF' header.
     * @throws LogicException If logger dependency is missing.
     * @sideEffect Reads the beginning of the file content. Logs validation steps.
     */
    protected function validatePdfContent(UploadedFile $file): void
    {
        // Dependencies assumed checked by validateFile()
        $logChannel = $this->channel ?? 'upload';
        $fileNameForLog = $file->getClientOriginalName();

        $this->logger->info('[HasValidation] Starting PDF content validation.', ['fileName' => $fileNameForLog]);

        // 1. Get file path and check existence
        $filePath = $file->getRealPath();
        if ($filePath === false || !file_exists($filePath)) {
           $this->logger->error('[HasValidation] PDF file path invalid or file not found for content validation.', ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']);
            throw new Exception("PDF file path could not be determined or file does not exist."); // Let caller map to UEM code
        }

        // 2. Read the first few bytes
        $fileStart = file_get_contents($filePath, false, null, 0, 10); // Read 10 bytes

        // 3. Check read result
        if ($fileStart === false) {
             $this->logger->error('[HasValidation] Failed to read start of PDF file.', ['fileName' => $fileNameForLog, 'path' => $filePath]);
             // Let caller map to UEM code 'FILE_READ_ERROR'
            throw new Exception("Could not read PDF file content for validation.");
        }

        // 4. Check for '%PDF' header
        if (!str_starts_with($fileStart, '%PDF')) {
             $this->logger->error('[HasValidation] PDF content validation failed (Missing %PDF header).', ['fileName' => $fileNameForLog, 'startBytesHex' => bin2hex($fileStart)]);
             // Let caller map to UEM code 'INVALID_FILE_PDF'
            throw new Exception("Invalid PDF file content: Missing '%PDF' header.");
        }

        $this->logger->info('[HasValidation] PDF content validation passed.', ['fileName' => $fileNameForLog]);
    }

} // End trait HasValidation