<?php

declare(strict_types=1);

namespace Ultra\UltraLogManager;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * UltraLogManager – Centralised PSR‑3 Logger enriched with caller context.
 *
 * This service is the semantic core of logging for the Ultra ecosystem. It
 * delegates every write to an injected PSR‑3 implementation while enriching the
 * log context with the *who* (class & method) and a snapshot of the original
 * message.
 *
 * --- Core Logic ---
 * 1. Accepts any PSR‑3 compliant logger (typically Monolog)
 * 2. Reads behavioural knobs from the **ultra_log_manager** config file
 *    - `log_channel` (used as *default* channel name)
 *    - `log_backtrace_depth`
 *    - `backtrace_limit`
 * 3. Builds an enriched context array for each call (see {@link enrichContext()})
 * 4. Forwards level, message and enriched context to the underlying logger
 * 5. Caches caller information for performance across subsequent calls
 * --- End Core Logic ---
 *
 * --- GDPR Note ---
 * UltraLogManager itself does **not** ingest or persist personal data. Any
 * payload received via `$context` **must** already be sanitized or anonymised by
 * the caller. A helper `ContextSanitizer` (see README) is provided in the
 * package for common masking patterns (e.g. e‑mail, IP). Use it upstream before
 * logging to remain GDPR‑compliant.
 * --- End GDPR Note ---
 *
 * @package   Ultra\UltraLogManager
 * @author    Fabio Cherici <fabiocherici@gmail.com>
 * @license   MIT
 * @version   1.3.2‑oracode
 * @since     1.0.0
 *
 * @configReads  ultra_log_manager.log_channel
 * @configReads  ultra_log_manager.log_backtrace_depth
 * @configReads  ultra_log_manager.backtrace_limit
 */
class UltraLogManager implements LoggerInterface
{
    /** Injected PSR‑3 logger */
    protected LoggerInterface $logger;

    /** Default channel used by the injected logger */
    protected string $defaultChannel;

    /** Copy of config array read at construction */
    protected array $config;

    /** Cached [class, method] for the current call stack */
    protected ?array $callerContextCache = null;

    /**
     * Create a new UltraLogManager.
     *
     * @param LoggerInterface $logger Concrete PSR‑3 implementation already bound
     *                                to the desired channel (e.g. a Monolog
     *                                instance named "order" or "security").
     * @param array<string,mixed> $config Behaviour overrides (channel depth, etc.)
     */
    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger         = $logger;
        $this->config         = $config;
        $this->defaultChannel = $config['log_channel'] ?? $logger->getName() ?? 'ultra_log_manager';
    }

    /** @inheritDoc */
    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logger->log($level, (string) $message, $this->enrichContext($message, $context));
    }

    /** @inheritDoc */
    public function info(Stringable|string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /** @inheritDoc */
    public function error(Stringable|string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /** @inheritDoc */
    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /** @inheritDoc */
    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /** @inheritDoc */
    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /** @inheritDoc */
    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /** @inheritDoc */
    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /** @inheritDoc */
    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Build an enriched context array.
     *
     * @param Stringable|string $message  Original message from caller
     * @param array<string,mixed> $context Caller‑provided context
     * @return array<string,mixed>         Merged context with introspection data
     */
    protected function enrichContext(Stringable|string $message, array $context): array
    {
        if ($this->callerContextCache === null) {
            $this->callerContextCache = $this->getCallerContext();
        }

        [$callerClass, $callerMethod] = $this->callerContextCache;

        return $context + [
            'Class'   => $callerClass,
            'Method'  => $callerMethod,
            'Message' => (string) $message,
        ];
    }

    /**
     * Retrieve caller [class, method] using debug_backtrace().
     *
     * @return array{string,string}
     */
    protected function getCallerContext(): array
    {
        $depth        = (int) ($this->config['log_backtrace_depth'] ?? 3);
        $limit        = (int) ($this->config['backtrace_limit'] ?? 7);
        $currentDepth = $depth;

        while ($currentDepth <= $limit) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $currentDepth);
            foreach ($backtrace as $trace) {
                if (isset($trace['class']) && $trace['class'] !== self::class) {
                    return [
                        $trace['class']    ?? 'UnknownClass',
                        $trace['function'] ?? 'UnknownMethod',
                    ];
                }
            }
            $currentDepth += 2;
        }

        return ['UnknownClass', 'UnknownMethod'];
    }
}