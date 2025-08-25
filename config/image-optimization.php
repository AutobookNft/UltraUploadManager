<?php

/**
 * Ultra Upload Manager - Image Optimization Configuration
 * 
 * Configuration for responsive image variants generation using Spatie Media Library.
 * Defines variants for different image types (EGI, banners, cards, avatars) with
 * specific dimensions and quality settings optimized for web performance.
 * 
 * Target: Reduce LCP from 10.15s to <2.5s while maintaining HD originals for zoom.
 * 
 * @package Ultra\UploadManager
 * @version 1.0.0
 * @author Fabio Cherici
 */

return [
    /**
     * Image optimization variants configuration
     * 
     * Each image type has specific variants optimized for different devices/contexts.
     * All variants are generated in WebP format for maximum compression efficiency.
     */
    'variants' => [
        /**
         * EGI Standard Images
         * 
         * Primary NFT images requiring multiple responsive variants for gallery views
         * while preserving original HD for zoom functionality.
         */
        'egi' => [
            'thumbnail' => [
                'width' => 150,
                'height' => 150,
                'quality' => 85,
                'format' => 'webp',
                'description' => 'Small thumbnail for grid views'
            ],
            'mobile' => [
                'width' => 400,
                'height' => 400,
                'quality' => 80,
                'format' => 'webp',
                'description' => 'Mobile device optimized'
            ],
            'tablet' => [
                'width' => 600,
                'height' => 600,
                'quality' => 75,
                'format' => 'webp',
                'description' => 'Tablet device optimized'
            ],
            'desktop' => [
                'width' => 800,
                'height' => 800,
                'quality' => 75,
                'format' => 'webp',
                'description' => 'Desktop display optimized'
            ],
        ],

        /**
         * Hero Banner Images
         * 
         * Large promotional banners requiring horizontal aspect ratios
         * for different screen sizes with aggressive compression for LCP optimization.
         */
        'banner' => [
            'mobile' => [
                'width' => 800,
                'height' => 400,
                'quality' => 80,
                'format' => 'webp',
                'description' => 'Mobile banner (2:1 ratio)'
            ],
            'tablet' => [
                'width' => 1200,
                'height' => 600,
                'quality' => 75,
                'format' => 'webp',
                'description' => 'Tablet banner (2:1 ratio)'
            ],
            'desktop' => [
                'width' => 1920,
                'height' => 960,
                'quality' => 70,
                'format' => 'webp',
                'description' => 'Desktop banner (2:1 ratio)'
            ],
        ],

        /**
         * Card Images
         * 
         * Collection cards and preview thumbnails.
         */
        'card' => [
            'default' => [
                'width' => 300,
                'height' => 300,
                'quality' => 85,
                'format' => 'webp',
                'description' => 'Collection card thumbnail'
            ],
        ],

        /**
         * Avatar Images
         * 
         * User and creator profile avatars.
         */
        'avatar' => [
            'default' => [
                'width' => 200,
                'height' => 200,
                'quality' => 90,
                'format' => 'webp',
                'description' => 'User profile avatar'
            ],
        ],
    ],

    /**
     * Global optimization settings
     */
    'settings' => [
        /**
         * Default output format for optimized variants
         */
        'format' => 'webp',

        /**
         * Fallback format if WebP is not supported
         */
        'fallback_format' => 'jpg',

        /**
         * Queue name for background processing
         */
        'queue' => 'images',

        /**
         * Batch size for processing existing images
         */
        'batch_size' => 50,

        /**
         * Timeout for individual image processing (seconds)
         */
        'processing_timeout' => 120,

        /**
         * Maximum file size for optimization (bytes)
         * Images larger than this will be skipped
         */
        'max_file_size' => 100 * 1024 * 1024, // 100MB

        /**
         * Path structure for optimized variants
         * 
         * CRITICAL: Follows existing path structure requirements
         * Pattern: {original_path}/optimized/{variant_name}/{filename}_{width}x{height}.{format}
         */
        'optimized_path_structure' => [
            'subdirectory' => 'optimized',
            'variant_subdirectory' => true, // Create subdirectory for each variant
            'include_dimensions' => true,   // Include dimensions in filename
            'preserve_original_name' => true, // Keep original filename base
        ],

        /**
         * Enable/disable optimization for different upload types
         */
        'enabled_for' => [
            'egi' => true,
            'banner' => true,
            'card' => true,
            'avatar' => true,
            'documents' => false, // Never optimize documents
        ],

        /**
         * Logging configuration
         */
        'logging' => [
            'channel' => 'upload',
            'level' => 'info',
            'log_processing_time' => true,
            'log_file_sizes' => true,
        ],
    ],

    /**
     * Performance monitoring settings
     */
    'monitoring' => [
        /**
         * Track optimization statistics
         */
        'track_stats' => true,

        /**
         * Storage savings calculation
         */
        'calculate_savings' => true,

        /**
         * Performance metrics to track
         */
        'metrics' => [
            'processing_time',
            'file_size_reduction',
            'variants_generated',
            'errors_encountered',
        ],
    ],

    /**
     * Integration settings for Spatie Media Library
     */
    'spatie' => [
        /**
         * Media collections to process
         */
        'collections' => [
            'main_gallery',
            'featured_image',
        ],

        /**
         * Conversion naming pattern
         * 
         * This will be used to name Spatie conversions
         * Pattern: {variant_name}_{image_type}
         */
        'conversion_naming' => '{variant_name}_{image_type}',

        /**
         * Path generator integration
         * 
         * Use existing CustomPathGenerator for path consistency
         */
        'use_custom_path_generator' => true,
    ],
];
