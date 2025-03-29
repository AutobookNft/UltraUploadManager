<?php

return [
    // Generale
    'dev' => [
        'invalid_file' => 'Invalid or missing file: :fileName',
        'invalid_file_validation' => 'Validation failed for file :fileName: :error',
        'error_saving_file_metadata' => 'Unable to save metadata for file :fileName',
        'server_limits_restrictive' => 'Server upload limits are more restrictive than application settings',
        // ... altri messaggi
    ],
    'user' => [
        'invalid_file' => 'The uploaded file is invalid. Please try again with another file.',
        'invalid_file_validation' => 'The file does not meet the requirements. Check format and size.',
        'error_saving_file_metadata' => 'An error occurred while saving the file information.',
        'server_limits_restrictive' => '',
        // ... altri messaggi
    ],

    'upload' => [
        'max_files' => 'Max :count file',
        'max_file_size' => 'Max :size per file',
        'max_total_size' => 'Max :size total',
        'max_files_error' => 'You can upload a maximum of :count files at a time.',
        'max_file_size_error' => 'The file ":name" exceeds the maximum allowed size (:size).',
        'max_total_size_error' => 'The total size of the files (:size) exceeds the allowed limit (:limit).',
    ],

    // Enterprise feature badges (Point 4)
    'storage_space_unit' => 'GB',
    'secure_storage' => 'Secure Storage',
    'secure_storage_tooltip' => 'Your files are saved with redundancy to protect your assets',
    'virus_scan_feature' => 'Virus Scan',
    'virus_scan_tooltip' => 'Each file is scanned for potential threats before storage',
    'advanced_validation' => 'Advanced Validation',
    'advanced_validation_tooltip' => 'Format validation and file integrity checks',
    'storage_space' => 'Space: :used/:total GB',
    'storage_space_tooltip' => 'Available storage space for your EGI assets',
    'toggle_virus_scan' => 'Toggle virus scanning on/off',

    // EGI Metadata (Point 3)
    'quick_egi_metadata' => 'Quick EGI Metadata',
    'egi_title' => 'EGI Title',
    'egi_title_placeholder' => 'E.g. Pixel Dragon #123',
    'egi_collection' => 'Collection',
    'select_collection' => 'Select collection',
    'existing_collections' => 'Existing collections',
    'create_new_collection' => 'Create new collection',
    'egi_description' => 'Description',
    'egi_description_placeholder' => 'Brief description of your work...',
    'metadata_notice' => 'These metadata will be associated with your EGI, but you can edit them later.',

    // Accessibility (Point 5)
    'select_files_aria' => 'Select files for upload',
    'select_files_tooltip' => 'Select one or more files from your device',
    'save_aria' => 'Save selected files',
    'save_tooltip' => 'Upload selected files to the server',
    'cancel_aria' => 'Cancel current upload',
    'cancel_tooltip' => 'Cancel the operation and remove the selected files',
    'return_aria' => 'Return to collection',
    'return_tooltip' => 'Return to the collection view without saving',

    // Generale
    'file_saved_successfully' => 'File :fileCaricato saved successfully',
    'file_deleted_successfully' => 'File deleted successfully',
    'first_template_title' => 'Ultra Upload Manager by Fabio Cherici',
    'file_upload' => 'File Upload',
    'max_file_size_reminder' => 'Maximum file size: 10MB',
    'upload_your_files' => 'Upload your files',
    'save_the_files' => 'Save files',
    'cancel' => 'Cancel',
    'return_to_collection' => 'Return to collection',
    'mint_your_masterpiece' => 'Make your own masterpiece',
    'preparing_to_mint' => 'I\'m waiting for your files, dear...',
    'cancel_confirmation' => 'Do you want to cancel?',
    'waiting_for_upload' => 'Upload Status: Waiting...',
    'server_unexpected_response' => 'The server returned an invalid or unexpected response.',
    'unable_to_save_after_recreate' => 'Unable to save the file after recreating the directory.',
    'config_not_loaded' => 'Global configuration not loaded. Make sure JSON has been fetched.',
    'drag_files_here' => 'Drag files here',
    'select_files' => 'Select files',
    'or' => 'or',

    // Validation messages
    'allowedExtensionsMessage' => 'File extension not allowed. The allowed extensions are: :allowedExtensions',
    'allowedMimeTypesMessage' => 'File type not allowed. The allowed file types are: :allowedMimeTypes',
    'maxFileSizeMessage' => 'File size too large. The maximum allowed size is :maxFileSize',
    'minFileSizeMessage' => 'File size too small. The minimum allowed size is :minFileSize',
    'maxNumberOfFilesMessage' => 'Maximum number of files exceeded. The maximum allowed number is :maxNumberOfFiles',
    'acceptFileTypesMessage' => 'File type not allowed. The accepted file types are: :acceptFileTypes',
    'invalidFileNameMessage' => 'Invalid file name. The file name cannot contain the following characters: / \ ? % * : | " < >',

    // Virus scanning
    'virus_scan_disabled' => 'Virus scanning disabled',
    'virus_scan_enabled' => 'Virus scanning enabled',
    'antivirus_scan_in_progress' => 'Antivirus scan in progress',
    'scan_skipped_but_upload_continues' => 'Scan skipped, but upload continues',
    'scanning_stopped' => 'Scanning stopped',
    'file_scanned_successfully' => 'File :fileCaricato scanned successfully',
    'one_or_more_files_were_found_infected' => 'One or more files were found infected',
    'all_files_were_scanned_no_infected_files' => 'All files were scanned and no infected files were found',
    'the_uploaded_file_was_detected_as_infected' => 'The uploaded file was detected as infected',
    'possible_scanning_issues' => 'Warning: possible issues during virus scan',
    'unable_to_complete_scan_continuing' => 'Warning: Unable to complete virus scan, but continuing anyway',

    // Status messages
    'im_checking_the_validity_of_the_file' => 'Checking file validity',
    'im_recording_the_information_in_the_database' => 'Recording information in the database',
    'all_files_are_saved' => 'All files have been saved',
    'upload_failed' => 'Upload failed',
    'some_errors' => 'Some errors occurred',
    'no_file_uploaded' => 'No file uploaded',

    // JavaScript translations
    'js' => [

        'upload_processing_error' => 'Error processing the upload',
        'invalid_server_response' => 'The server returned an invalid or unexpected response.',
        'unexpected_upload_error' => 'Unexpected error during upload.',
        'critical_upload_error' => 'Critical error during upload',
        'file_not_found_for_scan' => 'File not found for antivirus scan',
        'scan_error' => 'Error during antivirus scan',
        'no_file_specified' => 'No file specified',
        'confirm_cancel' => 'Do you want to cancel?',
        'upload_waiting' => 'Upload Status: Waiting...',
        'server_error' => 'The server returned an invalid or unexpected response.',
        'save_error' => 'Unable to save the file after recreating the directory.',
        'config_error' => 'Global configuration not loaded. Make sure data has been fetched.',
        'starting_upload' => 'Starting upload',
        'loading' => 'Loading',
        'upload_finished' => 'Upload completed',
        'upload_and_scan' => 'Upload and scan completed',
        'virus_scan_advice' => 'Virus scanning may slow down the upload process',
        'enable_virus_scanning' => 'Virus scanning enabled',
        'disable_virus_scanning' => 'Virus scanning disabled',
        'delete_button' => 'Delete',
        'of' => 'of',
        'delete_file_error' => 'Error deleting file',
        'some_error' => 'Some errors occurred',
        'complete_failure' => 'Upload completely failed',

        // Added translations for emoji feedback
        'emoji_happy' => 'Upload completed successfully',
        'emoji_sad' => 'Some files had errors during upload',
        'emoji_angry' => 'Upload completely failed',

        // Added translations for upload process
        'starting_saving' => 'Starting to save files',
        'starting_scan' => 'Starting virus scan',
        'scanning_complete' => 'Scan completed',
        'Scanning_stopped' => 'Scanning stopped',
        'scanning_success' => 'Scan successful :fileCaricato',

        // Added translations for error handling
        'error_during_upload' => 'Error during upload processing',
        'error_delete_temp_local' => 'Error deleting local temporary file',
        'error_delete_temp_ext' => 'Error deleting external temporary file',
        'error_during_upload_request' => 'Error during upload request',
        'unknown_error' => 'Unknown error',
        'unspecifiedError' => 'Unspecified error',

        // Validation messages
        'invalidFilesTitle' => 'Invalid Files Detected',
        'invalidFilesMessage' => 'The following files could not be uploaded',
        'checkFilesGuide' => 'Please check file types, sizes, and names.',
        'okButton' => 'OK',

        'file_types' => [
        'image' => 'Image',
        'document' => 'Document',
        'audio' => 'Audio',
        'video' => 'Video',
        'archive' => 'Archive',
        '3d-model' => '3D Model',
        ],

        'file_extensions' => [
            // Archives
            'zip' => 'ZIP Archive',
            'rar' => 'RAR Archive',
            '7z' => '7-Zip Archive',
            'tar' => 'TAR Archive',
            'gz' => 'GZip Archive',

            // 3D Models
            'obj' => 'OBJ 3D Model',
            'fbx' => 'FBX 3D Model',
            'stl' => 'STL 3D Model',
            'glb' => 'GLB 3D Model',
            'gltf' => 'glTF 3D Model',

            // Additional audio formats
            'aac' => 'AAC Audio',
            'ogg' => 'OGG Audio',
            'wma' => 'WMA Audio',

            // Additional video formats
            'wmv' => 'WMV Video',
            'flv' => 'Flash Video',
            'webm' => 'WebM Video'
        ],

        'validation_errors' => [
            'unsupported_type' => 'File type not supported',
            'archive_too_large' => 'Archive file is too large (maximum size: :size MB)',
            '3d_model_too_large' => '3D model file is too large (maximum size: :size MB)',
            'security_blocked' => 'This file type has been blocked for security reasons',
        ],

    ],
];
