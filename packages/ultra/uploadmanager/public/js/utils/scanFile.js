var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { csrfToken, progressBar, progressText, saveLocalTempFile, deleteTemporaryFileLocal, updateStatusDiv, saveToSystemTempDir, deleteSystemTempFile } from '../index';
/**
 * Performs a virus scan on a file with a progress feedback simulation.
 *
 * @param formData - The form data containing the file to be scanned.
 * @returns A promise that resolves to the response and data from the server.
 * @throws An error if the request fails or the response is not ok.
 */
export function scanFileWithProgress(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        if (window.envMode === 'local') {
            console.log('Inside scanFileWithProgress');
        }
        let progress = 0;
        const realScanDuration = 35000; // Simulated real scan duration of 35 seconds
        const startTime = Date.now();
        // Function to simulate progress up to 95%
        function simulateProgress() {
            const elapsedTime = Date.now() - startTime;
            progress = Math.min(95, (elapsedTime / realScanDuration) * 100);
            progressBar.style.width = `${progress}%`;
            progressText.innerText = `${Math.round(progress)}%`;
            if (progress >= 95) {
                clearInterval(interval);
            }
        }
        const interval = setInterval(simulateProgress, 100);
        if (window.envMode === 'local') {
            console.log('In scanFileWithProgress: formData:', formData); // Log the Content-Type to verify the type of response
        }
        try {
            // Se il form contiene un percorso temporaneo personalizzato, aggiungiamolo alla richiesta
            if (formData.has('systemTempPath')) {
                formData.append('customTempPath', formData.get('systemTempPath'));
            }
            const response = yield fetch('/scan-virus', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            });
            clearInterval(interval);
            if (response.ok) {
                const finalProgressInterval = setInterval(() => {
                    progress += 0.5;
                    progressBar.style.width = `${progress}%`;
                    progressText.innerText = `${Math.round(progress)}%`;
                    if (progress >= 100) {
                        clearInterval(finalProgressInterval);
                    }
                }, 50);
                const data = yield response.json();
                if (data) {
                    return { response, data };
                }
                else {
                    throw new Error('The JSON response is incorrect');
                }
            }
            else {
                const data = yield response.json();
                return { response, data };
            }
        }
        catch (error) {
            clearInterval(interval);
            throw error;
        }
    });
}
/**
 * Handles the antivirus scan for the given file, saving it temporarily before scanning.
 *
 * @param formData - The form data containing the file to be scanned.
 * @returns A promise that resolves to a boolean indicating if the file is infected.
 * @throws An error if the request fails or the response is not ok.
 */
export function handleVirusScan(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        let usedFallbackMethod = false;
        try {
            // Prova il metodo standard di salvataggio temporaneo
            yield saveLocalTempFile(formData);
            if (window.envMode === 'local') {
                console.log('File salvato temporaneamente con metodo standard');
            }
        }
        catch (primaryError) {
            if (window.envMode === 'local') {
                console.error('Errore nel salvataggio primario del file temporaneo:', primaryError);
            }
            try {
                // Metodo alternativo: proviamo a usare la directory di sistema temp
                yield saveToSystemTempDir(formData);
                usedFallbackMethod = true;
                if (window.envMode === 'local') {
                    console.log('File salvato temporaneamente con metodo fallback');
                }
            }
            catch (backupError) {
                // Se falliscono entrambi i metodi, proviamo comunque ma con un avviso
                if (window.envMode === 'local') {
                    console.error('Errore anche nel metodo alternativo:', backupError);
                }
                updateStatusDiv('Avviso: possibili problemi durante la scansione virus', 'warning');
                // Continuiamo comunque con la scansione
            }
        }
        try {
            // Scan del file e gestione della barra di progresso
            const { response, data } = yield scanFileWithProgress(formData);
            // Pulizia: elimina il file temporaneo in base al metodo usato
            try {
                if (usedFallbackMethod) {
                    yield deleteSystemTempFile(formData);
                }
                else {
                    yield deleteTemporaryFileLocal(formData.get('file'));
                }
            }
            catch (deleteError) {
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
        }
        catch (scanError) {
            // Gestione speciale per l'errore "No such file or directory"
            if (scanError.message && typeof scanError.message === 'string' &&
                (scanError.message.includes('No such file or directory') ||
                    scanError.message.includes('file non trovato'))) {
                if (window.envMode === 'local') {
                    console.warn('File temporaneo non trovato durante la scansione. Procediamo comunque:', scanError);
                }
                updateStatusDiv('Avviso: Impossibile completare la scansione virus, ma procediamo comunque', 'warning');
                return false; // Consideriamo il file non infetto per continuare
            }
            // Se c'è un errore nella scansione, dobbiamo comunque assicurarci di pulire
            try {
                if (usedFallbackMethod) {
                    yield deleteSystemTempFile(formData);
                }
                else {
                    yield deleteTemporaryFileLocal(formData.get('file'));
                }
            }
            catch (cleanupError) {
                // Log dell'errore di pulizia, ma l'errore principale è quello della scansione
                if (window.envMode === 'local') {
                    console.warn('Errore nella pulizia dopo errore di scansione:', cleanupError);
                }
            }
            // Rilanciamo l'errore della scansione per altri tipi di errore
            throw scanError;
        }
    });
}
//# sourceMappingURL=scanFile.js.map