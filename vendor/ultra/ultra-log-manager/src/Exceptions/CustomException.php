<?php

declare(strict_types=1);

namespace Ultra\UltraLogManager\Exceptions;

use Exception;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 🎯 CustomException – Oracoded Ultra Logging Exception
 *
 * Custom exception class for the Ultra ecosystem, designed to carry a string-based
 * error code and log its occurrence with enriched context.
 *
 * 🧱 Structure:
 * - Extends base Exception for standard compatibility
 * - Stores a string error code ($stringCode)
 * - Logs instantiation details via an injected logger
 *
 * 📡 Communicates:
 * - With logging system via injected LoggerInterface
 * - With callers via $stringCode accessor
 *
 * 🧪 Testable:
 * - Logger injectable for mocking
 * - No static dependencies
 */
class CustomException extends Exception
{
    /**
     * 🧱 @property String error code
     *
     * Unique identifier for the error, used for logging and debugging.
     *
     * @var string
     */
    protected string $stringCode;

    /**
     * 🧱 @dependency Logger instance
     *
     * Handles logging of exception instantiation.
     *
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * 🎯 Construct a custom exception
     *
     * Initializes the exception with a string code, logs its occurrence,
     * and chains any previous exception.
     *
     * 🧱 Structure:
     * - Sets $stringCode and $logger
     * - Logs error details with context
     * - Calls parent constructor with $stringCode as message
     *
     * 📡 Communicates:
     * - Logs via injected logger
     *
     * 🧪 Testable:
     * - Logger mockable
     * - Parameters injectable
     *
     * @param string $stringCode Custom error code
     * @param LoggerInterface $logger Logger for error tracking
     * @param Throwable|null $previous Previous exception for chaining
     */
    public function __construct(string $stringCode, LoggerInterface $logger, ?Throwable $previous = null)
    {
        $this->stringCode = $stringCode;
        $this->logger = $logger;

        $this->logger->error('Errore Gestito', [
            'Class' => self::class,
            'Method' => '__construct',
            'StringCode' => $stringCode,
        ]);

        parent::__construct($stringCode, 1, $previous);
    }

    /**
     * 🎯 Retrieve the custom error code
     *
     * Provides access to the string-based error identifier.
     *
     * 🧱 Structure:
     * - Simple getter for $stringCode
     *
     * 📡 Communicates:
     * - Returns code to callers
     *
     * 🧪 Testable:
     * - Pure function, no side effects
     *
     * @return string The custom error code
     */
    public function getStringCode(): string
    {
        return $this->stringCode;
    }
}