<?php

// app/Logging/CustomFormatter.php
namespace Ultra\UploadManager\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

/**
 * Class CustomFormatter
 *
 * This formatter extends Monolog's LineFormatter to include microseconds in the log timestamp.
 * This is useful for tracking highly granular log events in real-time applications.
 *
 * @package Fabio\ErrorManager\Logging
 */
class CustomFormatter extends LineFormatter
{
    public function format(LogRecord $record): string
    {
        // Non modificare il datetime del record originale
        // Usa il datetime esistente nel formato
        $output = parent::format($record);

        // Se vuoi includere i microsecondi nel formato di output
        // puoi modificare il formato nel costruttore
        return $output;
    }

    // Opzionale: Sovrascrivi il costruttore per personalizzare il formato
    public function __construct(
        ?string $format = null,
        ?string $dateFormat = "Y-m-d H:i:s.u",
        bool $allowInlineLineBreaks = false,
        bool $ignoreEmptyContextAndExtra = false
    ) {
        parent::__construct($format, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
    }
}

