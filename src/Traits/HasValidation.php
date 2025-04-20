<?php

/**
 * 📜 Oracode Trait: HasValidation (Refactored for Laravel Standard)
 * Provides file validation logic using Laravel's standard config() and Log facade.
 *
 * @package     Ultra\UploadManager\Traits
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 * @version     1.4.0 // Refactored to use Laravel config() and Log, removing UCM/ULM/UEM dependencies.
 * @since       1.0.0
 */

namespace Ultra\UploadManager\Traits;

// Laravel & PHP Dependencies
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log; // <-- Usa il Facade Log standard
use Illuminate\Validation\Validator as IlluminateValidator;
use Throwable;
use Exception; // Usato per fallimenti regole di validazione
use ImagickException;
use LogicException; // Usato per errori setup/dipendenze Laravel core

trait HasValidation
{
    // Nota: Le proprietà $logger, $configManager, $errorManager, $testingConditions
    // NON sono più richieste da questo trait. Se la classe che USA il trait
    // le necessita per altri scopi, dovrà gestirle autonomamente.

    /**
     * 🎯 Validate an uploaded file against configured rules using Laravel's config().
     * Main entry point for file validation within a handler using this trait.
     * Orchestrates calls to specific validation methods.
     * Retrieves validation rules using config().
     *
     * --- Core Logic ---
     * 1. Log validation start using Log facade.
     * 2. Execute specific validation steps in a try-catch block:
     *    - `baseValidation()`: Checks MIME/Ext/Size using config(). Throws Exception if rules fail or config missing.
     *    - `validateImageStructure()`: Checks image integrity if applicable. Throws Exception on failure.
     *    - `validateFileName()`: Checks filename rules using config(). Throws Exception on failure.
     *    - `validatePdfContent()`: Checks basic PDF structure if applicable. Throws Exception on failure.
     * 3. Log success if all steps pass.
     * 4. Catch any Throwable during validation, log the error, and re-throw it to be handled by the caller.
     * --- End Core Logic ---
     *
     * @param UploadedFile $file The file instance to validate.
     * @param int|string $index Optional index (kept for potential future use, but simulation removed).
     * @return void Returns nothing; throws exception on validation failure.
     *
     * @throws Exception If any validation rule fails (size, extension, pattern, structure) or if required config keys are missing.
     * @throws LogicException If core Laravel services (like 'validator') are unavailable.
     *
     * @sideEffect Reads configuration via Laravel's `config()` helper.
     * @sideEffect Logs validation steps and errors via Laravel's `Log` facade.
     * @see self::baseValidation()
     * @see self::validateImageStructure()
     * @see self::validateFileName()
     * @see self::validatePdfContent()
     */
    protected function validateFile(UploadedFile $file, int|string $index = 0): void
    {
        $logChannel = property_exists($this, 'logChannel') ? $this->logChannel : 'stack'; // Usa il canale definito nella classe o 'stack'
        $fileNameForLog = $file->getClientOriginalName();

        Log::channel($logChannel)->info(
            '[HasValidation] Starting file validation process (Laravel Standard).',
            [
                'fileName' => $fileNameForLog,
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'index' => $index,
            ]
        );

        try {
            // 1. Base Validation (uses config())
            $this->baseValidation($file, $index);

            // 2. Image Structure Validation (if applicable)
            if ($this->isImageMimeType($file)) {
                $this->validateImageStructure($file);
            }

            // 3. Filename Validation (uses config())
            $this->validateFileName($file);

            // 4. PDF Content Validation (if applicable)
            if ($this->isPdf($file)) {
                $this->validatePdfContent($file);
            }

            Log::channel($logChannel)->info(
                '[HasValidation] File validation completed successfully.',
                ['fileName' => $fileNameForLog]
            );
        } catch (Throwable $e) { // Catch any exception from validation steps
            Log::channel($logChannel)->error(
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
            // Il chiamante (es. BaseUploadHandler) dovrà gestirla.
            throw $e;
        }
    }

    /**
     * 🎯 Perform base file validation (MIME type, extension, size) using rules from Laravel config.
     *
     * @param UploadedFile $file The file to validate.
     * @param int|string $index Index (kept for signature compatibility, not used for simulation now).
     * @return void
     *
     * @throws Exception If Laravel's validation rules fail or if required config keys are missing/invalid.
     * @throws LogicException If core Laravel 'validator' service is unavailable.
     *
     * @sideEffect Reads configuration via `config()`. Logs steps and errors via `Log`.
     * @configReads Required: AllowedFileType.collection.allowed_extensions (Array) from `config/AllowedFileType.php`
     * @configReads Required: AllowedFileType.collection.max_size (Int) from `config/AllowedFileType.php`
     */
    protected function baseValidation(UploadedFile $file, int|string $index): void
    {
        $logChannel = property_exists($this, 'logChannel') ? $this->logChannel : 'stack';
        $fileNameForLog = $file->getClientOriginalName();

        // Resolve Laravel validator factory
        try {
            /** @var ValidationFactory $validatorFactory */
            $validatorFactory = app('validator');
        } catch(Throwable $e) {
             Log::channel($logChannel)->critical('[HasValidation] Failed to resolve core Laravel validator service.', ['error' => $e->getMessage()]);
             throw new LogicException('Core Laravel validator service unavailable in HasValidation trait.', 0, $e);
        }

        // --- Get validation rules from Laravel config() ---
        // Usiamo valori di default robusti nel caso la chiave non esista
        $allowedExtensions = config('AllowedFileType.collection.allowed_extensions', []);
        $maxSizeInBytes = config('AllowedFileType.collection.max_size', 10 * 1024 * 1024); // Default 10MB

        // Valida il *tipo* dei valori di configurazione recuperati
        if (!is_array($allowedExtensions)) {
            Log::channel($logChannel)->error("[HasValidation] Invalid configuration format: 'AllowedFileType.collection.allowed_extensions' must be an array. Using empty array.", ['retrieved_value_type' => gettype($allowedExtensions)]);
            $allowedExtensions = []; // Usa default sicuro
            // Potresti voler lanciare un'eccezione qui se questa config è assolutamente critica
            // throw new LogicException("Invalid configuration format: 'AllowedFileType.collection.allowed_extensions' must be an array.");
        }
        if (!is_int($maxSizeInBytes) || $maxSizeInBytes <= 0) {
            Log::channel($logChannel)->error("[HasValidation] Invalid configuration format: 'AllowedFileType.collection.max_size' must be a positive integer. Using 10MB default.", ['retrieved_value' => $maxSizeInBytes]);
            $maxSizeInBytes = 10 * 1024 * 1024; // Usa default sicuro
             // Potresti voler lanciare un'eccezione qui se questa config è assolutamente critica
            // throw new LogicException("Invalid configuration format: 'AllowedFileType.collection.max_size' must be a positive integer.");
        }
        // --- End config() Usage ---

        $maxSizeInKilobytes = (int)($maxSizeInBytes / 1024);

        Log::channel($logChannel)->debug(
            '[HasValidation] Starting base validation check (MIME, Ext, Size) using Laravel config.',
             [
                 'fileName' => $fileNameForLog,
                 'sizeBytes' => $file->getSize(),
                 'maxSizeKB' => $maxSizeInKilobytes,
                 'mimeType' => $file->getMimeType(),
                 'allowedExtensionsCount' => count($allowedExtensions), // Log count instead of full array potentially
             ]
        );

        // Laravel validation messages (idealmente dovrebbero usare trans())
        $messages = [
             'file.max' => "File exceeds maximum allowed size ({$maxSizeInKilobytes} KB).",
             'file.extensions' => 'File type (extension) is not allowed.',
             'file.required' => 'A file is required.',
             'file.file' => 'The uploaded item is not a valid file.'
         ];

        // Create validator instance
        /** @var IlluminateValidator $validator */
        $validator = $validatorFactory->make(
            ['file' => $file],
            ['file' => ['required', 'file', 'max:' . $maxSizeInKilobytes, 'extensions:' . implode(',', $allowedExtensions)]],
            $messages
        );

        // --- Perform Validation ---
        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('file') ?? 'Unknown base validation error'; // Provide default
            Log::channel($logChannel)->warning(
                '[HasValidation] Base validation failed.',
                [
                    'fileName' => $fileNameForLog,
                    'errors' => $validator->errors()->toArray(),
                    'first_error' => $errorMessage
                ]
            );
            // Lancia un'eccezione standard. Sarà compito del chiamante mapparla a un codice UEM/gestirla.
            throw new Exception("Base validation failed: {$errorMessage}");
        }

        Log::channel($logChannel)->debug(
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
        return is_string($file->getMimeType()) && strtolower($file->getMimeType()) === 'application/pdf';
    }

    /**
     * 🎯 Validate the structural integrity of an image file using Imagick.
     * Checks if the Imagick extension is loaded and attempts a quick `pingImage`.
     *
     * @param UploadedFile $file The image file to validate.
     * @return void
     * @throws Exception If Imagick extension is unavailable, file is unreadable, or `pingImage` fails.
     * @sideEffect Logs validation steps and errors via `Log`. Reads file path. May instantiate Imagick object.
     */
    protected function validateImageStructure(UploadedFile $file): void
    {
        $logChannel = property_exists($this, 'logChannel') ? $this->logChannel : 'stack';
        $fileNameForLog = $file->getClientOriginalName();

        Log::channel($logChannel)->info('[HasValidation] Starting image structure validation.', ['fileName' => $fileNameForLog]);

        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            Log::channel($logChannel)->error('[HasValidation] Imagick extension not available. Cannot validate image structure.', ['fileName' => $fileNameForLog]);
            throw new Exception("Required Imagick extension is not loaded or class Imagick not found."); // Lancia eccezione generica
        }

        $filePath = $file->getRealPath();
        if ($filePath === false || !file_exists($filePath)) {
           Log::channel($logChannel)->error('[HasValidation] Image file path invalid or file not found for structure validation.', ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']);
           throw new Exception("Image file path could not be determined or file does not exist."); // Lancia eccezione generica
        }

        $imagick = null;
        try {
            $imagick = new \Imagick();
            if (!$imagick->pingImage($filePath)) {
                 throw new ImagickException("Imagick::pingImage returned false, file might be corrupt or an unsupported format.");
            }
             Log::channel($logChannel)->info('[HasValidation] Image structure validation passed (pingImage successful).', ['fileName' => $fileNameForLog]);

        } catch (ImagickException $e) { // Catch specific Imagick errors
            Log::channel($logChannel)->error('[HasValidation] Image structure validation failed (ImagickException).', ['fileName' => $fileNameForLog, 'error' => $e->getMessage(), 'code' => $e->getCode()]);
            throw new Exception("Invalid image structure detected by Imagick: " . $e->getMessage(), 0, $e); // Rilancia come generica
        } catch (Throwable $e) { // Catch other unexpected errors
             Log::channel($logChannel)->error('[HasValidation] Unexpected error during image structure validation.', ['fileName' => $fileNameForLog, 'exception_class' => get_class($e), 'error' => $e->getMessage()]);
             throw new Exception("Unexpected error during Imagick validation: " . $e->getMessage(), 0, $e); // Rilancia come generica
        } finally {
            if ($imagick instanceof \Imagick) {
                $imagick->clear();
                $imagick->destroy();
            }
        }
    }

    /**
     * 🎯 Validate the filename against length and pattern rules retrieved from Laravel config.
     *
     * @param UploadedFile $file The file whose name needs validation.
     * @return void
     * @throws Exception If the filename fails length or pattern validation or config is invalid.
     * @sideEffect Reads optional 'file_validation.*' configuration via `config()`. Logs steps/errors via `Log`.
     * @configReads Optional: file_validation.images.max_name_length (Int, default 255) from `config/file_validation.php` (o come definito)
     * @configReads Optional: file_validation.min_name_length (Int, default 1)
     * @configReads Optional: file_validation.allowed_name_pattern (String regex, default '/^[\w\-. ]+$/u')
     */
    protected function validateFileName(UploadedFile $file): void
    {
        $logChannel = property_exists($this, 'logChannel') ? $this->logChannel : 'stack';
        $fileName = $file->getClientOriginalName();

        Log::channel($logChannel)->info('[HasValidation] Starting filename validation using Laravel config (with defaults).', ['fileName' => $fileName]);

        // --- Use config() with defaults ---
        $maxLength = config('file_validation.images.max_name_length', 255);
        $minLength = config('file_validation.min_name_length', 1);
        $allowedPattern = config('file_validation.allowed_name_pattern', '/^[\w\-. ]+$/u');
        // --- End config() Usage ---

        // Valida tipi di config recuperati (logga warning e usa default se invalidi)
        if (!is_int($maxLength) || $maxLength <= 0) {
             Log::channel($logChannel)->warning('[HasValidation] Invalid config value for file_validation.images.max_name_length, using default.', ['retrieved_value' => $maxLength]);
             $maxLength = 255;
        }
        if (!is_int($minLength) || $minLength < 0) {
             Log::channel($logChannel)->warning('[HasValidation] Invalid config value for file_validation.min_name_length, using default.', ['retrieved_value' => $minLength]);
             $minLength = 1;
        }
         if (!is_string($allowedPattern) || empty($allowedPattern)) {
             Log::channel($logChannel)->warning('[HasValidation] Invalid config value for file_validation.allowed_name_pattern, using default.', ['retrieved_value' => $allowedPattern]);
             $allowedPattern = '/^[\w\-. ]+$/u';
         }
          Log::channel($logChannel)->debug('[HasValidation] Filename validation rules applied.', ['minLength' => $minLength, 'maxLength' => $maxLength, 'pattern' => $allowedPattern]);

        // 1. Check Length
        $nameLength = mb_strlen($fileName, 'UTF-8');
        if ($nameLength < $minLength || $nameLength > $maxLength) {
             Log::channel($logChannel)->error('[HasValidation] Filename length validation failed.', ['fileName' => $fileName, 'length' => $nameLength, 'minLength' => $minLength, 'maxLength' => $maxLength]);
             throw new Exception("Invalid filename: Length must be between {$minLength} and {$maxLength} characters.");
        }

        // 2. Check Pattern
        if (!preg_match($allowedPattern, $fileName)) {
            Log::channel($logChannel)->error('[HasValidation] Filename pattern validation failed.', ['fileName' => $fileName, 'pattern' => $allowedPattern]);
            throw new Exception("Invalid filename: Contains disallowed characters or does not match required pattern '{$allowedPattern}'.");
        }

        Log::channel($logChannel)->info('[HasValidation] Filename validation passed.', ['fileName' => $fileName]);
    }

    /**
     * 🎯 Validate the basic structure of a PDF file by checking for the '%PDF' magic header.
     *
     * @param UploadedFile $file The PDF file to validate.
     * @return void
     * @throws Exception If the file is not found, cannot be read, or lacks the '%PDF' header.
     * @sideEffect Reads the beginning of the file content. Logs validation steps via `Log`.
     */
    protected function validatePdfContent(UploadedFile $file): void
    {
        $logChannel = property_exists($this, 'logChannel') ? $this->logChannel : 'stack';
        $fileNameForLog = $file->getClientOriginalName();

        Log::channel($logChannel)->info('[HasValidation] Starting PDF content validation.', ['fileName' => $fileNameForLog]);

        $filePath = $file->getRealPath();
        if ($filePath === false || !file_exists($filePath)) {
           Log::channel($logChannel)->error('[HasValidation] PDF file path invalid or file not found for content validation.', ['fileName' => $fileNameForLog, 'attemptedPath' => $filePath ?: 'N/A']);
           throw new Exception("PDF file path could not be determined or file does not exist.");
        }

        $fileStart = file_get_contents($filePath, false, null, 0, 10);

        if ($fileStart === false) {
             Log::channel($logChannel)->error('[HasValidation] Failed to read start of PDF file.', ['fileName' => $fileNameForLog, 'path' => $filePath]);
             throw new Exception("Could not read PDF file content for validation.");
        }

        if (!str_starts_with($fileStart, '%PDF')) {
             Log::channel($logChannel)->error('[HasValidation] PDF content validation failed (Missing %PDF header).', ['fileName' => $fileNameForLog, 'startBytesHex' => bin2hex($fileStart)]);
             throw new Exception("Invalid PDF file content: Missing '%PDF' header.");
        }

        Log::channel($logChannel)->info('[HasValidation] PDF content validation passed.', ['fileName' => $fileNameForLog]);
    }

    // Rimosso metodo addTestingErrors poiché la simulazione non è più gestita qui

} // End trait HasValidation