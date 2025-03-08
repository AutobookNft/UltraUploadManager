<?php

namespace Ultra\UploadManager\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;


/**
 * Class NonBlocking
 * @package App\Http\Controllers
 * @version 1.0
 * @since 1.0
 * @description Questa classe gestisce i codici degli errori non bloccanti
 * Se il codice dell'errore è presente nell'array nonBlockingErrorConstants, il codice non è bloccante
 */

class NonBlockingErrorController extends Controller
{
    public function getNonBlockingErrorConstant($code): string    {

        try{

            // Carica le costanti degli errori non bloccanti
            $nonBlockingErrorConstants = config('non_blocking_error_constants');
            $errorConstants = config('error_constants');

            Log::channel('upload')->info('Route: API. Method: getNonBlockingErrorConstant. Action: nonBlockingErrorConstants', ['nonBlockingErrorConstants' => $nonBlockingErrorConstants]);
            Log::channel('upload')->info('Route: API. Method: getNonBlockingErrorConstant. Action: errorConstants', ['errorConstants' => $errorConstants]);

            // Cerca la costante nel file di configurazione
            $constant = array_search($code, $errorConstants);
            Log::channel('upload')->info('Route: API. Method: getNonBlockingErrorConstant. Action: constant', ['constant' => $constant]);

            $constant = in_array($constant, $nonBlockingErrorConstants) ? $constant : null;
            Log::channel('upload')->info('Route: API. Method: getNonBlockingErrorConstant. Action: constant', ['constant' => $constant]);

            if ($constant) {
                // Costante trovata, il codice non è bloccante
                return response()->json(['isNotBlocking' => true], 200);
            }

            // Costante non trovata, il codice è bloccante
            return response()->json(['isNotBlocking' => false], 200);

        } catch (\Exception $e) {
            Log::channel('upload')->error('Route: API. Method: getNonBlockingErrorConstant. Action: error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
