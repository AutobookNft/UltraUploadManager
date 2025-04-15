var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { csrfToken } from '../index';
/**
 * Funzione alternativa che tenta di salvare un file nella directory temporanea di sistema
 * quando il metodo standard di salvataggio fallisce.
 *
 * @param formData - I dati del form contenenti il file da salvare temporaneamente
 * @returns Una promessa che si risolve quando il file è stato salvato con successo
 * @throws Lancia un errore se il salvataggio fallisce
 */
export function saveToSystemTempDir(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        if (window.envMode === 'local') {
            console.log('Inside saveToSystemTempDir (fallback method)');
        }
        try {
            // Ottieni informazioni sul file per il logging
            const file = formData.get('file');
            if (!file) {
                throw new Error('File mancante nel FormData');
            }
            if (window.envMode === 'local') {
                console.log(`Tentativo di salvataggio alternativo per il file: ${file.name}`);
            }
            // Crea una copia del FormData con parametri aggiuntivi per identificare
            // che stiamo usando il metodo alternativo
            const fallbackFormData = new FormData();
            fallbackFormData.append('file', file);
            fallbackFormData.append('_token', csrfToken);
            fallbackFormData.append('method', 'system_temp');
            fallbackFormData.append('fallback', 'true');
            // Chiamata al server per salvare il file nella directory di sistema
            const response = yield fetch('/upload-system-temp', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: fallbackFormData,
            });
            if (window.envMode === 'local') {
                console.log('Response from system temp upload:', response);
            }
            if (!response.ok) {
                // Se la risposta non è ok, ottieni i dettagli dell'errore
                let errorDetails;
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    errorDetails = yield response.json();
                }
                else {
                    errorDetails = yield response.text();
                }
                if (window.envMode === 'local') {
                    console.error('Error in saveToSystemTempDir. Server response:', errorDetails);
                }
                throw new Error(typeof errorDetails === 'string'
                    ? errorDetails
                    : (errorDetails.message || 'Impossibile salvare nella directory temporanea di sistema'));
            }
            // Ottieni la risposta JSON dal server
            const result = yield response.json();
            if (window.envMode === 'local') {
                console.log('File saved to system temp directory:', result);
            }
            // Aggiorna i dati nei form per i passaggi successivi
            if (result.tempPath) {
                // Se il server restituisce un percorso temporaneo, lo aggiungiamo al FormData
                // originale per poterlo usare nelle funzioni successive
                formData.append('systemTempPath', result.tempPath);
            }
            return;
        }
        catch (error) {
            if (window.envMode === 'local') {
                console.error('Error in saveToSystemTempDir:', error);
            }
            // Rilancia l'errore con un messaggio più specifico
            throw new Error(`Fallback di salvataggio fallito: ${error.message || 'Errore sconosciuto'}`);
        }
    });
}
/**
 * Funzione per eliminare un file temporaneo salvato con il metodo alternativo.
 *
 * @param formData - I dati del form contenenti le informazioni sul file
 * @returns Una promessa che si risolve quando il file è stato eliminato
 */
export function deleteSystemTempFile(formData) {
    return __awaiter(this, void 0, void 0, function* () {
        if (window.envMode === 'local') {
            console.log('Inside deleteSystemTempFile');
        }
        // Verifica se c'è un percorso temporaneo di sistema da eliminare
        const systemTempPath = formData.get('systemTempPath');
        if (!systemTempPath) {
            if (window.envMode === 'local') {
                console.log('No system temp path to delete, skipping');
            }
            return;
        }
        try {
            const deleteFormData = new FormData();
            deleteFormData.append('tempPath', systemTempPath);
            deleteFormData.append('_token', csrfToken);
            const response = yield fetch('/delete-system-temp', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: deleteFormData
            });
            if (!response.ok) {
                if (window.envMode === 'local') {
                    console.warn('Failed to delete system temp file, but continuing...', yield response.text());
                }
                // Non lanciamo un errore qui, perché l'operazione di pulizia non dovrebbe
                // interrompere il flusso principale
            }
            else if (window.envMode === 'local') {
                console.log('Successfully deleted system temp file');
            }
        }
        catch (error) {
            if (window.envMode === 'local') {
                console.warn('Error in deleteSystemTempFile, but continuing...', error);
            }
            // Non lanciamo un errore qui per lo stesso motivo spiegato sopra
        }
    });
}
//# sourceMappingURL=saveToSystemTemp.js.map