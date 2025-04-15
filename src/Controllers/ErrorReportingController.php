<?php

namespace Ultra\UploadManager\Controllers;

use App\Events\FileProcessingUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ErrorOccurredMailable;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class ErrorReportingController extends Controller
{
    /**
     * Gestisce la segnalazione di errori JavaScript dal client.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reportJsError(Request $request): \Illuminate\Http\JsonResponse
    {

        try {
            $request->validate([
                'message' => 'required',
                'codeError' => 'required',
                'fileName' => 'required',
                'methodName' => 'required',
                'lineNumber' => 'required',
                'columnNumber' => 'required',
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        $errorDetails = $request->all();

        // Log dell'errore nel file di log
        Log::channel('javascript')->error('Errore JavaScript catturato', $errorDetails);

        try {

            $params = [
                'to' => config('app.devteam_email'),
                'subject' => 'Critical JavaScript Error Detected',
                'message' => $errorDetails['message'],
                'code error' => $errorDetails['codeError'],
                'file' => $errorDetails['fileName'],
                'method' => $errorDetails['methodName'],
                'lineno' => $errorDetails['lineNumber'],
                'colno' => $errorDetails['columnNumber'],
                'stack' => $errorDetails['stack'] ?? null,
            ];

            Log::channel('javascript')->info('Parametri per l\'invio della mail', $params);

            // Invia una email al DevTeam se l'errore è critico, solo se la costante è impostata su true
            // Puoi personalizzare questa logica in base ai tuoi criteri, è utile quando si vuole testare gli errori
            // sensa inviare email al DevTeam, per non sprecare email
            if (config('send_email_devTeam')){ // Costante per l'invio di email in caso di errore critico
                Mail::to($params['to'])->send(new ErrorOccurredMailable($params));
            } else {
                Log::channel('javascript')->info('Email non inviata, costante per l\'invio di email in caso di errore critico non attiva', $params);
                // Simulazione di invio email
                FileProcessingUpload::dispatch('Simulazione di invio mail: \n' . json_encode($params), 'error', Auth::id());
            }

            Log::channel('javascript')->info('Email inviata con successo');

        } catch (\Exception $e) {
            Log::channel('javascript')->error('Errore durante l\'invio della mail', ['message' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }

        return response()->json(['status' => 'error']);
    }

    /**
     * Determina se l'errore JavaScript è critico.
     *
     * @param array $errorDetails
     * @return bool
     */
    protected function isCriticalError(array $errorDetails)
    {

        // Costante per l'invio di email in caso di errore critico
        $SEND_MAIL = config('error_constants.SEND_EMAIL');

        // Puoi personalizzare questa logica in base ai tuoi criteri
        return str_contains($errorDetails['message'], $SEND_MAIL);
    }
}
