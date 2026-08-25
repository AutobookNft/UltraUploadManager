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
        $nome = (string) preg_replace('#^.*[\\\\/]#', '', $nomeDalClient);
        $nome = (string) preg_replace('/[\x00-\x1F\x7F:*?"<>|]/u', '', $nome);
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
