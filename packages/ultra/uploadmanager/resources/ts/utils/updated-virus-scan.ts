import {
    csrfToken,
    progressBar,
    progressText,
    saveLocalTempFile,
    deleteTemporaryFileLocal,
    updateStatusDiv,
    saveToSystemTempDir,
    deleteSystemTempFile
} from '../index';

declare const window: any;

/**
 * Gestisce la scansione antivirus per il file specificato.
 * Implementa un approccio con fallback in caso di errori nel salvataggio temporaneo.
 *
 * @param formData - I dati del form contenenti il file da scansionare
 * @returns Una promessa che restituisce un booleano che indica se il file è infetto
 * @throws Errore se la scansione fallisce o se non è possibile salvare il file temporaneamente
 */
export async function handleVirusScan(formData: FormData): Promise<boolean> {
    let usedFallbackMethod = false;

    try {
        // Prova il metodo standard di salvataggio temporaneo
        await saveLocalTempFile(formData);
        if (window.envMode === 'local') {
            console.log('File salvato temporaneamente con metodo standard');
        }
    } catch (primaryError) {
        if (window.envMode === 'local') {
            console.error('Errore nel salvataggio primario del file temporaneo:', primaryError);
        }

        try {
            // Metodo alternativo: proviamo a usare la directory di sistema temp
            await saveToSystemTempDir(formData);
            usedFallbackMethod = true;
            if (window.envMode === 'local') {
                console.log('File salvato temporaneamente con metodo fallback');
            }
        } catch (backupError) {
            // Se falliscono entrambi i metodi, non possiamo procedere
            if (window.envMode === 'local') {
                console.error('Errore anche nel metodo alternativo:', backupError);
            }
            updateStatusDiv('Impossibile completare la scansione virus: problema di salvataggio temporaneo', 'error');
            throw new Error('Impossibile salvare il file temporaneo per la scansione virus');
        }
    }

    // A questo punto, il file è stato salvato con successo, possiamo procedere con la scansione
    try {
        // Scan del file e gestione della barra di progresso
        const { response, data } = await scanFileWithProgress(formData);

        // Pulizia: elimina il file temporaneo in base al metodo usato
        try {
            if (usedFallbackMethod) {
                await deleteSystemTempFile(formData);
            } else {
                await deleteTemporaryFileLocal(formData.get('file') as File);
            }
        } catch (deleteError) {
            // Log dell'errore ma continuiamo (il file è già stato scansionato)
            if (window.envMode === 'local') {
                console.warn('Errore nell\'eliminazione del file temporaneo:', deleteError);
            }
        }

        if (!response.ok) {
            // Il file è infetto
            if (response.status === 422) {
                updateStatusDiv(data.userMessage, 'error');
                return true; // Virus trovato
            }

            // Altro tipo di errore nella risposta
            throw data;
        }

        // File non infetto
        return false;
    } catch (scanError) {
        // Se c'è un errore nella scansione, dobbiamo comunque assicurarci di pulire
        try {
            if (usedFallbackMethod) {
                await deleteSystemTempFile(formData);
            } else {
                await deleteTemporaryFileLocal(formData.get('file') as File);
            }
        } catch (cleanupError) {
            // Log dell'errore di pulizia, ma l'errore principale è quello della scansione
            if (window.envMode === 'local') {
                console.warn('Errore nella pulizia dopo errore di scansione:', cleanupError);
            }
        }

        // Rilanciamo l'errore della scansione
        throw scanError;
    }
}

/**
 * Esegue una scansione virus su un file con feedback di progresso.
 *
 * @param formData - I dati del form contenenti il file da scansionare
 * @returns Una promessa che restituisce la risposta e i dati dal server
 */
export async function scanFileWithProgress(formData: FormData): Promise<ScanFileResponse> {
    if (window.envMode === 'local') {
        console.log('Inside scanFileWithProgress');
    }

    let progress = 0;
    const realScanDuration = 35000; // Durata simulata della scansione: 35 secondi
    const startTime = Date.now();

    // Funzione per simulare l'avanzamento fino al 95%
    function simulateProgress(): void {
        const elapsedTime = Date.now() - startTime;
        progress = Math.min(95, (elapsedTime / realScanDuration) * 100);
        progressBar.style.width = `${progress}%`;
        progressText.innerText = `${Math.round(progress)}%`;

        if (progress >= 95) {
            clearInterval(interval);
        }
    }

    const interval = setInterval(simulateProgress, 100);

    try {
        // Se il form contiene un percorso temporaneo personalizzato, aggiungiamolo alla richiesta
        if (formData.has('systemTempPath')) {
            formData.append('customTempPath', formData.get('systemTempPath') as string);
        }

        const response = await fetch('/scan-virus', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        clearInterval(interval);

        if (response.ok) {
            // Completa la barra di progresso fino al 100%
            const finalProgressInterval = setInterval(() => {
                progress += 0.5;
                progressBar.style.width = `${progress}%`;
                progressText.innerText = `${Math.round(progress)}%`;

                if (progress >= 100) {
                    clearInterval(finalProgressInterval);
                }
            }, 50);

            const data = await response.json();
            return { response, data };
        } else {
            const data = await response.json();
            return { response, data };
        }
    } catch (error: any) {
        clearInterval(interval);
        throw error;
    }
}
