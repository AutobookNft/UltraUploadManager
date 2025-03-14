
/**
 * Funzione per caricare un file lato server.
 * @param formData - I dati del form contenenti il file da caricare.
 * @returns Un oggetto contenente l'esito dell'upload, la risposta e gli eventuali errori.
 */
export async function fileForUpload(formData: FormData): Promise<FileUploadResult> {
    let errorData: any = null;
    let success: boolean = true;

    if ((window as any).envMode === 'local') {
        console.log('dentro fileForUpload');
    }

    if ((window as any).envMode === 'local') {
        console.log('in fileForUpload: formData:', formData.get('file')?.name); // Log del Content-Type per verificare il tipo di risposta
    }

    try {
        const response: Response = await fetch('/uploading-files', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': (window as any).csrfToken,
                'Accept': 'application/json',
            },
            body: formData
        });

        const contentType = response.headers.get('content-type');
        if ((window as any).envMode === 'local') {
            console.log('Content-Type:', contentType); // Log del Content-Type per verificare il tipo di risposta
        }

        if (!response.ok) {
            if (contentType && contentType.includes('application/json')) {
                errorData = await response.json(); // Ottieni la response dal server in formato JSON
                success = false;
            } else {
                const rawErrorData = await response.text(); // Se non è JSON, ottieni il testo (potrebbe essere HTML)
                errorData = {
                    message: 'Il server ha restituito una risposta non valida o inaspettata.',
                    details: rawErrorData, // Mantiene il contenuto HTML o testo come dettaglio
                    state: 'unknown',
                    errorCode: 'unexpected_response',
                    blocking: 'blocking', // Considera questo un errore bloccante di default
                };
                success = false;
            }

            return { error: errorData, response, success };
        }

        return { error: false, response, success };

    } catch (error) {
        if ((window as any).envMode === 'local') {
            console.error('Error in fileForUpload:', error);
        }

        return { error, response: false, success: false }; // Restituiamo l'errore come parte dell'oggetto
    }
}
