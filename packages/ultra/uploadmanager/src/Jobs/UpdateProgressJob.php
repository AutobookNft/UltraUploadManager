<?php

namespace Ultra\UploadManager\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateProgressJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $progress;

    public function __construct($progress)
    {
        $this->progress = $progress;
    }

    public function handle()
    {
        // Qui puoi implementare la logica per aggiornare il progresso
        // Per esempio, emettere un evento o aggiornare un record nel database
    }
}
