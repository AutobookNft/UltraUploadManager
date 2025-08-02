<?php

/**
 * Ultra Upload Manager - Allowed File Types Configuration
 *
 * This file defines the allowed file types, extensions, MIME types,
 * and maximum sizes for various content types (images, documents, etc.)
 * The organization is structured to facilitate both validation and display
 * of files in the frontend.
 */

return [
    /**
     * Global configuration for all collection types
     */
    'collection' => [
        // Maximum size for all files (100MB)
        'max_size' => 104857600, // 100 MB
        // Maximum 100 file da 100 mb l'uno
        'max_total_size' => 100 * 104857600, // 100 files of 100 MB each

        // List of allowed extensions (used for quick validation)
        'allowed_extensions' => [
            // Images
            'jpg',
            'jpeg',
            'png',
            'gif',
            'bmp',
            'tiff',
            'webp',
            'svg',
            'eps',
            'psd',
            'ai',
            'cdr',
            'heic',
            'heif',
            // Documents
            'pdf',
            'epub',
            'txt',
            'doc',
            'docx',
            'xls',
            'xlsx',
            'ppt',
            'pptx',
            'odt',
            'ods',
            'odp',
            'rtf',
            // Audio
            'mp3',
            'wav',
            'm4a',
            'ape',
            'flac',
            // Video
            'mp4',
            'mov',
            'avi',
            'mkv'
        ],

        // Extension to category mapping (for UI organization)
        'categories' => [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff', 'webp', 'svg', 'eps', 'psd', 'ai', 'cdr', 'heic', 'heif'],
            'document' => ['pdf', 'epub', 'txt', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'odt', 'ods', 'odp', 'rtf'],
            'audio' => ['mp3', 'wav', 'm4a', 'ape', 'flac'],
            'video' => ['mp4', 'mov', 'avi', 'mkv']
        ],

        // Extension to file type mapping (legacy - maintained for compatibility)
        'allowed' => [
            // Images
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'bmp' => 'image',
            'tiff' => 'image',
            'svg' => 'image',
            'webp' => 'image',
            'eps' => 'image',
            'psd' => 'image',
            'ai' => 'image',
            'cdr' => 'image',
            'heic' => 'image',
            'heif' => 'image',
            // Documents
            'pdf' => 'document',
            'epub' => 'document',
            'txt' => 'document',
            'doc' => 'document',
            'docx' => 'document',
            'xls' => 'document',
            'xlsx' => 'document',
            'ppt' => 'document',
            'pptx' => 'document',
            'odt' => 'document',
            'ods' => 'document',
            'odp' => 'document',
            'rtf' => 'document',

            // Audio
            'mp3' => 'audio',
            'wav' => 'audio',
            'm4a' => 'audio',
            'ape' => 'audio',
            'flac' => 'audio',

            // Video
            'mp4' => 'video',
            'mov' => 'video',
            'avi' => 'video',
            'mkv' => 'video'
        ],

        // Allowed MIME types for complete validation
        'allowed_mime_types' => [
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp',
            'image/svg+xml',
            'image/tiff',
            'application/postscript',
            'image/vnd.adobe.photoshop',
            'application/illustrator',
            'application/x-coreldraw',
            'image/heic',
            'image/heif',
            // HEIC/HEIF alternative MIME types that browsers might use
            'image/x-heic',
            'image/x-heif',
            'application/heic',
            'application/heif',

            // Documents
            'application/pdf',
            'application/epub+zip',
            'text/plain',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/vnd.oasis.opendocument.text',
            'application/vnd.oasis.opendocument.spreadsheet',
            'application/vnd.oasis.opendocument.presentation',
            'application/rtf',
            'text/html',

            // Audio
            'audio/mpeg',
            'audio/wav',
            'audio/x-m4a',
            'audio/ape',
            'audio/flac',

            // Video
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska'
        ],

        // Frontend display configuration
        'ui_display' => [
            'image' => [
                'icon' => 'fa-file-image',
                'color' => 'blue',
                'preview' => true
            ],
            'document' => [
                'icon' => 'fa-file-pdf',
                'color' => 'red',
                'preview' => false
            ],
            'audio' => [
                'icon' => 'fa-file-audio',
                'color' => 'green',
                'preview' => false
            ],
            'video' => [
                'icon' => 'fa-file-video',
                'color' => 'purple',
                'preview' => false
            ]
        ],

        // Maximum sizes for specific categories (override global limits)
        'size_limits' => [
            'image' => 104857600,  // 100MB for images
            'document' => 104857600, // 100MB for documents
            'audio' => 104857600,  // 100MB for audio
            'video' => 524288000  // 500MB for video
        ]
    ],

    /**
     * Specific configurations for file types
     * These settings can override the general configuration
     */
    'document' => [
        'max_size' => 104857600,
        'allowed' => [
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'rtf' => 'application/rtf',
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'html' => 'text/html',
        ],
    ],

    /**
     * Advanced security rules configuration
     */
    'security' => [
        // Potentially dangerous files to block even if they have allowed extensions
        'blocked_patterns' => [
            '\.php$',
            '\.exe$',
            '\.sh$',
            '\.bat$',
            '\.cmd$',
            '\.dll$',
            '\.so$'
        ],

        // MIME types to block for security
        'blocked_mime_types' => [
            'application/x-msdownload',
            'application/x-executable',
            'application/x-sh',
            'application/x-php'
        ]
    ],

    /**
     * Filename validation configuration
     */
    'filename_validation' => [
        // Regular expression for allowed characters in filenames
        'pattern' => '/^[\w\-\.\s]+$/',

        // Maximum filename length
        'max_length' => 255,

        // Characters to automatically replace in filenames
        'sanitize_map' => [
            ' ' => '_',
            '&' => 'and',
            '@' => 'at',
            '#' => 'hash',
            '%' => 'percent'
        ]
    ]
];