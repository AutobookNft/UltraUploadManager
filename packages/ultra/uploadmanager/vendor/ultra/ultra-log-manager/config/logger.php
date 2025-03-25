<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Funzione per restituire un'istanza di Logger.
 *
 * @return Logger
 */
function getLogger(): Logger
{
    // Creiamo il logger
    $log = new Logger('ultra_admin_log');

    // Aggiungiamo un handler: i log verranno scritti nel file ultra_admin.log
    $log->pushHandler(new StreamHandler(__DIR__ . '/logs/ultra_admin.log', Logger::DEBUG));

    return $log;
}
