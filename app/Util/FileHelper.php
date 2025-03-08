<?php

namespace App\Util;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FileHelper
{
    /**
     * @throws \Exception
     */
    public static function getAssetVersion($path): string
    {
        // Se sei in locale, restituisci una stringa vuota (perché userai @vite)
        if (app()->environment('local')) {
            return '';
        }

        $manifestPath = public_path('/build/manifest.json');

        Log::channel('loading')->info('Checking manifest at: '.$manifestPath);

        // Se il manifest esiste in produzione, usa il suo contenuto
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);

            Log::channel('loading')->info('Manifest content: '.json_encode($manifest));

            if (isset($manifest[$path]['file'])) {
                Log::channel('loading')->info('Asset Path: '.$path);
                Log::channel('loading')->info('Returned Path: '.'/build/'.$manifest[$path]['file']);

                return '/build/'.$manifest[$path]['file'];
            }
        }

        // Se non viene trovato nulla, restituisci il percorso non versionato come fallback
        return asset($path);
    }

    public static function getAcceptedFileTypes($type)
    {
        $config = config('file_validation');

        $allowedTypes = $config[$type]['allowed_types'];

        $mimeTypes = $config[$type]['mime_types'];

        $result = [];

        foreach ($allowedTypes as $allowedType) {
            array_push($result, $mimeTypes[$allowedType]);
        }

        return implode(',', $result);
    }

    public static function generate_position_number($team_id)
    {

        // Log::channel('upload')->info('Classe: FileHelper. Method: generate_position_number. Action: team_id: '. json_encode($team_id));

        try{

            $max_position = DB::table('teams_items')->where('team_id', $team_id)->max('position');
            // Log::channel('upload')->info('Classe: FileHelper. Method: generate_position_number. Action: max_position: '. json_encode($max_position));

            // Se non ci sono record, $max_position sarà null. Coalesce a 0 in questo caso.
            return ($max_position ?? 0) + 1;

        } catch (\Exception $e) {
            Log::channel('upload')->error('Classe: FileHelper. Method: generate_position_number. Action: Errore: '. $e->getMessage());
            return 0;
        }

    }

    public static function fileAccepted($file)
    {

        $maxdimention = config('AllowedFileType.collection.max_size');

        //dd($file->getSize(), $maxdimention);

        if ($file->getSize() > $maxdimention) {
            return false;
        }

        $allowedTypes = config('AllowedFileType.collection.allowed');
        $extension = $file->getClientOriginalExtension();

        // dd(var_dump($allowedTypes), $extension);

        if (array_key_exists($extension, $allowedTypes)) {
            $value = $allowedTypes[$extension];
        } else {
            $value = false;
        }

        return $value;
    }

    public static function getSlogan()
    {
        $currentLocale = app()->getLocale();
        $slogan = env('PLATFORM_SLOGAN_'.strtoupper($currentLocale));
        Log::channel('upload')->info('$slogan: '.'PLATFORM_SLOGAN_'.strtoupper($currentLocale));

        return env('PLATFORM_SLOGAN_'.strtoupper($currentLocale));
    }
}
