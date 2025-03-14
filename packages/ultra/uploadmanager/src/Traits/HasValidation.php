<?php

namespace Ultra\UploadManager\Traits;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Ultra\UploadManager\Exceptions\CustomException;
use Ultra\UploadManager\Services\TestingConditionsManager;

/**
 * Trait HasValidation
 *
 * Questo trait fornisce una serie di metodi per la validazione dei file caricati,
 * tra cui controlli sulle estensioni, tipi MIME, dimensioni e struttura delle immagini.
 * È utilizzato per garantire che i file caricati rispettino i criteri di sicurezza e
 * validità richiesti dall'applicazione.
 *
 * @package App\Traits
 */
trait HasValidation
{

    /**
     * Valida un file caricato.
     *
     * @param \Illuminate\Http\UploadedFile $file Il file caricato che deve essere validato.
     * @throws \Exception Se la validazione fallisce.
     */
    protected function validateFile($file, $index = 0)
    {
        // Esegui la validazione di base del file
        $this->baseValidation($file, $index);

        // Se il file è un'immagine, valida la struttura dell'immagine
        if ($this->isImageMimeType($file)) {
            $this->validateImageStructure($file, $index);
        }

        // Valida il nome del file per garantire che rispetti le regole definite
        $this->validateFileName($file);

        // Se il file è un PDF, valida il contenuto del PDF
        // Oppure se è attivo il test per simulare un file PDF non valido
        if ($this->isPdf($file)) {
            $this->validatePdfContent($file);
        }
    }


    /**
     * Esegue la validazione di base del file.
     *
     * @param \Illuminate\Http\UploadedFile $file Il file da validare.
     * @throws \Exception Se la validazione fallisce.
     */
    protected function baseValidation($file, $index)
    {
        $channel = 'upload';
        // Inizializzazione del log
        $encodedLogParams = json_encode([
            'Trait' => 'HasValidation',
            'Method' => 'baseValidation',
        ]);


        // Recupera le estensioni consentite e la dimensione massima dal file di configurazione
        $allowedTypes = config('AllowedFileType.collection.allowed_extensions');
        $maxSize = config('AllowedFileType.collection.max_size');

        // Codici di errore
        $errorCodes = [
            'file.mimes' => 'MIME_TYPE_NOT_ALLOWED',
            'file.max' => 'MAX_FILE_SIZE',
            'file.extension' => 'INVALID_FILE_EXTENSION',
            'file.name' => 'INVALID_FILE_NAME',
            'file.structure' => 'INVALID_IMAGE_STRUCTURE',
            'file.pdf' => 'INVALID_FILE_PDF',
            'imagic_failed' => 'IMAGICK_NOT_AVAILABLE',
        ];

        // Esegui la validazione di base utilizzando il validatore di Laravel
        $validator = Validator::make(
            ['file' => $file],
            ['file' => 'file|mimes:' . implode(',', $allowedTypes) . '|max:' . $maxSize],
            $errorCodes
        );

        // Aggiungi eventuali errori di test simulati
        $this->addTestingErrors($validator, $errorCodes, $index);

        // Se la validazione fallisce, restituisce il validatore con errori
        if ($validator->fails()) {
             // Ottieni la chiave del primo errore
            $firstErrorKey = array_key_first($validator->errors()->getMessages());
            // Log::channel($channel)->error('Trait: HasValidation. Method: baseValidation. Action: Validazione di base fallita', ['firstErrorKey' => $errorCodes[$firstErrorKey]]);

            $message = $validator->errors()->first();

            // Restituisci il codice di errore associato
            throw new CustomException($errorCodes[$firstErrorKey]);
        }

        Log::channel($channel)->info('Trait: HasValidation. Method: baseValidation. Action: Validazione del file superata', [
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
        ]);
    }

    /**
     * Controlla se il file è un'immagine.
     *
     * @param \Illuminate\Http\UploadedFile $file Il file da controllare.
     * @return bool True se è un'immagine, altrimenti False.
     */
    protected function isImageMimeType($file)
    {
        $mimeType = $file->getMimeType();
        return strpos($mimeType, 'image/') === 0;
    }

    /**
     * Controlla se il file è un PDF.
     *
     * @param \Illuminate\Http\UploadedFile $file Il file da controllare.
     * @return bool True se è un PDF, altrimenti False.
     */
    protected function isPdf($file)
    {
        return $file->getMimeType() === 'application/pdf';
    }

    protected function addTestingErrors($validator, $errorCodes, $index)
    {

        if ($index !== "0"){
            return;
        }

        $channel = 'upload';
        $testConditions = [
            'MAX_FILE_SIZE' => 'file.max',
            'INVALID_FILE_EXTENSION' => 'file.extension',
            'INVALID_FILE_NAME' => 'file.name',
            'MIME_TYPE_NOT_ALLOWED' => 'file.mimes',
            'INVALID_IMAGE_STRUCTURE' => 'file.structure',
            'IMAGICK_NOT_AVAILABLE' => 'imagic_failed',
            'INVALID_FILE_PDF' => 'file.pdf',
        ];

        foreach ($testConditions as $test => $errorKey) {
            if (TestingConditionsManager::getInstance()->isTesting($test)) {
                Log::channel($channel)->error("Trait: HasValidation. Method: addTestingErrors. Action: Simulazione errore di $test");
                $validator->after(function ($validator) use ($errorCodes, $errorKey) {
                    $validator->errors()->add($errorKey, $errorCodes[$errorKey]);
                });
            }
        }
    }

    /**
     * Valida la struttura di un'immagine.
     *
     * @param \Illuminate\Http\UploadedFile $file Il file dell'immagine da validare.
     * @throws \Exception Se la struttura dell'immagine non è valida.
     */
    protected function validateImageStructure($file, $index)
    {

        // Inizializzazione del log
        $encodedLogParams = json_encode([
            'Trait' => 'HasValidation',
            'Method' => 'validateImageStructure',
        ]);

        // Controlla se Imagick è disponibile
        if (!class_exists('Imagick')) {
            Log::channel($this->channel)->error('Trait: HasValidation. Method: validateImageStructure. Action: Imagick non disponibile per la validazione delle immagini');
            throw new CustomException('IMAGICK_NOT_AVAILABLE');
        }

        // Ottiene il percorso temporaneo del file caricato
        $filePath = $file->getRealPath();

        try {
            // Utilizza Imagick per verificare che l'immagine sia valida
            $image = new \Imagick($filePath);

            // Usa pingImage per una validazione leggera
            $image->pingImage($filePath);

            Log::channel($this->channel)->info('Trait: HasValidation. Method: validateImageStructure. Action: Struttura dell\'immagine valida', ['file' => $file->getClientOriginalName()]);

        } catch (\ImagickException $e) {
            Log::channel($this->channel)->error('Trait: HasValidation. Method: validateImageStructure. Action: La struttura dell\'immagine non è valida', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            throw new CustomException('INVALID_IMAGE_STRUCTURE');
        }
    }

    /**
     * Valida il nome del file.
     *
     * @param \Illuminate\Http\UploadedFile $file Il file di cui validare il nome.
     * @throws \Exception Se il nome del file non è valido.
     */
    protected function validateFileName($file)
    {
        // Recupera il nome del file originale
        $fileName = $file->getClientOriginalName();
        // Log::channel($this->channel)->info('Trait: HasValidation. Method: validateFileName. Action: Validazione del nome del file', ['fileName' => $fileName]);

        // Lunghezza minima e massima consentita per il nome del file
        $maxLength = config('file_validation.images.max_name_length', 255);
        // Log::channel($this->channel)->info('Trait: HasValidation. Method: validateFileName. Action: Parametri di validazione del nome del file', [
        //     'maxLength' => $maxLength,
        // ]);

        $minLength = config('file_validation.min_name_length', 1);
        // log::channel($this->channel)->info('Trait: HasValidation. Method: validateFileName. Action: Parametri di validazione del nome del file', [
        //     'minLength' => $minLength,
        // ]);
        $allowedPattern = config('file_validation.allowed_name_pattern', '/^[\w\-. ]+$/');
        // Log::channel($this->channel)->info('Trait: HasValidation. Method: validateFileName. Action: Parametri di validazione del nome del file', [
        //     'allowedPattern' => $allowedPattern
        // ]);

        // Verifica che il nome del file abbia una lunghezza adeguata
        if (strlen($fileName) < $minLength || strlen($fileName) > $maxLength) {
            Log::channel($this->channel)->error('Trait: HasValidation. Method: validateFileName. Action: Nome file troppo lungo o troppo corto', [
                'fileName' => $fileName,
                'length' => strlen($fileName)
            ]);
            throw new CustomException('INVALID_FILE_NAME');
        }

        // Verifica che il nome del file non contenga caratteri non consentiti
        // Aggiungi un controllo sui caratteri problematici
        if (!preg_match($allowedPattern, $fileName)) {
            Log::channel($this->channel)->error('Trait: HasValidation. Method: validateFileName. Action: Nome file contiene caratteri non consentiti', [
                'fileName' => $fileName,
                'pattern' => $allowedPattern
            ]);
            throw new CustomException('INVALID_FILE_NAME');
        }

        // Se arriva qui, il nome del file è valido
        Log::channel('upload')->info('Trait: HasValidation. Method: validateFileName. Action: Nome file valido', ['fileName' => $fileName]);
    }


    /**
     * Valida il contenuto di un file PDF.
     *
     * @param \Illuminate\Http\UploadedFile $file Il file PDF da validare.
     * @throws \Exception Se il contenuto del PDF non è valido.
     */
    protected function validatePdfContent($file)
    {

        // Ottiene il percorso temporaneo del file PDF caricato
        $filePath = $file->getRealPath();

        // Verifica se il PDF è valido cercando le parole chiave comuni nei PDF
        if (!strpos(file_get_contents($filePath), '%PDF')) {
            Log::channel($this->channel)->error('Trait: HasValidation. Method: validatePdfContent. Action: Il file PDF non è valido', ['file' => $file->getClientOriginalName()]);
            throw new CustomException('INVALID_FILE_PDF');
        }
        Log::channel($this->channel)->info('Trait: HasValidation. Method: validatePdfContent. Action: Il contenuto del file PDF è valido', ['file' => $file->getClientOriginalName()]);
    }
}

