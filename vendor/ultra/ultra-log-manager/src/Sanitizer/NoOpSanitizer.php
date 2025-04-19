<?php 

declare(strict_types=1);

namespace Ultra\UltraLogManager\Sanitizer;

use Ultra\UltraLogManager\Contracts\ContextSanitizerInterface;

/**
 * NoOpSanitizer – intentionally leaves context untouched.
 *
 * Use as default binding to avoid breaking existing code. Swap with another
 * implementation (e.g. {@see DefaultContextSanitizer}) once you need GDPR
 * masking without refactoring callers.
 *
 * @package Ultra\UltraLogManager\Sanitizer
 * @author  Fabio Cherici
 * @version 1.0.0
 */
final class NoOpSanitizer implements ContextSanitizerInterface
{
    /** @inheritDoc */
    public function sanitize(array $context): array
    {
        return $context;
    }
}