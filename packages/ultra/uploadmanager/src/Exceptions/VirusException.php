<?php

namespace Ultra\UploadManager\Exceptions;

use Exception;

class VirusException extends Exception
{
    protected $virusFoundCode;
    protected $statusScan;
    protected $found;

    public function __construct($message, $virusFoundCode, $statusScan, $responseCode, $virusFound)
    {
        $this->virusFoundCode = $virusFoundCode;
        $this->statusScan = $statusScan;
        $this->found = $virusFound;

        // Passa il codice di risposta al costruttore di Exception
        parent::__construct($message, $responseCode, null);
    }

    public function getVirusFoundCode()
    {
        return $this->virusFoundCode;
    }

    public function getStatusScan()
    {
        return $this->statusScan;
    }

    public function getResponseCode()
    {
        return $this->getCode(); // Usa il metodo integrato per ottenere il codice
    }

    public function getVirusFound()
    {
        return $this->found;
    }
}
