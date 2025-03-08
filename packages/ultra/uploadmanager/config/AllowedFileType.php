<?php

/*

This configuration file contains the allowed types and maximum sizes
for various file types. The file types are organized by key, with the allowed
types and maximum size for each type stored in sub-arrays.
he keys and sub-array structures are as follows:

'images': allowed image types and maximum size
'ebooks': allowed ebook types and maximum size
'audio': allowed audio types and maximum size
'video': allowed video types and maximum size"

*/

return [
    'collection' => [
        'max_size' => 104857600, // 100mb in bytes
        'allowed_extensions' => [
            'jpg',
            'jpeg',
            'png',
            'gif',
            'bmp',
            'tiff',
            'svg',
            'eps',
            'psd',
            'ai',
            'cdr',
            'webp',
            'pdf',
            'epub',
            'txt',
            'mp3',
            'wav',
            'm4a',
            'ape',
            'flac',
            'mp4',
            'mov',
            'avi',
            'mkv',
        ],
        'allowed' => [
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'bmp' => 'image',
            'tiff' => 'image',
            'svg' => 'image',
            'eps' => 'image',
            'psd' => 'image',
            'ai' => 'image',
            'cdr' => 'image',
            'webp' => 'image',
            'pdf' => 'e-book',
            'epub' => 'e-book',
            'txt' => 'e-book',
            'mp3' => 'audio',
            'wav' => 'audio',
            'm4a' => 'audio',
            'ape' => 'audio',
            'flac' => 'audio',
            'mp4' => 'video',
            'mov' => 'video',
            'avi' => 'video',
            'mkv' => 'video',
        ],
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/bmp',
            'image/webp',
            'image/svg+xml',
            'image/tiff',
            'application/pdf',
            'application/epub+zip',
            'text/plain',
            'audio/mpeg',
            'audio/wav',
            'audio/x-m4a',
            'audio/ape',
            'audio/flac',
            'video/mp4',
            'video/quicktime',
            'video/x-msvideo',
            'video/x-matroska',
            // Aggiungi qui altri MIME types se necessario
        ],
    ],

    'document' => [
        'max_size' => 104857600, // in byte
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
        // in KB
    ],
];
