<?php

namespace Ultra\UploadManager\Logging;

use Monolog\Handler\StreamHandler;

class CustomizeFormatter
{

    /**
    * Calls the logger to customize the formatter of its handlers.
    * This method is used to update all `StreamHandlers` in the logger,
    * setting a custom formatter that includes milliseconds in the timestamp.
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

