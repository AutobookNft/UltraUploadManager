<?php

namespace Ultra\UploadManager\Controllers\Mail;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendErrorEmail
{
    public static function sendErrorEmail($params)
    {

        Log::channel('tests')->info('Classe: SendErrorEmail. Metodo: sendErrorEmail Action: $params: ', );
        $to = $params['to'];

        try {

            Mail::to($to)->send(new ErrorOccurredMailable($params));
            Log::channel('tests')->info('classe: SendErrorEmail. Metodo: sendErrorEmail Action: Email spedita correttamente');
            // L'e-mail è stata inviata con successo
            return true;

        } catch (\Exception $e) {

            // L'e-mail non è stata inviata
            $logError = [
                'Class' => 'SendErrorEmail',
                'Method' => 'sendErrorEmail',  // nome del metodo corretto
                'Action' => 'Email al dev team non è stata inviata',
                'Error' => $e->getCode(),
                'File' => $e->getFile(),
                'Line' => $e->getLine(),
                'Message' => $e->getMessage(),

            ];
            Log::channel('tests')->error('classe: SendErrorEmail. Metodo: sendErrorEmail Action: Error send: '. json_encode($logError));

            return false;
        }
    }
}
