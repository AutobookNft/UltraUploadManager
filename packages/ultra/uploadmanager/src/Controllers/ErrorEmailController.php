<?php

namespace Ultra\UploadManager\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Ultra\UploadManager\Mail\SendErrorEmail;


class ErrorEmailController extends Controller
{
    public function send(Request $request)
    {
        // Recupera i parametri dalla richiesta
        $params = $request->all();

        // Chiama la classe SendErrorEmail per inviare l'email
        $emailSent = SendErrorEmail::sendErrorEmail($params);

        if ($emailSent) {
            return response()->json(['status' => 'success', 'message' => 'Email inviata correttamente.']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Errore durante l\'invio dell\'email.'], 500);
        }
    }
}
