<?php

namespace App\Http\Controllers;

use Ultra\TranslationManager\Facades\UltraTrans;

class TestTranslationController extends Controller
{
    public function test()
    {
        // Recupera alcune traduzioni
        $welcome = UltraTrans::get('welcome', [], 'core');
        $uploadSuccess = UltraTrans::get('upload.success', [], 'core');
        $testMessage = UltraTrans::get('test.message', ['param' => 'Fabio'], 'core');
        $missing = UltraTrans::get('missing.key', [], 'core');

        return response()->json([
            'welcome' => $welcome,
            'upload_success' => $uploadSuccess,
            'test_message' => $testMessage,
            'missing' => $missing,
        ]);
    }
}
