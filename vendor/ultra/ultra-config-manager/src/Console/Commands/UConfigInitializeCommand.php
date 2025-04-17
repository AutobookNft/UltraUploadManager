<?php

/**
 * 📜 Oracode Command: UConfigInitializeCommand
 *
 * @package         Ultra\UltraConfigManager\Console\Commands
 * @version         1.1.0 // Versione incrementata per refactoring Oracode
 * @author          Fabio Cherici
 * @copyright       2024 Fabio Cherici
 * @license         MIT
 */

namespace Ultra\UltraConfigManager\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Builder as SchemaBuilder; // Per check tabella
use Psr\Log\LoggerInterface; // Per logging
use Throwable;
use Ultra\UltraConfigManager\Constants\GlobalConstants;
use Ultra\UltraConfigManager\Enums\CategoryEnum;
use Ultra\UltraConfigManager\UltraConfigManager; // Il servizio core

/**
 * 🎯 Purpose: Performs initial setup tasks for UltraConfigManager after installation
 *    and migration. Specifically, it checks if the core configuration table exists
 *    and displays a one-time message to the console regarding facade alias registration,
 *    using UCM itself to track whether the message has been shown.
 *
 * 🧱 Structure: Extends Laravel's `Illuminate\Console\Command`. Defines signature and description.
 *    The `handle` method contains the core logic, injecting necessary services.
 *
 * 🧩 Context: Executed via the Artisan console (`php artisan uconfig:initialize`). Typically
 *    run manually by the developer after installing and migrating the UCM package.
 *
 * 🛠️ Usage: `php artisan uconfig:initialize`
 *
 * 💾 State: Reads and potentially writes a specific configuration flag
 *    (`initial_publication_message`) using `UltraConfigManager` to ensure the console
 *    message is displayed only once. Checks database schema state.
 *
 * 🗝️ Key Logic:
 *    - Check if `uconfig` table exists using Schema Builder.
 *    - Use `UltraConfigManager` to get/set the `initial_publication_message` flag.
 *    - Display informational message about Facade alias to the console if flag not set.
 *    - Log actions and errors using `LoggerInterface`.
 *
 * 🚦 Signals:
 *    - Outputs informational messages, warnings, or errors to the console.
 *    - Returns `Command::SUCCESS` or `Command::FAILURE` exit codes.
 *    - Interacts with database schema and `UltraConfigManager`.
 *
 * 🛡️ Privacy (GDPR): Does not handle PII directly. Interacts with UCM which might store user IDs
 *    for the flag change audit, but this command itself doesn't provide user context.
 *    - `@privacy-safe`: Operates on system flags and console output.
 *
 * 🤝 Dependencies:
 *    - `UltraConfigManager`: To read/write the display flag configuration.
 *    - `Illuminate\Database\Schema\Builder`: To check for table existence.
 *    - `Psr\Log\LoggerInterface`: For logging.
 *    - (Implicit) Laravel Artisan console environment.
 *
 * 🧪 Testing:
 *    - Use Laravel's console testing features (`$this->artisan(...)`).
 *    - Mock dependencies (`UltraConfigManager`, `SchemaBuilder`, `LoggerInterface`).
 *    - Test case: Table doesn't exist -> returns FAILURE, shows error message.
 *    - Test case: Flag not set -> returns SUCCESS, shows info message, calls UCM set twice.
 *    - Test case: Flag already set -> returns SUCCESS, shows only completion message, no UCM set calls.
 *    - Test case: UCM throws exception -> returns FAILURE, shows error message, logs error.
 *
 * 💡 Logic:
 *    - Uses Dependency Injection in the `handle` method (supported by Laravel commands).
 *    - Removes all Facade usage.
 *    - Uses standard command output methods (`info`, `line`, `error`).
 *    - Compares flag value strictly (`=== 0` or uses boolean cast).
 *
 * @package Ultra\UltraConfigManager\Console\Commands
 */
class UConfigInitializeCommand extends Command
{
    /**
     * ✍️ The name and signature of the console command.
     * @var string
     */
    protected $signature = 'uconfig:initialize';

    /**
     * 💬 The console command description.
     * @var string
     */
    protected $description = 'Performs initial setup check and displays guidance for UltraConfigManager.';

    /**
     * ⚙️ Execute the console command.
     * Injects dependencies directly into the handle method.
     *
     * @param UltraConfigManager $uconfig The UCM service instance.
     * @param SchemaBuilder $schema Laravel Schema Builder instance.
     * @param LoggerInterface $logger PSR-3 Logger instance.
     * @return int Exit code (SUCCESS or FAILURE).
     */
    public function handle(UltraConfigManager $uconfig, SchemaBuilder $schema, LoggerInterface $logger): int
    {
        $logger->info('UCM Command: Running uconfig:initialize.');

        // 1. Check if the core table exists
        $tableName = config('uconfig.database.table', 'uconfig'); // Get table name from config if possible
        try {
            if (!$schema->hasTable($tableName)) {
                $this->error("❌ Table '{$tableName}' not found. Please run migrations before initialization (`php artisan migrate`).");
                $logger->error('UCM Command: Initialization failed - table not found.', ['table' => $tableName]);
                return self::FAILURE;
            }
        } catch (Throwable $e) {
             $this->error("🔥 Database connection error checking table '{$tableName}': {$e->getMessage()}");
             $logger->error('UCM Command: Initialization failed - DB error checking table.', ['table' => $tableName, 'error' => $e->getMessage()]);
             return self::FAILURE;
        }


        // 2. Check and display one-time message logic
        $flagKey = 'internal.ucm.initial_message_shown'; // Use a more specific internal key

        try {
            // Use boolean comparison after getting value. Default to false if not found.
            $shown = (bool) $uconfig->get($flagKey, false, true); // Use get(), default false, silent

            if (!$shown) {
                $logger->info('UCM Command: Initial message flag not set or false. Displaying message.');

                // Set flag to prevent re-display immediately (using string '0' temporarily)
                // Use system category and NO_USER for system actions
                $uconfig->set($flagKey, '0', CategoryEnum::System->value, GlobalConstants::NO_USER, false, true); // No version needed, audit useful

                // Display the message using standard console output methods
                $this->line(''); // Add some spacing
                $this->info('┌───────────────────────────────────────────────────────────────────────────┐');
                $this->info('│ UltraConfigManager Initialization Note                                    │');
                $this->info('├───────────────────────────────────────────────────────────────────────────┤');
                $this->info('│ If Facade auto-discovery fails or you prefer explicit aliases, add:       │');
                $this->line('│   \'UConfig\' => \Ultra\UltraConfigManager\Facades\UConfig::class         │');
                $this->info('│ to your \'config/app.php\' aliases array. See documentation for details.  │');
                $this->info('└───────────────────────────────────────────────────────────────────────────┘');
                $this->line('');

                // Set flag permanently (using string '1')
                $uconfig->set($flagKey, '1', CategoryEnum::System->value, GlobalConstants::NO_USER, false, true);
                $logger->info('UCM Command: Initial message displayed and flag set.', ['key' => $flagKey]);
            } else {
                 $logger->info('UCM Command: Initial message flag already set. Skipping display.');
            }

            $this->info("✅ UConfig initialization check complete.");
            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->error("🔥 Failed to run UConfig initialization logic: {$e->getMessage()}");
            $logger->error("UCM Command: Initialization failed.", ['exception' => $e::class, 'message' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}