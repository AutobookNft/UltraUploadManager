import Swal from 'sweetalert2';

// Interfaccia per il risultato della validazione
export interface ValidationResult {
    isValid: boolean;
    message?: string;
}

/**
 * Validates that a file meets all system requirements.
 * Performs checks for file extension, MIME type, file size, and filename format.
 * Displays error messages to the user when validation fails.
 *
 * @param file - The file to validate
 * @returns ValidationResult indicating whether the file is valid and any error message
 */
export function validateFile(file: File): ValidationResult {
    // Defensive programming: check if global config is available
    if (typeof window === 'undefined' || !window.allowedExtensions || !window.allowedMimeTypes) {
        console.error('Global configuration is not properly initialized');

        // Fallback error handling
        Swal.fire({
            title: 'Configuration Error',
            text: 'Upload validation configuration is not loaded. Please contact support.',
            icon: 'error',
            confirmButtonText: 'OK'
        });

        return {
            isValid: false,
            message: 'Configuration not available'
        };
    }

    // Log execution in local environment
    if (window.envMode === 'local') {
        console.log('Validating file:', file.name);
    }

    // Extract file extension
    const extension = file.name.split('.').pop()?.toLowerCase() || '';

    // Validation results array to collect all validation errors
    const validationErrors: string[] = [];

    // Check file extension against allowed list
    if (!window.allowedExtensions.includes(extension)) {
        const allowedExtensionsList = window.allowedExtensions.join(', ');
        const errorMessage = (window.allowedExtensionsMessage || 'File extension :extension is not allowed. Allowed extensions are: :extensions')
            .replace(':extension', extension)
            .replace(':extensions', allowedExtensionsList);
        validationErrors.push(errorMessage);
    }

    // Check MIME type against allowed list
    if (!window.allowedMimeTypes.includes(file.type)) {
        const errorMessage = (window.allowedMimeTypesMessage || 'File type :type is not allowed. Allowed types are: :mimetypes')
            .replace(':type', file.type)
            .replace(':mimetypes', window.allowedMimeTypesListMessage || 'Allowed types');
        validationErrors.push(errorMessage);
    }

    // Check file size against maximum allowed size
    if (file.size > (window.maxSize || 10 * 1024 * 1024)) {
        const errorMessage = (window.maxSizeMessage || 'File size exceeds the maximum allowed size of :size MB')
            .replace(':size', ((window.maxSize || 10 * 1024 * 1024) / 1024 / 1024).toFixed(2));
        validationErrors.push(errorMessage);
    }

    // Check filename format
    if (!validateFileName(file.name)) {
        const errorMessage = (window.invalidFileNameMessage || 'Filename :filename contains invalid characters')
            .replace(':filename', file.name);
        validationErrors.push(errorMessage);
    }

    // If there are validation errors, show comprehensive error message
    if (validationErrors.length > 0) {
        // Combine all validation errors into a single message
        const combinedErrorMessage = validationErrors.join('\n\n');

        // Show comprehensive error message to user using SweetAlert2
        Swal.fire({
            title: 'File Upload Validation Failed',
            html: `<div style="text-align: left;">${combinedErrorMessage.replace(/\n/g, '<br>')}</div>`,
            icon: 'error',
            confirmButtonText: 'OK'
        });

        return {
            isValid: false,
            message: combinedErrorMessage
        };
    }

    // All validation checks passed
    return { isValid: true };
}

/**
 * Validates a filename against a regular expression pattern.
 * Allows alphanumeric characters, underscores, hyphens, periods and spaces.
 *
 * @param fileName - The filename to validate
 * @returns true if the filename is valid, false otherwise
 */
export function validateFileName(fileName: string): boolean {
    // Allow alphanumeric characters, underscores, hyphens, periods and spaces
    const regex = /^[\w\-. ]+$/;
    return regex.test(fileName);
}
