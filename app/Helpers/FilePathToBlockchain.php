<?php

namespace App\Helpers;

class FilePathToBlockchain
{
    public static function execute($collection, $token): string
    {
        return config('app.bucket_path_file_folder_metadata') . "/" .
            "collections" . "/" . $collection  . "/" .
            "token" . "/" . $token . "/";

    }
}
