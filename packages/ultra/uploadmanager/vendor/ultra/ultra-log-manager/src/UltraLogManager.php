<?php

namespace Ultra\UltraLogManager;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Ultra\UltraConfigManager\UltraConfigManager;
use Throwable;

class UltraLogManager
{
    protected string $routeChannel;
    protected ?array $callerContextCache = null;

    public function __construct()
    {
        $this->initialize();
    }

    /**
     * Initialize log channel using ConfigManager.
     *
     * @return void
     */
    private function initialize(): void
    {
        try {
            // Retrieve the log channel from the config using ConfigManager
            $this->routeChannel = UltraConfigManager::getConfig('log_channel', 'error_manager');
        } catch (Throwable $e) {
            // Fall back to a default channel if ConfigManager fails
            $this->routeChannel = 'stack';
        }
    }

    /**
     * Log a message.
     *
     * @param string $level The log level (info, error, warning, etc.)
     * @param string $type The action being logged
     * @param string $message The message to log
     * @param array $context Additional context information to log
     * @param string|null $channel Optional log channel to use
     * @param bool $debug Optional flag to force logging even in production
     * @return void
     */
    public function log(string $level, string $type, string $message, array $context = [], ?string $channel = null, bool $debug = false): void
    {
        // Skip logging if we are in production environment and debug flag is not set
        if (App::environment('production') && !$debug) {
            return;
        }

        // Determine the channel to use
        $logChannel = $channel ?? $this->routeChannel;

        // Verify if the given channel is valid, fallback to 'stack' if it's not
        if (!$this->isValidChannel($logChannel)) {
            $logChannel = $this->routeChannel;
        }

        // Get the calling class and method, using the cache if available
        if ($this->callerContextCache === null) {
            $this->callerContextCache = $this->getCallerContext();
        }

        // Extract caller class and method from the cache
        [$callerClass, $callerMethod] = $this->callerContextCache;
        $context['Class'] = $callerClass;
        $context['Method'] = $callerMethod;
        $context['Type'] = $type;
        $context['Message'] = $message;

        // Create log with specified channel or fall back to 'stack'
        try {
            Log::channel($logChannel)->{$level}('', $context);
        } catch (Throwable $e) {
            // If the channel is invalid, use Laravel's default channel
            Log::channel('stack')->{$level}('', array_merge($context, [
                'FallbackChannel' => true, // Indication that fallback channel was used
            ]));
        }
    }

    /**
     * Validate if the given channel exists.
     *
     * @param string $channel The log channel to validate
     * @return bool
     */
    private function isValidChannel(string $channel): bool
    {
        try {
            Log::channel($channel);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get the calling class and method.
     *
     * @return array
     */
    private function getCallerContext(): array
    {
        // Recupera la profondità dal ConfigManager
        $initialDepth = UltraConfigManager::getConfig('log_backtrace_depth', 3);
        $backtrace_limit = UltraConfigManager::getConfig('backtrace_limit', 5);
            
        // Partiamo con la profondità configurata
        $currentDepth = $initialDepth;
    
        while ($currentDepth <= $backtrace_limit) {
            // Otteniamo il backtrace con la profondità attuale
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $currentDepth);
    
            // Prova a trovare il contesto chiamante
            foreach ($backtrace as $trace) {
                if (isset($trace['class']) && !str_contains($trace['class'], 'Facade') && $trace['class'] !== self::class) {
                    return [
                        $trace['class'] ?? 'UnknownClass',
                        $trace['function'] ?? 'UnknownMethod',
                    ];
                }
            }
    
            // Incrementa la profondità e ripeti la ricerca
            $currentDepth += 2;
        }
    
        // Fallback nel caso non sia possibile identificare il chiamante
        return ['UnknownClass', 'UnknownMethod'];
    }
    

    /**
     * Log a message with info level.
     *
     * @param string $type The action being logged
     * @param string $message The message to log
     * @param array $context Additional context information to log
     * @param string|null $channel Optional log channel to use
     * @return void
     */
    public function info(string $type, string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('info', $type, $message, $context, $channel);
    }

    /**
     * Log a message with error level.
     *
     * @param string $type The action being logged
     * @param string $message The message to log
     * @param array $context Additional context information to log
     * @param string|null $channel Optional log channel to use
     * @return void
     */
    public function error(string $type, string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('error', $type, $message, $context, $channel);
    }

    /**
     * Log a message with warning level.
     *
     * @param string $type The action being logged
     * @param string $message The message to log
     * @param array $context Additional context information to log
     * @param string|null $channel Optional log channel to use
     * @return void
     */
    public function warning(string $type, string $message, array $context = [], ?string $channel = null): void
    {
        $this->log('warning', $type, $message, $context, $channel);
    }
}
