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
        return storage_path(
            config('upload-manager.temp_path') . DIRECTORY_SEPARATOR . uum_nome_file_innocuo($filename)
        );
    }
}

if (!function_exists('uum_percorso_temporaneo_ammesso')) {
    /**
     * Accetta un percorso che arriva dal client SOLO se cade dentro una cartella temporanea
     * prevista; altrimenti lo scarta.
     *
     * ⚠️ QUESTO È L'UNICO CONTENIMENTO DEL PACCHETTO, come `uum_nome_file_innocuo` è l'unica
     * bonifica. Chiunque riceva un PERCORSO da fuori — non un nome, un percorso — passa di qui.
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
            realpath(storage_path((string) config('upload-manager.temp_path', 'app/private/temp'))),
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
