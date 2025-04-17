<?php

namespace Ultra\UploadManager\Providers;

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Registra i servizi dell'applicazione.
     *
     * @return void
     */
    public function register()
    {
        $this->checkAndSetupBroadcasting();
    }

    /**
     * Bootstrap dei servizi dell'applicazione.
     *
     * @return void
     */
    public function boot()
    {
        // Registra le routes per broadcasting dopo che la configurazione è stata impostata
        Broadcast::routes();

        // Registra i canali
        $this->registerChannels();

        // Logga la configurazione finale di broadcasting per debug
        // Log::channel('upload')->info("Configurazione finale broadcasting:\n" .
        //     "Driver: " . config('broadcasting.default') . "\n" .
        //     "Connections: " . implode(', ', array_keys(config('broadcasting.connections'))) . "\n" .
        //     "Pusher Config:\n" . json_encode(config('broadcasting.connections.pusher'), JSON_PRETTY_PRINT)
        // );
    }

    /**
     * Controlla e configura le impostazioni di broadcasting se necessario.
     *
     * @return void
     */
    protected function checkAndSetupBroadcasting()
    {
        // Log::channel('upload')->info('Verificando configurazione broadcasting...');

        // Controlla se esiste già una configurazione di broadcasting
        // Nota: verifichiamo sia 'null' (stringa) che null (valore PHP)
        if (config('broadcasting.default') === null ||
            config('broadcasting.default') === 'null' ||
            empty(config('broadcasting.default')) ||
            !config('broadcasting.connections.pusher')) {

            // Log::channel('upload')->info('Configurazione broadcasting non trovata o incompleta. Configurazione automatica in corso...');

            // Imposta la configurazione per il broadcaster
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

            // Log::channel('upload')->info('Configurazione broadcasting impostata automaticamente su pusher.');
        } else {
            // Log::channel('upload')->info('Configurazione broadcasting già presente: ' . config('broadcasting.default'));
        }
    }

    /**
     * Registra i canali di broadcasting.
     *
     * @return void
     */
    protected function registerChannels()
    {
        // Registra qui i tuoi canali di broadcasting
        Broadcast::channel('upload', function () {
            Log::channel('upload')->info('Qualcuno si è connesso al canale upload');
            return true;
        });

        // Altri canali possono essere aggiunti qui o caricati da un file esterno
        if (file_exists(__DIR__ . '/../routes/channels.php')) {
            require __DIR__ . '/../routes/channels.php';
        }

        // Log::channel('upload')->info('Canali broadcasting registrati con successo.');
    }
}
