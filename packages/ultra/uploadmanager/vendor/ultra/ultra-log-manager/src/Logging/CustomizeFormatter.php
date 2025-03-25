<?php

namespace Ultra\UltraLogManager\Logging;

use Monolog\Handler\StreamHandler;

class CustomizeFormatter
{
    /**
     * Customize the formatter for each StreamHandler in the logger.
     *
     * @param \Monolog\Logger $logger The logger to apply the custom formatter to.
     * @return void
     */
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof StreamHandler) {
                $handler->setFormatter(new CustomFormatter());
            }
        }
    }
}
