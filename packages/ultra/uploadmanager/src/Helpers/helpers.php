<?php

if (!function_exists('get_temp_file_path')) {
    /**
     * Generate the full path for a temporary file in the private storage directory.
     *
     * @param string $filename The name of the file
     * @return string The full temporary file path
     */
    function get_temp_file_path(string $filename): string
    {
        return storage_path(config('upload-manager.temp_path') . DIRECTORY_SEPARATOR . $filename);
    }
}
