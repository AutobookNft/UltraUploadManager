<?php

namespace Ultra\UploadManager\Traits;

use App\Exceptions\ErrorDispatcher;
use App\Exceptions\ErrorResult;
use App\Mail\ErrorOccurredMailable;
use App\Services\TestingConditionsManager;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

trait HasUtilitys
{
    /**
     * Get the disk that profile photos should be stored on.
     */
    protected function folderRoot(): string
    {
        return isset($_ENV['FOLDER_ROOT']) ? $_ENV['FOLDER_ROOT'] : 'storage';
    }

    /**
     * Encrypt and decrypt
     *
     * @author Nazmul Ahsan <n.mukto@gmail.com>
     *
     * @link http://nazmulahsan.me/simple-two-way-function-encrypt-decrypt-string
     *
     * @param  string  $string  string to be encrypted/decrypted
     * @param  string  $action  what to do with this? e for encrypt, d for decrypt
     */
    public function my_simple_crypt($string, $action = 'e'): bool|string
    {
        // you may change these values to your own
        $secret_key = 'my_simple_secret_key';
        $secret_iv = 'my_simple_secret_iv';

        $output = false;
        $encrypt_method = 'AES-256-CBC';
        $key = hash('sha256', $secret_key);
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        if ($action == 'e') {
            $output = base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv));
        } elseif ($action == 'd') {
            $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
        }

        return $output;
    }

    /**
     * Cripta o decripta una stringa utilizzando la chiave di crittografia fornita.
     *
     * @param  string  $string  La stringa da criptare o decriptare.
     * @param  string  $action  L'azione da eseguire: 'e' per criptare, 'd' per decriptare.
     * @return bool|string Restituisce la stringa criptata/decriptata o false in caso di errore.
     *
     * @throws EnvironmentIsBrokenException Se l'ambiente di esecuzione non supporta la crittografia sicura.
     * @throws BadFormatException Se la chiave fornita ha un formato errato.
     * @throws WrongKeyOrModifiedCiphertextException Se la chiave fornita non è valida o se la stringa criptata è stata modificata.
     *
     * Note: Assicurarsi di non perdere la chiave di crittografia. Senza di essa, non sarà possibile decriptare i dati.
     */
    public function my_advanced_crypt($string, $action = 'e'): bool|string
    {

        if (empty($string) || empty($action) || $string == null || $action == null) {
            return false;
        }

        // Utilizza una chiave esistente dalla sua rappresentazione ASCII safe
        $key_ascii = config('app.data_crypto_key');  // Recupera questa chiave da un posto sicuro (es. .env)

        try {

            $key = Key::loadFromAsciiSafeString($key_ascii);

        } catch (BadFormatException $e) {

            $yourErrorDetails = [
                'Error' => 'Error n. 0001.',
                'Trait' => 'HasUtility',
                'Method' => 'my_advanced_crypt',
                'Situation' => $e->getMessage(),
                'SystemError' => $e->getCode(),
            ];

            Log::channel('upload')->error(json_encode($yourErrorDetails));

            return false;
        }

        if ($action == 'e') {

            try {

                return Crypto::encrypt($string, $key);

            } catch (EnvironmentIsBrokenException|Exception $e) {
                // Questa eccezione si verifica se l'ambiente di esecuzione non è sicuro.
                Log::channel('upload')->error(json_encode([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ]));
                return false;
            }

        } elseif ($action == 'd') {

            try {

                return Crypto::decrypt($string, $key);

            } catch (WrongKeyOrModifiedCiphertextException|EnvironmentIsBrokenException|Exception $e) {
                Log::channel('upload')->error(json_encode([
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                    ]));
                return false;
            }
        }

        return false;
    }

    /**
     * Get the disk that profile photos should be stored on.
     */
    protected function defProfilePhotoDisk(): string
    {
        return isset($_ENV['VAPOR_ARTIFACT_NAME']) ? 's3' : config('jetstream.profile_photo_disk', 'public');
    }

    public function formatPriceInAlgo(int $amount): string
    {
        return number_format($amount, 2, '.', ',').' Algo';
    }

    /**
     * Format the size in megabytes.
     *
     * @param  int  $sizeInBytes  The size in bytes to be formatted.
     * @return string The formatted size in megabytes.
     */
    public function formatSizeInMegabytes(int $sizeInBytes): string
    {
        $megabytes = $sizeInBytes / (1024 * 1024);

        return number_format($megabytes, 2).' MB';
    }

    /**
     * Gestisce e aggiorna la lingua dell'utente basata sulla lingua locale.
     *
     * 1. Legge la lingua locale.
     * 2. Aggiorna la lingua nella sessione.
     * 3. Imposta un cookie per conservare le preferenze di lingua dell'utente per 30 giorni.
     *
     * @return string Ritorna la lingua attualmente impostata.
     */
    public function languageHandling(): string
    {
        // 1. Legge la lingua locale.
        $language = App::getLocale();

        // 2. Aggiorna la lingua nella sessione.
        session()->put('locale', $language);

        // 3. Imposta un cookie per conservare le preferenze di lingua dell'utente.
        // (Nota: è importante gestire il consenso dell'utente per i cookie in conformità con il GDPR)
        Cookie::queue('language', $language, 60 * 24 * 30); // Il cookie scade dopo 30 giorni.

        return $language;
    }

    /**
     * Send an error email.
     *
     * @param  array  $yourErrorDetails  Details of the error to be sent.
     * @param  string|null  $emailAddress  Optional email address to send the error to.
     * @return bool Returns true if the email is sent successfully and false if not.
     */
    public function sendErrorEmail(array $yourErrorDetails, ?string $emailAddress = null): bool
    {
        if ($emailAddress === null) {
            $emailAddress = config('app.errors_email');
        }

        try {
            Mail::to($emailAddress)->send(new ErrorOccurredMailable($yourErrorDetails));

            // L'e-mail è stata inviata con successo
            return true;

        } catch (\Exception $e) {

            // L'e-mail non è stata inviata
            $yourErrorDetails = [
                'error' => 'Error n. 999.',
                'Trait' => 'HasUtility',
                'method' => 'sendErrorEmail',  // nome del metodo corretto
                'situation' => $e->getMessage(),
            ];
            Log::channel('nft_transaction')->error(json_encode($yourErrorDetails));

            return false;
        }
    }

    private function generateFakeAlgorandAddress(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $address = '';
        for ($i = 0; $i < 36; $i++) {
            $address .= $characters[rand(0, strlen($characters) - 1)];
        }

        return $address;
    }

    /**
     * Verifica l'esistenza di una directory e ne garantisce i permessi corretti.
     *
     * Questa funzione controlla se la directory specificata esiste; se non esiste, tenta di crearla
     * con i permessi forniti come argomento. In seguito, controlla i permessi attuali della directory
     * e, se necessario, li modifica. Se l'operazione fallisce (creazione o modifica dei permessi),
     * restituisce un codice di errore predefinito.
     *
     * @param string $directory           Il percorso della directory da verificare o creare.
     * @param int $requiredPermissions    I permessi da applicare alla directory (default: 0755).
     *
     * @return string                     Restituisce `NOT_ERROR` se la directory esiste o viene creata/modificata con successo,
     *                                    altrimenti un codice di errore (stringa) definito in 'error_constants'.
     *
     * Logica:
     * - Se la directory non esiste, tenta di crearla.
     * - Se i permessi della directory non corrispondono a quelli richiesti, tenta di modificarli.
     * - Se fallisce in una di queste operazioni, registra un errore nei log e restituisce un codice di errore.
     *
     * Logging:
     * - Registra nei log il successo o il fallimento di ogni azione, inclusi classe, metodo e azione specifica
     *   per un migliore debugging nei sistemi di log centralizzati.
     *
     * Esempio d'uso:
     * $result = ensureDirectoryPermissions('/path/to/directory');
     * if ($result !== true) {
     *     // Gestire l'errore basato sul codice restituito
     * }
     */

    function ensureDirectoryPermissions($directory, $requiredPermissions = 0755): string {

        $logParams = [
            'Traits' => 'HasUtility',
            'Method' => 'ensureDirectoryPermissions',
        ];

        Log::channel('upload')->info(json_encode($logParams), [
            'Action' => 'Tentativo di creazione della directory',
            'directory' => $directory]);

        // Simula un errore durante la creazione della directory
        if (TestingConditionsManager::getInstance()->isTesting('UNABLE_TO_CREATE_DIRECTORY')) {

            Log::channel('upload')->error(json_encode($logParams), [
                'Action' => 'Simulazione di errore durante la creazione della directory',
                'directory' => $directory]);

            return 'UNABLE_TO_CREATE_DIRECTORY';
        }

        // Verifica se la directory esiste
        if (!file_exists($directory)) {
            // La directory non esiste, tenta di crearla
            try {
                if (!mkdir($directory, $requiredPermissions, true)) {

                    Log::channel('upload')->error(json_encode($logParams), [
                        'Action' => 'Impossibile creare la directory',
                        'directory' => $directory]);

                    return 'UNABLE_TO_CREATE_DIRECTORY';

                }
            } catch (\Exception $e) {

                Log::channel('upload')->error(json_encode($logParams), [
                    'Action' => 'Impossibile creare la directory',
                    'directory' => $directory]);

                return 'UNABLE_TO_CREATE_DIRECTORY';

            }
        }

        // Simula un errore durante la modifica dei permessi della directory
        if (TestingConditionsManager::getInstance()->isTesting('UNABLE_TO_CHANGE_PERMISSIONS')) {

            Log::channel('upload')->error(json_encode($logParams), [
                'Action' => 'Simulazione di errore durante la modifica dei permessi della directory',
                'directory' => $directory]);

            return 'UNABLE_TO_CHANGE_PERMISSIONS';

        }

        // Controlla i permessi attuali
        $currentPermissions = fileperms($directory) & 0777;
        if ($currentPermissions !== $requiredPermissions) {
            // I permessi della directory non corrispondono a quelli richiesti, tenta di modificarli
            Log::channel('upload')->error(json_encode($logParams), [
                'Action' => 'Modifica dei permessi della directory necessaria',
                'currentPermissions' => $currentPermissions,
                'directory' => $directory]);

            try {
                if (!chmod($directory, $requiredPermissions)) {

                    Log::channel('upload')->error(json_encode($logParams), [
                        'Action' => ' Impossibile cambiare i permessi della directory',
                        'currentPermissions' => $currentPermissions,
                        'directory' => $directory]);

                return 'UNABLE_TO_CHANGE_PERMISSIONS';

                }
            } catch (\Exception $e) {

                Log::channel('upload')->error(json_encode($logParams), [
                    'Action' => 'Errore durante la modifica dei permessi',
                    'error' => $e->getMessage(),
                    'directory' => $directory]);

                return 'UNABLE_TO_CHANGE_PERMISSIONS';
            }
        }

        Log::channel('upload')->info(json_encode($logParams), [
            'Action' => 'Directory esistente e permessi corretti',
            'directory' => $directory,
            'codeError' => 'NOT_ERROR']);

        return 'NOT_ERROR';
    }

    /**
    * Cambia i permessi di un file o di una directory.
    *
    * @param string $path
    * @param string $type ('file' o 'directory')
    * @return bool True se i permessi sono stati cambiati con successo, False altrimenti
    */
    private function changePermissions($path, $type)
    {

        $logParams = [
            'Traits' => 'HasUtility',
            'Method' => 'changePermissions',
        ];

        try {
            chmod($path, $type === 'file' ? 0664 : 0775);

            Log::channel('upload')->info(json_encode($logParams), [
                'Action' => 'Permessi cambiati con successo su ' . $type,
                'path' => $path]);

            return true;

        } catch (Exception $e) {

            Log::channel('upload')->error(json_encode($logParams), [
                'Action' => 'Errore cambiando permessi su ' . $type,
                'error' => $e->getMessage(),
                'path' => $path]);

            return false;

        }
    }

    /**
     * Gestisce l'eliminazione e ricreazione della directory.
     *
     * @param string $path
     * @return void
     * @throws Exception
     */
    private function handleDirectoryError($path):void
    {

        $logParams = [
            'Traits' => 'HasUtility',
            'Method' => 'handleDirectoryError',
        ];

        try {
            // Elimina la directory
            Storage::deleteDirectory($path);

            Log::channel('upload')->error(json_encode($logParams), [
                'Action' => 'Directory eliminata con successo',
                'path' => $path]);

            // Ricrea la directory con permessi 0775
            Storage::makeDirectory($path, 0775, true);

            Log::channel('upload')->info(json_encode($logParams), [
                'Action' => 'Directory ricreata con successo',
                'path' => $path]);

            // Prova a cambiare i permessi della nuova directory
            if (!$this->changePermissions($path, 'directory')) {
                throw new Exception("Impossibile cambiare i permessi della directory dopo la ricreazione: {$path}");
            }

        } catch (Exception $e) {

            $errorMessage = $e->getMessage();

            Log::channel('upload')->error(json_encode($logParams), [
                'Action' => 'Errore durante la cancellazione e ricreazione della directory',
                'error' => $errorMessage,
                'path' => $path]);

            throw new Exception($errorMessage);
        }
    }

    /**
     * Crea una directory con permessi specifici.
     *
     * @param string $path
     * @return void
     * @throws Exception
     */
    private function createDirectory($path)
    {

        $logParams = [
            'Traits' => 'HasUtility',
            'Method' => 'createDirectory',
        ];

        try {
            // Tentativo di creare la directory con permessi 0775
            if (!mkdir($path, 0775, true) && !is_dir($path)) {
                throw new Exception("Impossibile creare la directory temporanea: {$path}");
            }

            Log::channel('upload')->info(json_encode($logParams), [
                'Action' => 'Directory creata con successo',
                'path' => $path]);


        } catch (Exception $e) {

            $errorMessage = $e->getMessage();
            Log::channel('upload')->error(json_encode($logParams), [
                'Action' => 'Errore durante la creazione della directory',
                'error' => $errorMessage,
                'path' => $path]);

            throw new Exception($errorMessage);
        }
    }

}
