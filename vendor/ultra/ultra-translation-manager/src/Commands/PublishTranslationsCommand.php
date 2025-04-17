<?php

namespace Ultra\TranslationManager\Commands;

use Illuminate\Console\Command;

/**
 * Command to publish the Ultra Translation Manager (UTM) configuration.
 *
 * This command allows users to publish the configuration file for the Ultra Translation Manager
 * to the application's config directory. It uses Laravel's vendor:publish command to copy the
 * configuration file tagged as 'ultra-translation-config'.
 */
class PublishTranslationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Defines the command name that can be used in the Artisan CLI.
     *
     * @var string
     */
    protected $signature = 'ultra:translations:publish';

    /**
     * The console command description.
     *
     * Provides a brief description of the command's purpose, displayed when running
     * `php artisan list` or `php artisan help ultra:translations:publish`.
     *
     * @var string
     */
    protected $description = 'Publish the Ultra Translation Manager configuration';

    /**
     * Execute the console command.
     *
     * Publishes the Ultra Translation Manager configuration file by calling the
     * `vendor:publish` command with the appropriate tag. After successful execution,
     * it displays a confirmation message to the user.
     *
     * @return void
     */
    public function handle()
    {
        // Call the vendor:publish command to publish the configuration file
        $this->call('vendor:publish', [
            '--tag' => 'ultra-translation-config',
        ]);

        // Display a success message to the user
        $this->info('Ultra Translation Manager configuration published successfully!');
    }
}
