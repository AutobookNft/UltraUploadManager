<?php

namespace Ultra\UploadManager\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class UltraSetupCommand extends Command
{
    protected $signature = 'ultra:setup {--force : Sovrascrive configurazioni esistenti}';
    protected $description = 'Configura l\'ambiente per Ultra UploadManager';

    public function handle()
    {
        $this->info('Configurazione di Ultra UploadManager in corso...');

        // 1. Pubblica i file di configurazione
        $this->publishConfigs();

        // 2. Verifica/aggiunge variabili d'ambiente
        $this->setupEnvironmentVariables();

        // 3. Suggerisce installazione dipendenze npm
        $this->checkNpmDependencies();

        // 4. Registra provider nel config/app.php se necessario
        $this->registerServiceProviders();

        $this->info('✅ Configurazione completata con successo!');
        $this->info('Per completare la configurazione, assicurati di avere un account Pusher e imposta le relative chiavi nel file .env');

        return Command::SUCCESS;
    }

    protected function publishConfigs()
    {
        $this->info('Pubblicazione file di configurazione...');
        $params = ['--provider' => 'Ultra\UploadManager\UploadManagerServiceProvider'];

        if ($this->option('force')) {
            $params['--force'] = true;
        }

        Artisan::call('vendor:publish', $params);
        $this->info('File di configurazione pubblicati.');
    }

    protected function setupEnvironmentVariables()
    {
        $this->info('Verifica variabili d\'ambiente...');

        $envFile = app()->environmentFilePath();
        $envContents = File::get($envFile);

        $variables = [
            'BROADCAST_DRIVER' => 'pusher',
            'PUSHER_APP_ID' => 'your-pusher-app-id',
            'PUSHER_APP_KEY' => 'your-pusher-key',
            'PUSHER_APP_SECRET' => 'your-pusher-secret',
            'PUSHER_APP_CLUSTER' => 'eu'
        ];

        $envUpdated = false;

        foreach ($variables as $key => $default) {
            if (!preg_match("/^{$key}=/m", $envContents)) {
                $envContents .= PHP_EOL . "{$key}={$default}";
                $envUpdated = true;
                $this->info("Aggiunta variabile {$key} al file .env");
            }
        }

        if ($envUpdated) {
            File::put($envFile, $envContents);
            $this->info('File .env aggiornato con successo.');
        } else {
            $this->info('Tutte le variabili d\'ambiente necessarie sono già configurate.');
        }
    }

    protected function checkNpmDependencies()
    {
        if (File::exists(base_path('package.json'))) {
            $packageJson = json_decode(File::get(base_path('package.json')), true);
            $dependencies = array_merge(
                $packageJson['dependencies'] ?? [],
                $packageJson['devDependencies'] ?? []
            );

            $missingDependencies = [];

            if (!isset($dependencies['laravel-echo'])) {
                $missingDependencies[] = 'laravel-echo';
            }

            if (!isset($dependencies['pusher-js'])) {
                $missingDependencies[] = 'pusher-js';
            }

            if (!empty($missingDependencies)) {
                $this->warn('Dipendenze NPM mancanti: ' . implode(', ', $missingDependencies));
                $this->info('Esegui questo comando per installarle:');
                $this->info('npm install --save ' . implode(' ', $missingDependencies));
            } else {
                $this->info('Tutte le dipendenze NPM necessarie sono già installate.');
            }
        } else {
            $this->warn('File package.json non trovato. Assicurati di installare le dipendenze frontend:');
            $this->info('npm install --save laravel-echo pusher-js');
        }
    }

    protected function registerServiceProviders()
    {
        $appConfig = config_path('app.php');

        if (File::exists($appConfig)) {
            $contents = File::get($appConfig);

            $providers = [
                'Ultra\\UploadManager\\UploadManagerServiceProvider',
                'Ultra\\UploadManager\\Providers\\BroadcastingConfigServiceProvider'
            ];

            $updated = false;

            foreach ($providers as $provider) {
                if (!str_contains($contents, $provider)) {
                    $this->info("Il provider {$provider} non è registrato in config/app.php");
                    $this->info("Aggiungi manualmente questa riga all'array 'providers':");
                    $this->line($provider . '::class,');
                    $updated = true;
                }
            }

            if (!$updated) {
                $this->info('Tutti i service provider necessari sono già registrati.');
            }
        }
    }
}
