<?php

declare(strict_types=1);

namespace Ultra\UltraLogManager\Sanitizer;

use Ultra\UltraLogManager\Contracts\ContextSanitizerInterface;


/**
 * DefaultContextSanitizer – basic masking of e‑mail addresses and IPs.
 *
 * --- Core Logic ---
 * 1. Iterate each value in `$context`.
 * 2. If value is e‑mail → mask local part (e.g. `u***@host`).
 * 3. If value is IPv4/IPv6 → replace last segment with `*`.
 * 4. Return sanitized array.
 *
 * --- Privacy Impact ---
 * Reduces risk of logging personal data. Not fool‑proof; customise for your
 * domain or switch to a hashing/encryption sanitizer when necessary.
 * --- End Notes ---
 *
 * @package Ultra\UltraLogManager\Sanitizer
 * @author  Fabio Cherici
 * @version 1.0.0
 */
final class DefaultContextSanitizer implements ContextSanitizerInterface
{
    /** @inheritDoc */
    public function sanitize(array $context): array
    {
        return array_map([$this, 'maskValue'], $context);
    }

    /** @param mixed $value */
    private function maskValue($value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return $this->maskEmail($value);
        }

        if (filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6)) {
            return $this->maskIp($value);
        }

        return $value;
    }

    private function maskEmail(string $email): string
    {
        [$user, $host] = explode('@', $email, 2);
        return substr($user, 0, 1) . str_repeat('*', max(strlen($user) - 1, 0)) . '@' . $host;
    }

    private function maskIp(string $ip): string
    {
        if (str_contains($ip, ':')) { // IPv6
            return preg_replace('/:[0-9a-fA-F]+$/', ':*', $ip) ?: $ip;
        }
        return preg_replace('/\.\d+$/', '.*', $ip) ?: $ip; // IPv4
    }
}