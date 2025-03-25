<?php

namespace Ultra\UploadManager\Traits;

use Illuminate\Support\Facades\Validator;
use Ultra\ErrorManager\Facades\UltraError;
use Ultra\ErrorManager\Facades\TestingConditions;
use Ultra\UltraLogManager\Facades\UltraLog;
use Exception;

/**
 * Trait HasValidation
 *
 * This trait provides a set of methods for uploaded file validation,
 * including checks on extensions, MIME types, sizes, and image structure.
 * It is used to ensure that uploaded files meet the security and
 * validity criteria required by the application.
 *
 * @package Ultra\UploadManager\Traits
 */
trait HasValidation
{
    /**
     * Validates an uploaded file.
     *
     * @param \Illuminate\Http\UploadedFile $file The uploaded file to be validated.
     * @param string|int $index File index for test simulations.
     * @throws \Exception If validation fails.
     */
    protected function validateFile($file, $index = 0)
    {
        // Initialize the logging channel
        $channel = isset($this->channel) ? $this->channel : 'upload';

        UltraLog::info(
            'FileValidationStart',
            'Starting file validation process',
            [
                'fileName' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'index' => $index
            ],
            $channel
        );

        try {
            // Perform base file validation
            $this->baseValidation($file, $index);

            // If the file is an image, validate the image structure
            if ($this->isImageMimeType($file)) {
                $this->validateImageStructure($file, $index);
            }

            // Validate the file name to ensure it meets defined rules
            $this->validateFileName($file);

            // If the file is a PDF, validate the PDF content
            if ($this->isPdf($file)) {
                $this->validatePdfContent($file);
            }

            UltraLog::info(
                'FileValidationComplete',
                'File validation completed successfully',
                [
                    'fileName' => $file->getClientOriginalName()
                ],
                $channel
            );
        } catch (Exception $e) {
            UltraLog::error(
                'FileValidationFailed',
                'File validation failed',
                [
                    'fileName' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                    'exception' => get_class($e)
                ],
                $channel
            );

            // Rethrow the exception so it can be handled by the controller
            throw $e;
        }
    }

    /**
     * Performs base validation of the file.
     *
     * @param \Illuminate\Http\UploadedFile $file The file to validate.
     * @param string|int $index File index for test simulations.
     * @throws \Exception If validation fails.
     */
    protected function baseValidation($file, $index)
    {
        // Initialize the logging channel
        $channel = isset($this->channel) ? $this->channel : 'upload';

        // Get allowed extensions and maximum size from configuration
        $allowedTypes = config('AllowedFileType.collection.allowed_extensions');
        $maxSize = config('AllowedFileType.collection.max_size');

        UltraLog::info(
            'BaseValidationStart',
            'Starting base file validation',
            [
                'fileName' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mimeType' => $file->getMimeType(),
                'allowedTypes' => $allowedTypes,
                'maxSize' => $maxSize
            ],
            $channel
        );

        // Error codes
        $errorCodes = [
            'file.mimes' => 'MIME_TYPE_NOT_ALLOWED',
            'file.max' => 'MAX_FILE_SIZE',
            'file.extension' => 'INVALID_FILE_EXTENSION',
            'file.name' => 'INVALID_FILE_NAME',
            'file.structure' => 'INVALID_IMAGE_STRUCTURE',
            'file.pdf' => 'INVALID_FILE_PDF',
            'imagic_failed' => 'IMAGICK_NOT_AVAILABLE',
        ];

        // Perform base validation using Laravel's validator
        $validator = Validator::make(
            ['file' => $file],
            ['file' => 'file|mimes:' . implode(',', $allowedTypes) . '|max:' . $maxSize],
            $errorCodes
        );

        // Add any simulated test errors
        $this->addTestingErrors($validator, $errorCodes, $index);

        // If validation fails, throw an exception with the appropriate error code
        if ($validator->fails()) {
            // Get the key of the first error
            $firstErrorKey = array_key_first($validator->errors()->getMessages());
            $errorCode = $errorCodes[$firstErrorKey];
            $message = $validator->errors()->first();

            UltraLog::warning(
                'BaseValidationFailed',
                'Base validation failed',
                [
                    'fileName' => $file->getClientOriginalName(),
                    'errorCode' => $errorCode,
                    'errorMessage' => $message
                ],
                $channel
            );

            // Create an exception that will be handled by the controller with UltraError
            $exception = new Exception("File validation failed: {$message}");
            throw $exception;
        }

        UltraLog::info(
            'BaseValidationPassed',
            'Base validation passed',
            [
                'fileName' => $file->getClientOriginalName()
            ],
            $channel
        );
    }

    /**
     * Checks if the file is an image.
     *
     * @param \Illuminate\Http\UploadedFile $file The file to check.
     * @return bool True if it's an image, otherwise False.
     */
    protected function isImageMimeType($file)
    {
        $mimeType = $file->getMimeType();
        return strpos($mimeType, 'image/') === 0;
    }

    /**
     * Checks if the file is a PDF.
     *
     * @param \Illuminate\Http\UploadedFile $file The file to check.
     * @return bool True if it's a PDF, otherwise False.
     */
    protected function isPdf($file)
    {
        return $file->getMimeType() === 'application/pdf';
    }

    /**
     * Adds simulated test errors to the validator.
     *
     * @param \Illuminate\Validation\Validator $validator Laravel validator.
     * @param array $errorCodes Array of error codes.
     * @param string|int $index File index for test simulations.
     */
    protected function addTestingErrors($validator, $errorCodes, $index)
    {
        // Initialize the logging channel
        $channel = isset($this->channel) ? $this->channel : 'upload';

        // Check if the index is the one used for tests
        if ($index !== "0") {
            return;
        }

        UltraLog::info(
            'TestingErrorsCheck',
            'Checking test conditions for error simulation',
            ['index' => $index],
            $channel
        );

        $testConditions = [
            'MAX_FILE_SIZE' => 'file.max',
            'INVALID_FILE_EXTENSION' => 'file.extension',
            'INVALID_FILE_NAME' => 'file.name',
            'MIME_TYPE_NOT_ALLOWED' => 'file.mimes',
            'INVALID_IMAGE_STRUCTURE' => 'file.structure',
            'IMAGICK_NOT_AVAILABLE' => 'imagic_failed',
            'INVALID_FILE_PDF' => 'file.pdf',
        ];

        foreach ($testConditions as $test => $errorKey) {
            // Use the TestingConditions facade instead of TestingConditionsManager::getInstance()
            if (TestingConditions::isTesting($test)) {
                UltraLog::info(
                    'TestErrorSimulation',
                    'Test error simulation activated',
                    [
                        'test' => $test,
                        'errorKey' => $errorKey
                    ],
                    $channel
                );

                $validator->after(function ($validator) use ($errorCodes, $errorKey) {
                    $validator->errors()->add($errorKey, $errorCodes[$errorKey]);
                });
            }
        }
    }

    /**
     * Validates the structure of an image.
     *
     * @param \Illuminate\Http\UploadedFile $file The image file to validate.
     * @param string|int $index File index for test simulations.
     * @throws \Exception If the image structure is invalid.
     */
    protected function validateImageStructure($file, $index)
    {
        // Initialize the logging channel
        $channel = isset($this->channel) ? $this->channel : 'upload';

        UltraLog::info(
            'ImageStructureValidationStart',
            'Starting image structure validation',
            [
                'fileName' => $file->getClientOriginalName()
            ],
            $channel
        );

        // Check if Imagick is available
        if (!class_exists('Imagick')) {
            UltraLog::error(
                'ImagickNotAvailable',
                'Imagick is not available for image validation',
                [
                    'fileName' => $file->getClientOriginalName()
                ],
                $channel
            );

            $exception = new Exception("Imagick is not available for image validation");
            throw $exception;
        }

        // Get the temporary path of the uploaded file
        $filePath = $file->getRealPath();

        try {
            // Verify file path exists before using Imagick
            if (!file_exists($filePath)) {
                UltraLog::error(
                    'ImageFileNotFound',
                    'Image file not found at specified path',
                    [
                        'fileName' => $file->getClientOriginalName(),
                        'filePath' => $filePath
                    ],
                    $channel
                );
                throw new Exception("Image file not found at specified path");
            }

            // Use Imagick to verify that the image is valid
            $image = new \Imagick($filePath);

            // Use pingImage for lightweight validation
            $image->pingImage($filePath);

            UltraLog::info(
                'ImageStructureValidationPassed',
                'Image structure is valid',
                [
                    'fileName' => $file->getClientOriginalName()
                ],
                $channel
            );

        } catch (\ImagickException $e) {
            UltraLog::error(
                'ImageStructureValidationFailed',
                'Image structure validation failed',
                [
                    'fileName' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ],
                $channel
            );

            throw new Exception("Invalid image structure: " . $e->getMessage());
        }
    }

    /**
     * Validates the file name.
     *
     * @param \Illuminate\Http\UploadedFile $file The file whose name to validate.
     * @throws \Exception If the file name is invalid.
     */
    protected function validateFileName($file)
    {
        // Initialize the logging channel
        $channel = isset($this->channel) ? $this->channel : 'upload';

        // Get the original file name
        $fileName = $file->getClientOriginalName();

        UltraLog::info(
            'FileNameValidationStart',
            'Starting file name validation',
            [
                'fileName' => $fileName
            ],
            $channel
        );

        // Minimum and maximum allowed lengths for the file name
        $maxLength = config('file_validation.images.max_name_length', 255);
        $minLength = config('file_validation.min_name_length', 1);
        $allowedPattern = config('file_validation.allowed_name_pattern', '/^[\w\-. ]+$/');

        // Verify that the file name has an appropriate length
        if (strlen($fileName) < $minLength || strlen($fileName) > $maxLength) {
            UltraLog::error(
                'FileNameLengthInvalid',
                'File name is too long or too short',
                [
                    'fileName' => $fileName,
                    'length' => strlen($fileName),
                    'minLength' => $minLength,
                    'maxLength' => $maxLength
                ],
                $channel
            );

            throw new Exception("Invalid file name: length not allowed");
        }

        // Verify that the file name does not contain disallowed characters
        if (!preg_match($allowedPattern, $fileName)) {
            UltraLog::error(
                'FileNamePatternInvalid',
                'File name contains disallowed characters',
                [
                    'fileName' => $fileName,
                    'pattern' => $allowedPattern
                ],
                $channel
            );

            throw new Exception("Invalid file name: disallowed characters");
        }

        UltraLog::info(
            'FileNameValidationPassed',
            'File name is valid',
            [
                'fileName' => $fileName
            ],
            $channel
        );
    }

    /**
     * Validates the content of a PDF file.
     *
     * @param \Illuminate\Http\UploadedFile $file The PDF file to validate.
     * @throws \Exception If the PDF content is invalid.
     */
    protected function validatePdfContent($file)
    {
        // Initialize the logging channel
        $channel = isset($this->channel) ? $this->channel : 'upload';

        UltraLog::info(
            'PdfContentValidationStart',
            'Starting PDF content validation',
            [
                'fileName' => $file->getClientOriginalName()
            ],
            $channel
        );

        // Get the temporary path of the uploaded PDF file
        $filePath = $file->getRealPath();

        // Verify that the file exists
        if (!file_exists($filePath)) {
            UltraLog::error(
                'PdfFileNotFound',
                'PDF file not found at specified path',
                [
                    'fileName' => $file->getClientOriginalName(),
                    'filePath' => $filePath
                ],
                $channel
            );
            throw new Exception("PDF file not found at specified path");
        }

        // Verify if the PDF is valid by looking for common keywords in PDFs
        if (!strpos(file_get_contents($filePath), '%PDF')) {
            UltraLog::error(
                'PdfContentValidationFailed',
                'PDF file content is invalid',
                [
                    'fileName' => $file->getClientOriginalName()
                ],
                $channel
            );

            throw new Exception("Invalid PDF file content");
        }

        UltraLog::info(
            'PdfContentValidationPassed',
            'PDF content is valid',
            [
                'fileName' => $file->getClientOriginalName()
            ],
            $channel
        );
    }
}
