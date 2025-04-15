<?php

namespace Ultra\UploadManager\Services;

use InvalidArgumentException;

/**
 * Service class to parse human-readable size strings into bytes.
 */
class SizeParser
{
    /**
     * Multipliers for each unit, defined explicitly for clarity and correctness.
     *
     * @var array<string, int>
     */
    protected $units = [
        ''    => 1,                   // No unit = bytes
        'k'   => 1024,                // Kilobytes
        'm'   => 1024 * 1024,         // Megabytes
        'g'   => 1024 * 1024 * 1024,  // Gigabytes
        't'   => 1024 * 1024 * 1024 * 1024, // Terabytes
        'p'   => 1024 * 1024 * 1024 * 1024 * 1024, // Petabytes
        'e'   => 1024 * 1024 * 1024 * 1024 * 1024 * 1024, // Exabytes
        'z'   => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024, // Zettabytes
        'y'   => 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 * 1024 // Yottabytes
    ];

    /**
     * Converts a size string (e.g., "80M", "2G") to bytes.
     *
     * @param string $size Size string to parse (e.g., "80M", "2G", "1024")
     * @return int Size in bytes, rounded to the nearest integer
     * @throws InvalidArgumentException If the size string is invalid
     */
    public function parse($size)
    {
        if (!is_string($size) || empty($size)) {
            throw new InvalidArgumentException("Size must be a non-empty string");
        }

        // Extract numeric part and unit
        $number = preg_replace('/[^0-9.]/', '', $size);
        $unit = preg_replace('/[^a-zA-Z]/', '', $size);

        // Validate numeric part
        if ($number === '' || !is_numeric($number)) {
            throw new InvalidArgumentException("Invalid size format: no valid number found in '$size'");
        }

        $value = (float)$number;

        // Convert unit to lowercase for case-insensitive matching
        $unit = strtolower($unit);

        // Check if unit is valid
        if (!array_key_exists($unit, $this->units)) {
            throw new InvalidArgumentException("Invalid unit '$unit' in size string '$size'");
        }

        // Calculate and return size in bytes
        return (int)round($value * $this->units[$unit]);
    }
}
