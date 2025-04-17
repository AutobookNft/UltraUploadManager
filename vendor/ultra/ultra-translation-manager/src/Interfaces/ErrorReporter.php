<?php


namespace Ultra\TranslationManager\Interfaces;

interface ErrorReporter
{
    /**
     * Report an error with optional context and exception.
     *
     * @param string $errorCode The error code identifier
     * @param array $context Additional context data
     * @param ?\Throwable $exception The related exception, if any
     * @return void
     */
    public function report(string $errorCode, array $context = [], ?\Throwable $exception = null): void;
}
