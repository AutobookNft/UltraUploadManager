<?php

namespace Ultra\UltraLogManager\Logging;

use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

/**
 * Class CustomFormatter
 *
 * This formatter extends Monolog's LineFormatter to include microseconds in the log timestamp.
 * This is useful for tracking highly granular log events in real-time applications.
 */
class CustomFormatter extends LineFormatter
{
    public function format(LogRecord $record): string
    {
        // Update the datetime format to include microseconds
        $record = $record->with(datetime: $record->datetime->format('Y-m-d H:i:s.u'));

        // Call the parent formatter with the updated record
        return parent::format($record);
    }
}
