<?php

declare(strict_types=1);

namespace Ultra\TranslationManager\Loggers;

use Psr\Log\LoggerInterface as PsrLoggerInterface; // Importa standard PSR-3
use Ultra\TranslationManager\Interfaces\LoggerInterface as UtmLoggerInterface; // Interfaccia specifica UTM

/**
 * 🎯 DefaultLogger – Oracoded UTM Default Logger (Standalone)
 *
 * Default implementation of the UTM LoggerInterface. It delegates logging calls
 * to a standard PSR-3 Logger instance injected via the constructor. This ensures
 * UTM uses the application's main logging system without depending on ULM.
 *
 * 🧱 Structure:
 * - Implements UtmLoggerInterface.
 * - Requires a PsrLoggerInterface injected via constructor.
 * - Maps UTM log methods directly to PSR-3 logger methods.
 *
 * 📡 Communicates:
 * - With the application's main PSR-3 logger instance.
 *
 * 🧪 Testable:
 * - Depends on injectable PsrLoggerInterface.
 * - No static dependencies.
 * - No dependency on UltraLogManager.
 */
final class DefaultLogger implements UtmLoggerInterface
{
    /**
     * 🧱 @dependency Injected PSR-3 Logger instance
     *
     * The application's main logger used for actual log writing.
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
     * 🪵 Log a debug message via the injected PSR-3 logger.
     *
     * @param string $message The message to log.
     * @param array<string, mixed> $context Additional context data.
     * @return void
     */
    public function debug(string $message, array $context = []): void
    {
        // Aggiungiamo un prefisso per identificare i log di UTM? Opzionale.
        // $this->logger->debug("[UTM] " . $message, $context);
        $this->logger->debug($message, $context); // Versione senza prefisso
    }

    /**
     * 🪵 Log a warning message via the injected PSR-3 logger.
     *
     * @param string $message The message to log.
     * @param array<string, mixed> $context Additional context data.
     * @return void
     */
    public function warning(string $message, array $context = []): void
    {
        // $this->logger->warning("[UTM] " . $message, $context);
        $this->logger->warning($message, $context);
    }

    /**
     * 🪵 Log an error message via the injected PSR-3 logger.
     *
     * @param string $message The message to log.
     * @param array<string, mixed> $context Additional context data.
     * @return void
     */
    public function error(string $message, array $context = []): void
    {
        // $this->logger->error("[UTM] " . $message, $context);
        $this->logger->error($message, $context);
    }

    /**
     * 🪵 Log an info message via the injected PSR-3 logger.
     *
     * @param string $message The message to log.
     * @param array<string, mixed> $context Additional context data.
     * @return void
     */
    public function info(string $message, array $context = []): void
    {
        // $this->logger->info("[UTM] " . $message, $context);
        $this->logger->info($message, $context);
    }
}