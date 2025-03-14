<?php

namespace Ultra\UploadManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class TempFilesCleaner implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Il numero di ore dopo le quali un file temporaneo è considerato "vecchio"
     * e può essere eliminato.
     *
     * @var int
     */
    protected $hoursThreshold;

    /**
     * Create a new job instance.
     *
     * @param int $hoursThreshold Il numero di ore dopo le quali un file è considerato vecchio
     * @return void
     */
    public function __construct(int $hoursThreshold = 24)
    {
        $this->hoursThreshold = $hoursThreshold;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->cleanStandardTempDirectory();
        $this->cleanSystemTempDirectory();
    }

    /**
     * Pulisce la directory temporanea standard dell'applicazione.
     *
     * @return void
     */
    protected function cleanStandardTempDirectory()
    {
        $tempDir = storage_path('app/' . config('app.bucket_temp_file_folder'));
        
        // Controlla se la directory esiste
        if (!File::exists($tempDir)) {
            Log::info('Directory temporanea standard non trovata', ['path' => $tempDir]);
            return;
        }

        $count = $this->cleanDirectory($tempDir);
        Log::info("Pulizia directory temp standard completata", ['eliminati' => $count, 'directory' => $tempDir]);
    }

    /**
     * Pulisce la directory temporanea di sistema usata come fallback.
     *
     * @return void
     */
    protected function cleanSystemTempDirectory()
    {
        $systemTempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ultra_upload_temp';
        
        // Controlla se la directory esiste
        if (!File::exists($systemTempDir)) {
            Log::info('Directory temporanea di sistema non trovata', ['path' => $systemTempDir]);
            return;
        }

        $count = $this->cleanDirectory($systemTempDir);
        Log::info("Pulizia directory temp di sistema completata", ['eliminati' => $count, 'directory' => $systemTempDir]);
    }

    /**
     * Pulisce una directory eliminando i file più vecchi della soglia configurata.
     *
     * @param string $directory Il percorso della directory da pulire
     * @return int Il numero di file eliminati
     */
    protected function cleanDirectory(string $directory): int
    {
        $now = time();
        $threshold = $this->hoursThreshold * 3600; // Conversione ore in secondi
        $count = 0;

        // Recupera tutti i file nella directory
        $files = File::files($directory);

        foreach ($files as $file) {
            $filePath = $file->getPathname();
            $lastModified = filemtime($filePath);
            
            // Se il file è più vecchio della soglia, eliminalo
            if ($now - $lastModified > $threshold) {
                try {
                    if (File::delete($filePath)) {
                        $count++;
                        Log::info("File temporaneo eliminato", ['file' => $filePath, 'età' => round(($now - $lastModified) / 3600, 1) . ' ore']);
                    }
                } catch (\Exception $e) {
                    Log::warning("Impossibile eliminare file temporaneo", ['file' => $filePath, 'errore' => $e->getMessage()]);
                }
            }
        }

        // Controlla anche eventuali sottodirectory (caso raro, ma possibile)
        $directories = File::directories($directory);
        foreach ($directories as $subDirectory) {
            // Verifica se la subdirectory è vuota
            if (count(File::allFiles($subDirectory)) === 0) {
                try {
                    File::deleteDirectory($subDirectory);
                    Log::info("Sottodirectory vuota eliminata", ['directory' => $subDirectory]);
                } catch (\Exception $e) {
                    Log::warning("Impossibile eliminare sottodirectory", ['directory' => $subDirectory, 'errore' => $e->getMessage()]);
                }
            } else {
                // Pulisci ricorsivamente la sottodirectory
                $count += $this->cleanDirectory($subDirectory);
            }
        }

        return $count;
    }
}
