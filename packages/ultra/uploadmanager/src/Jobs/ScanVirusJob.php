<?php

namespace Ultra\UploadManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use App\Events\FileProcessingUpload;
use Illuminate\Support\Facades\Auth;

class ScanVirusJob implements ShouldQueue
{

    protected $tempPath, $fileName, $user;

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct($tempPath, $fileName, $user)
    {

        $this->tempPath = $tempPath;
        $this->fileName = $fileName;
        $this->user = $user;


    }

    public function handle(): mixed
    {

        $state = "virusScan";
        $message = __("label.antivirus_scan_in_progress");

        // Log della scansione antivirus
        Log::channel('upload')->info("classe: ScanVirusJob. Method: handle. Action: Inizio scansione antivirus per il file: " . $this->fileName);

        // Inviare un messaggio di stato tramite broadcasting
        FileProcessingUpload::dispatch($message . ': ' . $this->fileName, $state, Auth::id(), 0);

        // Esegui la scansione con ClamAV
        $process = new Process(['clamscan', '--no-summary', '--stdout', $this->tempPath]);
        $process->start();

        $progress = 0;
        while ($process->isRunning()) {

            $progress += 10;
            if ($progress > 100) {
                $progress = 100;
            }elseif($progress == 100){
                $progress = 0;
            }

            FileProcessingUpload::dispatch("Scansione in corso: $this->fileName", 'virusScan', Auth::id(), $progress);
            sleep(1); // Adjust the sleep duration as needed

            Log::channel('upload')->info("classe: ScanVirusJob. Method: handle. Action: Scansione in corso per il file: $this->fileName", ['progress' => $progress]);

        }

        // Controlla se il processo è stato eseguito correttamente
        if (!$process->isSuccessful()) {
            Log::channel('upload')->error("classe: ScanVirusJob. Method: handle. Action: Errore durante la scansione antivirus per il file: $this->fileName", ['error' => $process->getErrorOutput()]);
            return response()->json(['message' => __("errors.error_during_file_upload")], 422);
        }

        // Risultato della scansione
        $output = $process->getOutput();
        Log::channel('upload')->info("classe: ScanVirusJob. Method: handle. Action: Risultato della scansione antivirus per il file: $this->fileName", ['output' => $output]);

        if (strpos($output, 'FOUND') !== false) {
            Log::channel('upload')->warning("classe: ScanVirusJob. Method: handle. Action: Il file caricato è stato rilevato come infetto: $this->fileName");
            FileProcessingUpload::dispatch('File infetto: ' . $this->fileName, 'infected', Auth::id(), 100);
            return response()->json(['message' => __("label.the_uploaded_file_was_detected_as_infected")], 422);
        }

        FileProcessingUpload::dispatch('Scan completato per: ' . $this->fileName, 'processingCompleted', Auth::id(), 100);
        Log::channel('upload')->info("classe: ScanVirusJob. Method: handle. Action: Scansione completata per il file: $this->fileName");
        return response()->json(['message' => __("label.file_uploaded_successfully", ['fileCaricato' => $this->fileName])], 200);

    }
}
