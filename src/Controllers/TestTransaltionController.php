<?php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Routing\Controller;


class TestTransaltionController extends Controller
{


public function testTranslations()
{
    $path = __DIR__.'./../../resources/lang/it/uploadmanager.php';
    $translations = include($path);

    // Verifica diretta di alcune chiavi
    $fileUpload = $translations['file_upload'] ?? 'non trovato';

    // Verifica con l'helper trans()
    $transResult = trans('uploadmanager::file_upload');

    return [
        'direct_read' => $fileUpload,
        'trans_result' => $transResult,
        'full_translations' => $translations
    ];
}

public function testServiceProviders()
{
    return [
        'providers' => app()->getLoadedProviders(),
        'our_provider' => array_key_exists('Ultra\\UploadManager\\Providers\\UploadManagerServiceProvider', app()->getLoadedProviders())
    ];
}

public function testPermissions()
{
    $path = __DIR__.'/../resources/lang/en/uploadmanager.php';
    return [
        'readable' => is_readable($path),
        'perms' => fileperms($path),
        'owner' => fileowner($path),
        'group' => filegroup($path)
    ];
}

public function testSyntax()
{
    $path = __DIR__.'/../resources/lang/en/uploadmanager.php';
    $content = file_get_contents($path);
    $array = include $path;

    return [
        'content' => substr($content, 0, 100).'...', // Mostra parte del contenuto
        'array' => $array,
        'is_array' => is_array($array)
    ];
}
}
