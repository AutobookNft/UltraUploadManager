<?php

declare(strict_types=1);

namespace Ultra\UltraLogManager;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 🎯 UltraLogManager – Oracoded Logging Core
 *
 * Centralized logging manager for the Ultra ecosystem, designed to provide
 * detailed, context-enriched logs with configurable channels and caller tracking.
 * Implements PSR-3 for compatibility while preserving Ultra-specific semantics.
 *
 * 🧱 Structure:
 * - Delegates logging to an injected PSR-3 logger
 * - Enriches context with caller class, method, type, and message
 * - Caches caller context for performance
 *
 * 📡 Communicates:
 * - With external systems via the injected logger
 * - With callers by embedding their context in logs
 *
 * 🧪 Testable:
 * - Fully injectable via constructor
 * - No static dependencies
 * - All methods mockable
 */
class UltraLogManager implements LoggerInterface
{
    /**
     * 🧱 @dependency Injected PSR-3 logger
     *
     * Core logging implementation, typically Monolog.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * 🧱 @config Default log channel
     *
     * Fallback channel for logging operations.
     *
     * @var string
     */
    protected string $defaultChannel;

    /**
     * 🧱 @config Configuration array
     *
     * Holds settings like backtrace depth and channel.
     *
     * @var array
     */
    protected array $config;

    /**
     * 🧱 @cache Caller context
     *
     * Cached caller class and method to avoid repeated backtrace calls.
     *
     * @var array|null
     */
    protected ?array $callerContextCache = null;

    /**
     * 🎯 Initialize the logger
     *
     * Sets up the logger with an injected PSR-3 implementation and configuration.
     *
     * 🧱 Structure:
     * - Stores logger and config
     * - Sets default channel from config
     *
     * 📡 Communicates:
     * - None directly, prepares state
     *
     * 🧪 Testable:
     * - Dependencies injectable
     * - No side effects
     *
     * @param LoggerInterface $logger Injected logger instance
     * @param array $config Configuration (channel, backtrace settings)
     */
    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        $this->config = $config;
        $this->defaultChannel = $config['log_channel'] ?? 'error_manager';
    }

    /**
     * 🎯 Log a message at a specified level
     *
     * Core logging method that enriches context with caller info, type, and message.
     *
     * 🧱 Structure:
     * - Builds enriched context
     * - Delegates to injected logger
     *
     * 📡 Communicates:
     * - Logs via the injected logger
     *
     * 🧪 Testable:
     * - Logger mockable
     * - Context enrichment isolabile
     *
     * @param mixed $level Log level (e.g., 'info', 'error')
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $enrichedContext = $this->enrichContext($message, $context);
        $this->logger->log($level, $message, $enrichedContext);
    }

    /**
     * 🎯 Log an info-level message
     *
     * Logs informational events with enriched context.
     *
     * 🧱 Structure:
     * - Delegates to `log` with 'info' level
     *
     * 📡 Communicates:
     * - Via injected logger
     *
     * 🧪 Testable:
     * - Fully mockable
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * 🎯 Log an error-level message
     *
     * Logs error events with enriched context.
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * 🎯 Log a warning-level message
     *
     * Logs warning events with enriched context.
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * 🎯 Log a debug-level message
     *
     * Logs debug events with enriched context.
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * 🎯 Log a critical-level message
     *
     * Logs critical events with enriched context.
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * 🎯 Log an emergency-level message
     *
     * Logs emergency events with enriched context.
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * 🎯 Log an alert-level message
     *
     * Logs alert events with enriched context.
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * 🎯 Log a notice-level message
     *
     * Logs notice events with enriched context.
     *
     * @param string|\Stringable $message Message to log
     * @param array $context Additional context data
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * 🎯 Enrich log context with caller and message information
     *
     * Adds caller class, method, and original message to the context for detailed logging.
     *
     * 🧱 Structure:
     * - Uses cached caller context
     * - Merges with provided context
     *
     * 📡 Communicates:
     * - Provides enriched data to logger
     *
     * 🧪 Testable:
     * - Pure function
     * - Caller context mockable via config
     *
     * @param string|\Stringable $message Original message
     * @param array $context Provided context data
     * @return array Enriched context
     */
    protected function enrichContext(string|\Stringable $message, array $context): array
    {
        if ($this->callerContextCache === null) {
            $this->callerContextCache = $this->getCallerContext();
        }

        [$callerClass, $callerMethod] = $this->callerContextCache;
        $context['Class'] = $callerClass;
        $context['Method'] = $callerMethod;
        $context['Message'] = (string) $message;

        // 'Type' rimane nel context se il chiamante lo fornisce, altrimenti non lo aggiungiamo
        // Nessuna azione necessaria: $context['Type'] è già preservato se presente

        return $context;
    }

    /**
     * 🎯 Retrieve caller context from backtrace
     *
     * Identifies the calling class and method for context enrichment.
     *
     * 🧱 Structure:
     * - Uses configurable backtrace depth
     * - Filters out internal calls
     *
     * 📡 Communicates:
     * - Returns caller info for `enrichContext`
     *
     * 🧪 Testable:
     * - Config mockable
     * - Backtrace isolabile
     *
     * @return array [class, method]
     */
    protected function getCallerContext(): array
    {
        $initialDepth = $this->config['log_backtrace_depth'] ?? 3;
        $backtraceLimit = $this->config['backtrace_limit'] ?? 7;
        $currentDepth = $initialDepth;

        while ($currentDepth <= $backtraceLimit) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $currentDepth);
            foreach ($backtrace as $trace) {
                if (isset($trace['class']) && $trace['class'] !== self::class) {
                    return [
                        $trace['class'] ?? 'UnknownClass',
                        $trace['function'] ?? 'UnknownMethod',
                    ];
                }
            }
            $currentDepth += 2;
        }
        return ['UnknownClass', 'UnknownMethod'];
    }
}