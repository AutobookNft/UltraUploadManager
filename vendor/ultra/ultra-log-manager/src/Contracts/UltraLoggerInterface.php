<?php

namespace Ultra\UltraLogManager\Contracts;

interface UltraLoggerInterface
{
    public function log(string $level, string $type, string $message, array $context = [], ?string $channel = null, bool $debug = false): void;
    public function info(string $type, string $message, array $context = [], ?string $channel = null): void;
    public function error(string $type, string $message, array $context = [], ?string $channel = null): void;
    public function warning(string $type, string $message, array $context = [], ?string $channel = null): void;
    public function debug(string $category, string $message, array $context = []): void;
    public function critical(string $category, string $message, array $context = []): void;
}