<?php

namespace Ultra\UltraLogManager\Facades;

use Illuminate\Support\Facades\Facade;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * UltraLog – Test-safe Logging Facade
 *
 * 🎯 Facade for routing logs through custom Ultra channels and formats.
 * 🧪 Fully test-compatible via internal fallback to NullLogger when Facade root is not set.
 * 🔐 Used throughout Ultra ecosystem as a semantic logging layer.
 * 📡 Compliant with PSR-3 interfaces when fallback is invoked.
 *
 * @see \Ultra\UltraLogManager\UltraLogManager
 */
class UltraLog extends Facade
{
    /**
     * 🧱 Accessor key used to resolve from Laravel container
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'ultralogmanager';
    }

    /**
     * 🧪 @test
     * 🧷 @fallback
     * Get the logger instance, falling back to NullLogger in test contexts.
     *
     * Resolves the current facade root. If the resolved instance does not implement
     * LoggerInterface (e.g., during early lifecycle or in test), a NullLogger is returned.
     *
     * @return LoggerInterface
     */
    protected static function safeLogger(): LoggerInterface
    {
        $root = static::getFacadeRoot();

        return $root instanceof LoggerInterface
            ? $root
            : new NullLogger();
    }

    /**
     * 🔄 Semantic Level: Custom multi-context logging
     *
     * @param string $level
     * @param string $type
     * @param string $message
     * @param array $context
     * @param string|null $channel
     * @param bool $debug
     * @return void
     */
    public static function log(string $level, string $type, string $message, array $context = [], ?string $channel = null, bool $debug = false): void
    {
        static::safeLogger()->log($level, "[$type] $message", $context);
    }

    /** 🪵 Info-level semantic log */
    public static function info(string $type, string $message, array $context = [], ?string $channel = null): void
    {
        static::safeLogger()->info("[$type] $message", $context);
    }

    /** ⚠️ Warning-level semantic log */
    public static function warning(string $type, string $message, array $context = [], ?string $channel = null): void
    {
        static::safeLogger()->warning("[$type] $message", $context);
    }

    /** 🚨 Error-level semantic log */
    public static function error(string $type, string $message, array $context = [], ?string $channel = null): void
    {
        static::safeLogger()->error("[$type] $message", $context);
    }

    /** 🧪 Debug-level diagnostic log */
    public static function debug(string $category, string $message, array $context = []): void
    {
        static::safeLogger()->debug("[$category] $message", $context);
    }

    /** 🔥 Critical-level semantic alert */
    public static function critical(string $category, string $message, array $context = []): void
    {
        static::safeLogger()->critical("[$category] $message", $context);
    }
}
