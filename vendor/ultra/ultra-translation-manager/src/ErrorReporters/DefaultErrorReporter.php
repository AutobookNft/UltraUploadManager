<?php

declare(strict_types=1);

namespace Ultra\TranslationManager\ErrorReporters;

use Psr\Log\LoggerInterface as PsrLoggerInterface; // Importa standard PSR-3
use Ultra\TranslationManager\Interfaces\ErrorReporter as UtmErrorReporterInterface;
use Throwable;

/**
 * 🎯 DefaultErrorReporter – Oracoded UTM Default Error Reporting (Standalone)
 *
 * Default implementation of the UTM ErrorReporter interface. It reports errors
 * by logging them (typically as warnings) using an injected PSR-3 Logger instance.
 * This ensures errors are logged via the application's standard logging system.
 *
 * 🧱 Structure:
 * - Implements UtmErrorReporterInterface.
 * - Requires a PsrLoggerInterface injected via constructor.
 * - Formats the error message and context before logging.
 *
 * 📡 Communicates:
 * - With the application's main PSR-3 logger instance.
 *
 * 🧪 Testable:
 * - Depends on injectable PsrLoggerInterface.
 * - No static dependencies.
 * - No dependency on UltraLogManager.
 */
final class DefaultErrorReporter implements UtmErrorReporterInterface
{
    /**
     * 🧱 @dependency Injected PSR-3 Logger instance
     *
     * The application's main logger used for reporting errors.
     *
     * @var PsrLoggerInterface
     */
    protected readonly PsrLoggerInterface $logger;

    /**
     * 🎯 Constructor: Injects the PSR-3 Logger dependency.
     *
     * @param PsrLoggerInterface $logger The application's logger instance.
     */
    public function __construct(PsrLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 🚨 Report an error by logging it as a warning via the injected PSR-3 logger.
     *
     * @param string $errorCode The error code identifier (e.g., 'TRANSLATION_PATH_INVALID').
     * @param array<string, mixed> $context Additional context data.
     * @param Throwable|null $exception The related exception, if any.
     * @return void
     */
    public function report(string $errorCode, array $context = [], ?Throwable $exception = null): void
    {
        // Format message clearly identifying it comes from UTM
        $message = "[UTM Error] Code: {$errorCode}";

        // Include exception message in context if available
        if ($exception) {
            $context['exception_message'] = $exception->getMessage();
            // Consider adding trace in debug mode? For now, keep it simple.
            // $context['exception_trace'] = $exception->getTraceAsString();
        }

        // Log using the injected PSR-3 logger instance (using warning level by default)
        $this->logger->warning($message, $context);
    }
}