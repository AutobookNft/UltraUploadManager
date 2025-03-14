<?php

namespace Ultra\UploadManager\Console;

use Illuminate\Console\Command;
use Ultra\UploadManager\Jobs\TempFilesCleaner;

class CleanTempFilesCommand extends Command
{
    /**
     * Il nome e la firma del comando console.
     *
     * @var string
     */
    protected $signature = 'ultra:clean-temp {--hours=24 : Numero di ore dopo le quali un file è considerato vecchio}';

    /**
     * La descrizione del comando console.
     *
     * @var string
     */
    protected $description = 'Pulisce le directory temporanee dei file di upload';

    /**
     * Esegue il comando console.
     *
     * @return int
     */
    public function handle()
    {
        $hours = $this->option('hours');
        
        $this->info("Avvio pulizia dei file temporanei più vecchi di {$hours} ore...");
        
        // Esegui il job immediatamente (senza accodarlo)
        (new TempFilesCleaner($hours))->handle();
        
        // In alternativa, accoda il job
        // TempFilesCleaner::dispatch($hours);
        
        $this->info('Pulizia completata!');
        
        return Command::SUCCESS;
    }
}
