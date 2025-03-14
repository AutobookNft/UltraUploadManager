<?php

namespace Ultra\UploadManager\Jobs;

use App\Models\Teams_item;
use App\Traits\HasUtilitys;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Events\FileProcessingUpdate;
use App\Traits\HasValidation;
use Illuminate\Support\Facades\Storage;
use App\Util\FileHelper;
use Illuminate\Support\Facades\Auth;

class ProcessUploadedFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasValidation, HasUtilitys;

    /** @var \Illuminate\Http\UploadedFile File da elaborare */
    protected $filePath;

     /** @var array Array degli errori di validazione */
     public $errors = [];

     /** @var string Messaggio di errore generico */
     public $error;

     /** @var float Prezzo minimo per gli EGI */
     protected $floorPrice;

     /** @var \App\Models\Team Team corrente dell'utente
      * NOTA BENE: QUESTA VARIABILE deve essere public, altrimenti non viene settata correttamente
     */
     public $current_team;

     /** @var string ID univoco per l'upload corrente */
     protected $upload_id;

     /** @var string Nome del file attualmente in elaborazione */
     protected $currentFileName;

     /** @var int ID dell'utente corrente */
     protected $user_id;

     protected $path_image;

     public $team_id;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filePath, $path_image, $upload_id, $user_id, $team_id)
    {

        Log::channel('upload')->info('all\'interno di construct');

        $this->filePath = $filePath;
        $this->path_image = $path_image;
        $this->upload_id = $upload_id;
        $this->user_id = $user_id;
        $this->team_id = $team_id;
        $this->current_team = Auth::user()->currentTeam;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
{
    Log::channel('upload')->info('all\'interno di handle');

    try {
        // Carica il file temporaneo per la validazione e altre operazioni
        $filePath = storage_path('app/' . $this->filePath);
        Log::channel('upload')->info('Percorso del file', ['filePath' => $filePath]);

        if (!file_exists($filePath)) {
            Log::channel('upload')->error('File non trovato', ['filePath' => $filePath]);
            throw new \Exception("File non trovato: " . $filePath);
        }

        if (!is_readable($filePath)) {
            Log::channel('upload')->error('File non leggibile', ['filePath' => $filePath]);
            throw new \Exception("File non leggibile: " . $filePath);
        }

        // Usa Illuminate\Http\File per caricare il file
        $file = new \Illuminate\Http\File($filePath);
        Log::channel('upload')->info('Oggetto File creato', ['file' => $file]);

        // Crea un oggetto UploadedFile
        $uploadedFile = new \Illuminate\Http\UploadedFile(
            $file->getPathname(),
            $file->getFilename(),
            $file->getMimeType(),
            null,
            true
        );
        Log::channel('upload')->info('Oggetto UploadedFile creato', ['uploadedFile' => $uploadedFile]);

        event(new FileProcessingUpdate($this->upload_id, 'Validating file'));
        Log::channel('upload')->info('Prima della validazione del file', ['filePath' => $this->filePath]);

        $this->validateFile($uploadedFile);
        Log::channel('upload')->info('File validato', ['file' => $uploadedFile->getClientOriginalName()]);

        event(new FileProcessingUpdate($this->upload_id, 'Scanning for viruses'));
        // Logica di scansione per virus...

        event(new FileProcessingUpdate($this->upload_id, 'Saving file information'));
        $fileDetails = $this->prepareFileDetails($uploadedFile);
        Log::channel('upload')->info('Dettagli file', ['details' => $fileDetails]);

        $egiRecord = $this->createEGIRecord($fileDetails, $this->path_image);
        Log::channel('upload')->info('Record EGI creato', ['id' => $egiRecord->id]);

        event(new FileProcessingUpdate($this->upload_id, 'Saving on the store'));
        $fileToSave = $this->path_image . "/" . $egiRecord->id . "." . $fileDetails['extension'];
        Log::channel('upload')->info('File da salvare', ['file' => $fileToSave]);

        $this->saveFileToSpaces($fileToSave, $filePath, $fileDetails['extension']);
        Log::channel('upload')->info('File salvato', ['file' => $uploadedFile->getClientOriginalName()]);

        event(new FileProcessingUpdate($this->upload_id, 'File processed successfully'));

        // Elimina il file temporaneo dopo il processo
        Storage::disk('local')->delete($this->filePath);
        Log::channel('upload')->info('File temporaneo eliminato', ['filePath' => $this->filePath]);

    } catch (\InvalidArgumentException $e) {
        Log::error('Errore di validazione durante l\'elaborazione del file', ['error' => $e->getMessage()]);
        event(new FileProcessingUpdate($this->upload_id, 'File processing failed due to validation error'));
    } catch (\Exception $e) {
        Log::error('Errore generico durante l\'elaborazione del file', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        event(new FileProcessingUpdate($this->upload_id, 'File processing failed'));
    }
}



     /**
     * Prepara i dettagli del file per l'elaborazione.
     *
     * @param \Illuminate\Http\UploadedFile $file File caricato
     * @return array Dettagli del file
     */
    protected function prepareFileDetails($file)
    {

        try {

            $hash_filename = $file->hashName();
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Nome hash del file', ['hash_filename' => $hash_filename]);

            $mimeType = $file->getMimeType();
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Mime type del file', ['mimeType' => $mimeType]);

            $realPath = $file->getRealPath();
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Percorso reale del file', ['realPath' => $realPath]);

            $extension = $file->extension();
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Estensione del file', ['extension' => $extension]);

            $fileType = $this->getFileType($extension);
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Tipo di file', ['fileType' => $fileType]);

            $original_filename = $file->getClientOriginalName();
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Nome originale del file', ['original_filename' => $original_filename]);

            $crypt_filename = $this->my_advanced_crypt($original_filename, 'e');
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Nome criptato del file', ['crypt_filename' => $crypt_filename]);

            $num = FileHelper::generate_position_number($this->team_id);
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Numero di posizione', ['num' => $num]);

            $default_name = $this->generateDefaultName($num);
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: prepareFileDetails. Action: Nome predefinito', ['default_name' => $default_name]);

            return compact('hash_filename', 'extension', 'mimeType', 'fileType','realPath', 'crypt_filename', 'num', 'default_name');

        } catch (\Exception $e) {
            Log::channel('upload')->error('Errore durante la preparazione dei dettagli del file', ['error' => $e->getMessage()]);
            throw $e;

        }
    }

    /**
     * Crea un nuovo record EcoNFT nel database.
     *
     * @param array $fileDetails Dettagli del file
     * @param string $path_image Percorso base dell'immagine
     * @return Teams_item Nuovo record EcoNFT creato
     */
    protected function createEGIRecord($fileDetails, $path_image)
    {
        $EGI = new Teams_item();
        $EGI->fill([
            'user_id' => $this->user_id,
            'owner_id' => $this->user_id,
            'creator' => $this->current_team->creator,
            'owner_wallet' => $this->current_team->creator,
            'upload_id' => $this->upload_id,
            'extension' => $fileDetails['extension'],
            'file_hash' => $fileDetails['hash_filename'],
            'file_mime' => $fileDetails['mimeType'],
            'position' => $fileDetails['num'],
            'title' => $fileDetails['default_name'],
            'team_id' => $this->current_team->id,
            'file_crypt' => $fileDetails['crypt_filename'],
            'type' => $fileDetails['fileType'],
            'bind' => 0,
            'price' => $this->floorPrice,
            'floorDropPrice' => $this->floorPrice,
            'show' => true,
        ]);

        $this->setFileDimensions($EGI, $fileDetails['realPath']);
        $this->setFileSize($EGI, $fileDetails['realPath']);
        $this->setFileMediaProperties($EGI, $path_image);

        $EGI->save();

        return $EGI;
    }

        /**
     * Genera un nome predefinito per l'EGI.
     *
     * @param int $num Numero di posizione
     * @return string Nome predefinito generato
     */
    protected function generateDefaultName($num)
    {
        $first = str_pad($num, 4, '0', STR_PAD_LEFT);
        $second = str_pad(rand(0, 999), 3, '0', STR_PAD_LEFT);
        return "#{$first}{$second}";
    }


        /**
     * Salva il file nello storage degli spazi.
     *
     * @param string $path_image Percorso dell'immagine
     * @param string $tempPath Percorso temporaneo del file
     * @param string $extension Estensione del file
     * @return void
     * @throws \Exception Se si verifica un errore durante il salvataggio del file
     */
    public function saveFileToSpaces($fileToSave, $tempPath, $extension)
    {

        Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: saveFileToSpaces. Action: Salvataggio del file', ['fileToSave' => $fileToSave]);

        try {
            $contents = file_get_contents($tempPath);
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: saveFileToSpaces. Action: Contenuto del file', ['fileToSave' => $fileToSave]);

            Storage::disk('do')->put($fileToSave, $contents, 'public');
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: saveFileToSpaces. Action: File salvato', ['fileToSave' => $fileToSave]);

            Storage::disk('public')->put($fileToSave, $contents, 'public');
            Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: saveFileToSpaces. Action: File salvato', ['fileToSave' => $fileToSave]);

        } catch (\Exception $e) {
            Log::channel('upload')->error('Errore durante il caricamento del file', ['error' => $e->getMessage()]);
            throw $e;
        }
    }


     /**
     * Determina il tipo di file in base all'estensione.
     *
     * @param string $extension Estensione del file
     * @return string Tipo di file
     */
    protected function getFileType($extension)
    {
        $allowedTypes = config('AllowedFileType.collection.allowed');
        Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: getFileType. Action: Tipo di file', ['extension' => $extension]);

        return $allowedTypes[$extension] ?? 'unknown';
    }

    /**
     * Imposta le dimensioni del file nel record EcoNFT.
     *
     * @param Teams_item $ecoNFT Record EcoNFT
     * @param string $filePath Percorso del file
     * @return void
     */
    protected function setFileDimensions($EGI, $filePath)
    {
        $dimensions = getimagesize($filePath);
        Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: setFileDimensions. Action: Dimensioni del file', ['dimensions' => $dimensions]);
        $EGI->dimension = $dimensions ? "w: {$dimensions[0]} x h: {$dimensions[1]}" : 'empty';
    }

     /**
     * Imposta la dimensione del file nel record EcoNFT.
     *
     * @param Teams_item $ecoNFT Record EcoNFT
     * @param string $filePath Percorso del file
     * @return void
     */
    protected function setFileSize($EGI, $filePath)
    {
        $size = $this->formatSizeInMegabytes(filesize($filePath));
        Log::channel('upload')->info('Classe: ProcessUploadedFile. Method: setFileSize. Action: Dimensione del file', ['size' => $size]);

        $EGI->size = $size;

    }

    /**
     * Imposta le proprietà media del file nel record EcoNFT.
     *
     * @param Teams_item $EGI Record EGI
     * @param string $path_image Percorso base dell'immagine
     * @return void
     */
    protected function setFileMediaProperties($EGI, $path_image)
    {
        $isImage = $EGI->type === 'image';
        $EGI->media = !$isImage;
        $EGI->key_file = $isImage ? $EGI->id : 0;
    }

        /**
     * Formatta la dimensione del file in megabytes.
     *
     * @param int $sizeInBytes Dimensione del file in bytes
     * @return float Dimensione del file in megabytes
     */
    protected function formatSizeInMegabytes($sizeInBytes)
    {
        return round($sizeInBytes / (1024 * 1024), 2);
    }


}
