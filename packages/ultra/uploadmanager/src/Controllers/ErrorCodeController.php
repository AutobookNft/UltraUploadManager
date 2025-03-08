<?php

// ErrorCodeController.php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

/**
 * Summary of ErrorCodeController
 *
 * Questa classe gestisce i codici degli errori
 * Se il codice dell'errore è presente nell'array criticalError, il codice è critico
 * Se il codice dell'errore è presente nell'array errorConstants, il codice non è critico
 *
 */
class ErrorCodeController extends Controller
{
    public function getErrorConstant($code)
    {
        try {
            // Carica le costanti degli errori critici e non critici
            $criticalError = config('critical_errors');
            $errorConstants = config('error_constants');

            Log::channel('upload')->info('Route: API. Method: getErrorConstant. Action: code', ['code' => $code]);
            Log::channel('upload')->info('Route: API. Method: getErrorConstant. Action: errorConstants', ['errorConstants' => $errorConstants]);
            Log::channel('upload')->info('Route: API. Method: getErrorConstant. Action: criticalError', ['criticalError' => $criticalError]);

            // Cerca la costante nel file di configurazione
            $constant = array_search($code, $errorConstants);
            Log::channel('upload')->info('Route: API. Method: getErrorConstant. Action: constant', ['constant' => $constant]);

            if ($constant === false) {
                Log::channel('upload')->warning('Costante non trovata per il codice fornito', ['code' => $code]);
                return response()->json(['isCritical' => false], 200);
            }

            // Trova la costante all'interno dell'array criticalError
            $isCritical = in_array($constant, $criticalError['codes'], true); // Comparazione stretta
            Log::channel('upload')->info('Route: API. Method: getErrorConstant. Action: isCritical', ['isCritical' => $isCritical]);

            return response()->json(['isCritical' => $isCritical], 200);

        } catch (\Exception $e) {
            Log::channel('upload')->error('Route: API. Method: getErrorConstant. Action: error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}



