<?php

/**
 * @package  Ultra\UploadManager\Helpers
 * @author   Padmin D. Curtis (AI Partner OS3.0-Compliant) for Fabio Cherici
 * @version  1.1.0 (M-EGI-430)
 * @date     2026-08-25
 * @purpose  Le funzioni che chiunque nel pacchetto usa per trattare un nome di file che
 *           arriva da fuori, e per comporre il percorso temporaneo in cui finisce.
 */

if (!function_exists('uum_nome_file_innocuo')) {
    /**
     * Rende innocuo un nome di file che arriva dal client, prima che tocchi un percorso.
     *
     * ⚠️ QUESTA È L'UNICA BONIFICA DEL PACCHETTO. Chiunque componga un percorso con un nome
     * che viene da fuori passa di qui. Prima esisteva in un punto solo, dentro un controller,
     * mentre altri tre percorsi vivi concatenavano il nome grezzo — e finché la regola di
     * ammissione era strettissima la sicurezza era coperta per accidente (M-EGI-430).
     *
     * Tiene il nome LEGGIBILE — chi carica deve ritrovare il proprio file — e toglie soltanto
     * ciò con cui si esce dalla cartella prevista o si rompe un filesystem:
     *
     *   - il percorso: solo l'ultimo segmento, tagliando sia `/` sia `\` (il separatore di
     *     Windows su Linux passerebbe indenne dentro il nome). «../../etc/passwd» → «passwd»;
     *   - i caratteri di controllo, byte-zero compreso: un nome troncato da uno zero può
     *     puntare a un file diverso da quello che si legge;
     *   - i caratteri vietati dai filesystem: : * ? " < > |
     *   - i punti in testa: un nome che comincia per punto è nascosto, e «..» non è un nome.
     *
     * Se non resta niente di utilizzabile il nome diventa «file»: meglio un nome generico di
     * un nome vuoto concatenato in un percorso. Su UTF-8 malformato la sostituzione produce
     * `null` e si ricade nello stesso caso — fallisce chiuso, ed è la scelta voluta.
     *
     * @param string $nomeDalClient Il nome così come arriva da fuori, non fidato.
     * @return string Un nome utilizzabile per comporre un percorso.
     */
    function uum_nome_file_innocuo(string $nomeDalClient): string
    {
        // L'ORDINE CONTA, e non è ovvio (rilievo dell'audit, M-EGI-430).
        //
        // I caratteri di controllo si tolgono PRIMA del taglio del percorso. Il taglio usa un
        // «qualunque cosa fino all'ultima barra», ma in un'espressione regolare il punto NON
        // attraversa un a-capo: con «a\n../../etc/passwd» il taglio non agganciava niente e le
        // barre sopravvivevano — il nome usciva ancora con «../..» dentro. Pulendo prima, il
        // taglio vede una riga sola e fa il suo lavoro.
        $nome = (string) preg_replace('/[\x00-\x1F\x7F:*?"<>|]/u', '', $nomeDalClient);

        // Solo l'ultimo segmento, tagliando entrambi i separatori.
        $nome = (string) preg_replace('#^.*[\\\\/]#', '', $nome);

        // Cintura oltre alle bretelle: qualunque cosa sia sopravvissuta, qui resta un nome.
        $nome = basename($nome);

        $nome = ltrim($nome, '.');
        $nome = trim($nome);

        return $nome === '' ? 'file' : $nome;
    }
}

if (!function_exists('uum_cartella_temporanea')) {
    /**
     * La cartella temporanea dell'applicazione, risolta in percorso ASSOLUTO.
     *
     * ⚠️ Il valore di configurazione `upload-manager.temp_path` è ASSOLUTO per come lo scrive il
     * file di configurazione del pacchetto — `storage_path('app/private/temp')`, valutato al
     * caricamento. Passarlo a `storage_path()` una seconda volta produce un percorso doppio
     * (`…/storage/home/…/storage/app/private/temp`) che non esiste.
     *
     * Non è un dettaglio: `uum_percorso_temporaneo_ammesso` scartava la radice così composta —
     * `realpath()` di un percorso inesistente è `false` — e restava viva la sola `sys_get_temp_dir()`.
     * Cioè: i percorsi LEGITTIMI dentro la cartella dell'applicazione venivano RIFIUTATI, e nel
     * frattempo era ammessa `/tmp` per intero. Misurato con l'applicazione avviata (audit M-EGI-430).
     *
     * Qui si accetta l'una e l'altra forma: se il valore è già assoluto lo si usa com'è, altrimenti
     * lo si àncora a `storage_path()`. Così la funzione regge anche se un organo lo configura
     * relativo, che è la forma che il nome del parametro suggerisce.
     *
     * @return string Percorso assoluto (non necessariamente esistente: chi chiama usa realpath).
     */
    function uum_cartella_temporanea(): string
    {
        $configurato = (string) config('upload-manager.temp_path', 'app/private/temp');

        // Assoluto su POSIX, oppure con lettera di unità/UNC su Windows.
        $eAssoluto = str_starts_with($configurato, DIRECTORY_SEPARATOR)
            || str_starts_with($configurato, '/')
            || (bool) preg_match('#^[A-Za-z]:[\\\\/]#', $configurato)
            || str_starts_with($configurato, '\\\\');

        return $eAssoluto ? $configurato : storage_path($configurato);
    }
}

if (!function_exists('get_temp_file_path')) {
    /**
     * Generate the full path for a temporary file in the private storage directory.
     *
     * M-EGI-430: il nome viene bonificato qui dentro. Questo helper riceve nomi che arrivano
     * direttamente dal corpo della richiesta (es. la cancellazione di un file temporaneo), e
     * senza bonifica una risalita di cartella raggiungeva file fuori dalla cartella prevista.
     *
     * @param string $filename The name of the file
     * @return string The full temporary file path
     */
    function get_temp_file_path(string $filename): string
    {
        return uum_cartella_temporanea() . DIRECTORY_SEPARATOR . uum_nome_file_innocuo($filename);
    }
}

if (!function_exists('uum_prefisso_temporaneo_ammesso')) {
    /**
     * Accetta un PREFISSO di storage remoto (S3/Spaces) solo se cade dentro la cartella temporanea
     * dichiarata; altrimenti lo scarta.
     *
     * Il gemello di `uum_percorso_temporaneo_ammesso` per lo storage remoto, dove non esiste
     * `realpath`: non c'e' un filesystem da interrogare, quindi il contenimento è puramente
     * lessicale e la risalita va rifiutata a mano, prima del confronto — «tmp/../altrove»
     * comincia per «tmp» pur non essendoci dentro.
     *
     * Serve a `DeleteTempFolder`, dove il prefisso alimenta una listObjectsV2 seguita da una
     * deleteObject per ogni risultato: un prefisso largo cancella un ramo intero del bucket
     * (audit M-EGI-430).
     *
     * La radice è `app.bucket_temp_file_folder` — la cartella TEMPORANEA dentro il bucket, quella
     * che il pacchetto usa davvero per comporre l'indirizzo remoto di un file temporaneo
     * (`UploadingFiles`, `ConfigController`, `TempFilesCleaner`).
     *
     * ⚠️ La prima versione di questa guardia usava `app.do_bucket_folder`, che è un'ALTRA cosa: la
     * cartella base del bucket. Ancorata lì avrebbe ammesso l'intera cartella base e rifiutato il
     * prefisso legittimo — una difesa che sembra una difesa (audit di chiusura M-EGI-430, secondo
     * giro). Non era sfruttabile, perché la rotta è chiusa; ma una guardia ancorata al posto
     * sbagliato è peggio di una assente, perché chi legge smette di guardare.
     *
     * FALLISCE CHIUSO: se la cartella temporanea non è configurata non si indovina un valore
     * plausibile — si rifiuta tutto. Meglio una cancellazione che non parte di una che parte
     * troppo larga.
     *
     * @param mixed $prefissoDalClient Il valore così come arriva dalla richiesta, non fidato.
     * @return string|null Il prefisso normalizzato, se ammesso; `null` altrimenti.
     */
    function uum_prefisso_temporaneo_ammesso($prefissoDalClient): ?string
    {
        if (empty($prefissoDalClient) || !is_string($prefissoDalClient)) {
            return null;
        }

        if (str_contains($prefissoDalClient, "\0")) {
            return null;
        }

        $radice = trim((string) config('app.bucket_temp_file_folder', ''), '/');
        if ($radice === '') {
            return null;
        }

        // Il separatore di Windows diventa quello di S3 prima di qualunque confronto: senza questo
        // passaggio «..\..\altrove» attraverserebbe i controlli sotto senza essere visto.
        $normalizzato = trim(str_replace('\\', '/', $prefissoDalClient), '/');
        if ($normalizzato === '') {
            return null;
        }

        $risalita = $normalizzato === '..'
            || str_starts_with($normalizzato, '../')
            || str_contains($normalizzato, '/../')
            || str_ends_with($normalizzato, '/..');

        if ($risalita) {
            return null;
        }

        $dentro = $normalizzato === $radice
            || str_starts_with($normalizzato, $radice . '/');

        return $dentro ? $normalizzato : null;
    }
}

if (!function_exists('uum_percorso_temporaneo_ammesso')) {
    /**
     * Accetta un percorso che arriva dal client SOLO se cade dentro una cartella temporanea
     * prevista; altrimenti lo scarta.
     *
     * ⚠️ Questo è il contenimento dei percorsi sul FILESYSTEM LOCALE. Non è l'unico del pacchetto:
     * per i prefissi dello storage remoto c'è `uum_prefisso_temporaneo_ammesso`, che non può usare
     * `realpath` perché non ha un filesystem da interrogare.
     *
     * La versione precedente di questo commento diceva «l'unico contenimento… chiunque riceva un
     * percorso da fuori passa di qui», e non era vero: `DeleteTempFolder` riceveva un prefisso dal
     * client e non passava di qui affatto (audit M-EGI-430). Un commento che promette più di quanto
     * mantiene è peggio di nessun commento: chi legge smette di cercare.
     *
     * Bonificare il nome non basta quando è il percorso a essere libero: due rotte di questo
     * pacchetto prendevano un percorso intero dal corpo della richiesta e ci scrivevano sopra
     * (`/scan-virus`) o lo cancellavano (`/delete-system-temp`), senza autenticazione né CSRF.
     *
     * Non si confrontano stringhe: si risolve il percorso vero con `realpath`, perché
     * «/tmp/../altrove» comincia per «/tmp» pur non essendoci dentro, e perché `realpath`
     * risolve anche i collegamenti simbolici prima del confronto. Un percorso che non esiste
     * non ha realpath, e a queste rotte non serve: scartarlo è la risposta giusta.
     *
     * Decide soltanto: non scrive nel registro. Chi la chiama annota il rifiuto — così resta
     * una funzione pura, provabile senza avviare l'applicazione.
     *
     * @param mixed $percorsoDalClient Il valore così come arriva dalla richiesta, non fidato.
     * @return string|null Il percorso risolto, se ammesso; `null` altrimenti.
     */
    function uum_percorso_temporaneo_ammesso($percorsoDalClient): ?string
    {
        if (empty($percorsoDalClient) || !is_string($percorsoDalClient)) {
            return null;
        }

        // Il byte-zero si rifiuta QUI: `realpath()` su un percorso che lo contiene non
        // restituisce `false`, LANCIA — e sarebbe un 500 su una rotta non autenticata.
        if (str_contains($percorsoDalClient, "\0")) {
            return null;
        }

        $vero = realpath($percorsoDalClient);
        if ($vero === false) {
            return null;
        }

        $ammesse = array_filter([
            realpath(uum_cartella_temporanea()),
            realpath(sys_get_temp_dir()),
        ]);

        foreach ($ammesse as $radice) {
            $radice = rtrim($radice, DIRECTORY_SEPARATOR);
            if ($vero === $radice || str_starts_with($vero, $radice . DIRECTORY_SEPARATOR)) {
                return $vero;
            }
        }

        return null;
    }
}
