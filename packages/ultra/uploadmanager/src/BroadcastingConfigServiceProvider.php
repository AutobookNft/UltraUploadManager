<?php

namespace Ultra\UploadManager;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class BroadcastingConfigServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->checkAndSetupBroadcasting();
    }

    protected function checkAndSetupBroadcasting()
    {
        // Controlla se esiste già una configurazione di broadcasting
        if (config('broadcasting.default') === 'null' || !config('broadcasting.connections.pusher')) {
            Log::channel('upload')->info('Configurazione broadcasting non trovata o impostata su null. Configurazione automatica in corso...');

            // Imposta la configurazione minima necessaria
            config(['broadcasting.default' => 'pusher']);

            // Assicurati che esista la configurazione per pusher
            if (!config('broadcasting.connections.pusher')) {
                config(['broadcasting.connections.pusher' => [
                    'driver' => 'pusher',
                    'key' => env('PUSHER_APP_KEY'),
                    'secret' => env('PUSHER_APP_SECRET'),
                    'app_id' => env('PUSHER_APP_ID'),
                    'options' => [
                        'cluster' => env('PUSHER_APP_CLUSTER', 'eu'),
                        'useTLS' => true,
                        'encrypted' => true,
                    ],
                ]]);
            }

            Log::channel('upload')->info('Configurazione broadcasting impostata automaticamente.');
        }
    }
}
