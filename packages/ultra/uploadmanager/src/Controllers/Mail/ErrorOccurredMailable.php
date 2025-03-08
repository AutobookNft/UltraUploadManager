<?php

namespace Ultra\UploadManager\Controllers\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ErrorOccurredMailable extends Mailable
{
    use Queueable, SerializesModels;

    public array $params = [];

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->params = $params;


        // Rimuovi il destinatario dall'array dei parametri
        if (isset($this->params['to'])) {
            unset($this->params['to']);
        }

    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: $this->params['subject'],
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {

        Log::channel('nftflorence')->info('classe: ErrorOccurredMailable. Metodo: content. Action: $this->errorDetails: '.json_encode($this->params));
        // Rimuovi il destinatario dall'array dei parametri
        if (isset($this->params['subject'])) {
            unset($this->params['subject']);
        }

        return new Content(
            view: 'emails.error_notification', // il nome della tua vista e-mail
            with: ['details' => $this->params]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
