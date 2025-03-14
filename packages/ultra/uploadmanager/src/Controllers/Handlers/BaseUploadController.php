<?php

namespace Ultra\UploadManager\Controllers\Handlers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Ultra\UploadManager\Events\FileProcessingUpload;

class BaseUploadController extends Controller
{
    public function hendler(Request $request)
    {
        // 1. Verifica che il file sia valido
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json(['error' => 'File non valido o mancante'], 400);
        }

        // 2. Salva informazioni PRIMA di spostare il file
        $fileSize = $file->getSize();
        $originalExtension = $file->getClientOriginalExtension();
        $originalName = $file->getClientOriginalName();

        // 3. Configura e sposta il file
        $path = config('upload-manager.default_path');
        Log::channel('upload')->info('Route: API. Method: hendler. Action: path', ['path' => $path]);
        $file->move($path, $originalName);

        // 4. Costruisci i dati con le informazioni già ottenute
        $fullPath = $path . DIRECTORY_SEPARATOR . $originalName;
        $fileData = [
            'name'      => $originalName,
            'hash'      => md5_file($fullPath),
            'size'      => $fileSize, // Usa il valore salvato prima
            'extension' => $originalExtension, // Usa il valore salvato prima
        ];

        // 5. Salva nel JSON (il resto del codice rimane uguale)
        $jsonPath = storage_path('app/uploads.json');
        $uploads = [];
        if (file_exists($jsonPath)) {
            $json = file_get_contents($jsonPath);
            $uploads = json_decode($json, true) ?? [];
        }
        $uploads[] = $fileData;
        file_put_contents($jsonPath, json_encode($uploads, JSON_PRETTY_PRINT));

        $response = ['message' => 'Upload completato, avvio scansione virus...'];

        $this->simulateVirusScan($originalName);

        return response()->json($response);

    }

    /**
     * Simula una scansione antivirus con aggiornamenti push.
     *
     * @param string $fileName
     * @return void
     */
    protected function simulateVirusScan(string $fileName): void
    {
        // Prendi l'ID utente se esiste, altrimenti usa null
        $userId = Auth::check() ? Auth::id() : null;

        // Stato iniziale della simulazione
        $status = [
            'file'     => $fileName,
            'progress' => 0,
            'status'   => 'in_progress',
            'message'  => 'Inizio scansione virus...'
        ];

        // Simula la scansione in 5 step (ogni step simula un aggiornamento)
        for ($i = 1; $i <= 5; $i++) {
            sleep(1); // Simula un ritardo (es. 1 secondo per step)

            $status['progress'] = $i * 20; // Aggiornamento progressivo
            $status['message'] = "Scansione in corso: {$status['progress']}% completato";

            event(new FileProcessingUpload($status['message'], 'virusScan', $userId, $status['progress']));

            Log::channel('upload')->info("Scansione virus: {$status['progress']}% completato per il file {$fileName}");
        }

        // Stato finale: Scansione completata
        $status['progress'] = 100;
        $status['status'] = 'completed';
        $status['message'] = "Scansione completata. Nessuna infezione rilevata.";

        event(new FileProcessingUpload($status['message'], 'virusScan', $userId, $status['progress']));

        Log::channel('upload')->info("Scansione completata per il file {$fileName}");
    }

}
