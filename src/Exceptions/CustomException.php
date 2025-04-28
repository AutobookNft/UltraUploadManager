<?php

namespace Ultra\UploadManager\Exceptions;

use Exception;
use Illuminate\Support\Facades\Log;

class CustomException extends Exception
{
    protected string $stringCode;

    /**
     * Costruttore della CustomException.
     *
     * @param string $stringCode Codice di errore personalizzato.
     * @param \Throwable|null $previous Eccezione precedente.
     */
    public function __construct(string $stringCode, ?\Throwable $previous = null)
    {
        $this->stringCode = $stringCode;

        Log::channel('upload')->error('Errore Gestito', [
            'Class' => 'CustomException',
            'Method' => '__construct',
            'StringCode' => $stringCode,
        ]);

        parent::__construct( 'Messaggio personalizzato: '.$stringCode, 1, $previous); // Messaggio vuoto e codice 0
    }

    /**
     * Ottiene il codice di errore personalizzato.
     *
     * @return string
     */
    public function getStringCode(): string
    {
        return $this->stringCode;
    }
}
