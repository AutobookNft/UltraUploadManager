<?php

declare(strict_types=1);

namespace Ultra\UltraLogManager\Contracts;

/**
 * Contract for sanitizing log context arrays.
 *
 * Implementations MUST ensure they do not introduce side effects: they receive
 * an array and return a new sanitized array, leaving the original untouched.
 *
 * --- Core Logic ---
 * 1. Accept `$context` potentially containing personal data.
 * 2. Remove, mask or hash sensitive values according to implementation rules.
 * 3. Return a **new** array safe for logging.
 * --- End Core Logic ---
 *
 * @package   Ultra\UltraLogManager\Contracts
 * @author    Fabio Cherici <fabiocherici@gmail.com>
 * @license   MIT
 * @version   1.0.0
 */
interface ContextSanitizerInterface
{
    /**
     * Sanitize context before it reaches the logger.
     *
     * @param array<string, mixed> $context Raw context (may contain PII).
     * @return array<string, mixed> Sanitized copy safe to log.
     */
    public function sanitize(array $context): array;
}