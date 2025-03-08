    // @ts-ignore
    if (import.meta.env.MODE === 'development') {
        console.log('Dentro customOnerror');
    }

    /*
    |--------------------------------------------------------------------------
    | Gestione Centralizzata degli Errori JavaScript - window.onerror
    |--------------------------------------------------------------------------
    |
    | Questa funzione cattura gli errori JavaScript a livello globale nel browser,
    | fornendo un meccanismo centralizzato per gestire, loggare e inviare al server
    | solo gli errori critici. Utilizza un array di errori critici che richiedono
    | una segnalazione immediata al devTeam.
    |
    | Funzionamento:
    | 1. La funzione estrae i dettagli rilevanti dallo stack dell'errore, come il nome
    |    del file, il metodo, il numero di riga e colonna.
    | 2. Viene verificato se l'errore è critico, controllando se il codice dell'errore
    |    è incluso nella lista `window.criticalErrors`.
    | 3. Se l'errore è critico, viene inviato al server tramite una richiesta POST.
    | 4. Gli errori non critici non vengono inviati, ma sono comunque mostrati nella console.
    |
    | Variabili Globali Importanti:
    | - window.errorCodes: contiene i codici di errore configurati nel sistema.
    | - window.criticalErrors: contiene un array di errori considerati critici.
    | - window.extractErrorDetails: funzione di utilità che estrae i dettagli dell'errore
    |   dalla stack trace.
    |
    | Parametri:
    | - devMessage: Messaggio dettagliato per il team di sviluppo (per esempio, un'eccezione).
    | - codeError: Codice dell'errore associato.
    | - stack: La stack trace dell'errore (opzionale, può essere null).
    |
    | Esempio di utilizzo:
    | Quando si verifica un errore critico come un upload fallito o la rilevazione di un virus,
    | l'errore viene segnalato immediatamente al server e il devTeam riceve i dettagli
    | necessari per intervenire rapidamente.
    |
    | Nota:
    | - Gli errori non critici, pur non essendo inviati al server, possono essere tracciati
    |   per debugging nella console di sviluppo.
    |
    */

    /**
    * Funzione per loggare gli errori non bloccanti
    * @param {string} jsonString - Lista di file da validare
    * @param {string} key - Lista di file da validare
    *
    */
    function parseJsonString(jsonString, key) {
        try {
            const parsedData = JSON.parse(jsonString);
            return parsedData[key];
        } catch (error) {
            console.error(`Errore nel parsing della stringa JSON: ${error}`);
            return null; // Puoi gestire il caso in cui il parsing fallisce
        }
    }

    /**
     * @param {string} devMessage
     * @param {number} [codeError]
     * @param {string | null} [stack]
     * @returns {Promise<boolean>}
     */
     // @ts-ignore
     window.onerror = async function (devMessage, codeError = 0, stack = null)  {
        // @ts-ignore
        if (import.meta.env.MODE === 'development') {
            console.log('Dentro window.onerror codeError', codeError);
        }

        // @ts-ignore
        if (import.meta.env.MODE === 'development') {
            console.log('Dentro window.onerror stack', stack);
        }

        // Estrai la stack trace dell'errore
        const errorStack = stack ? String(stack) : undefined;

        // @ts-ignore
        if (import.meta.env.MODE === 'development') {
            console.log('Stack prima di chiamare extractErrorDetails:', errorStack);
        }

        // Estrai i dettagli dell'errore (riga, colonna, file, etc.)
        const errorDetails = window.extractErrorDetails(errorStack, codeError);
        errorDetails.message = devMessage;
        errorDetails.stack = errorStack;

        console.log('Message:', errorDetails.message);
        console.log('Method:', errorDetails.methodName);
        console.log('File:', errorDetails.fileName);
        console.log('Line:', errorDetails.lineNumber);
        console.log('Column:', errorDetails.columnNumber);
        console.log('Error code:', errorDetails.codeError);

        // Verifica se l'errore è critico o non bloccante
        try {

            // Verifica se l'errore è critico
            const resultIsCritical = await window.checkErrorStatus('/get-error-constant', 'isCritical', codeError);
            const isCritical = Boolean(resultIsCritical); // Converti in booleano per sicurezza

            // Verifica se l'errore è non bloccante
            const resultIsNotBlocking = await window.checkErrorStatus('/get-non-blocking-error-constant', 'isNotBlocking', codeError);
            const isNotBlocking = Boolean(resultIsNotBlocking); // Converti in booleano per sicurezza


            // @ts-ignore
            if (import.meta.env.MODE === 'development') {
                console.log('isCritical:', isCritical);
                console.log('isNotBlocking:', isNotBlocking);
            }

            // Se l'errore è critico, invia i dettagli al server
            if (isCritical) {
                await sendErrorToServer(errorDetails); // Funzione per inviare l'errore
            }

            // Ritorna true se l'errore non è bloccante, altrimenti false
            return isNotBlocking;

        } catch (e) {
            // @ts-ignore
            if (import.meta.env.MODE === 'development') {
                console.error('Errore in window.onerror:', e);
            }
            // In caso di errori durante il controllo, consideralo bloccante
            return true;
        }
    };


    /**
    * Funzione per inviare gli errori critici al server
    *
    * @param {string} errorDetails - Dettagli dell'errore da inviare al server
    *
    */
    async function sendErrorToServer(errorDetails) {
        try {
            const response = await fetch('/report-js-error', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(errorDetails)
            });
            const data = await response.json();
            // @ts-ignore
            if (import.meta.env.MODE === 'development') {
                console.info('Risposta dal server:', data);
            }
        } catch (error) {
            console.error('Errore durante l\'invio della segnalazione dell\'errore:', error);
        }
    }

    /**
    * Funzione per loggare gli errori non bloccanti
    * @param {string} errorDetails - Lista di file da validare
    *
    */
    function logNonBlockingError(errorDetails) {
        // @ts-ignore
        if (import.meta.env.MODE === 'development') {
            console.info('Errore non bloccante loggato:', errorDetails);
        }
        // Qui puoi anche inviare un log al server se necessario
    }

    /**
     * Funzione per effettuare una chiamata fetch generica e restituire un valore specifico dal JSON
     *
     * @param {string} endpoint - L'URL dell'endpoint API da chiamare
     * @param {string} jsonKey - La chiave JSON che vuoi estrarre dalla risposta
     * @param {number} codeError - Il codice dell'errore da passare all'API
     * @returns {Promise<boolean | null>} - Il valore associato alla chiave JSON estratta o null in caso di errore
     */
    window.checkErrorStatus = async function checkErrorStatus(endpoint, jsonKey, codeError) {
        try {
            console.log(`Dentro checkErrorStatus per ${endpoint}`, 'codeError: ' + codeError, 'jsonKey: ' + jsonKey);

            // Esegui la chiamata fetch all'endpoint passato come parametro
            const response = await fetch(`${endpoint}/${jsonKey}`);

            console.log('Risposta fetch ricevuta:', response);

            if (!response.ok) {
                throw new Error(`Errore di rete: ${response.statusText}`);
            }

            // Controlla il tipo di contenuto restituito dal server
            const contentType = response.headers.get("content-type");

            if (contentType && contentType.indexOf("application/json") !== -1) {

                // Se è JSON, prova a fare il parsing
                try {
                    const data = await response.json();
                    console.log('Risposta JSON ricevuta:', data);

                    // Verifica se la chiave specificata esiste nel JSON
                    if (jsonKey in data) {
                        return data[jsonKey];
                    } else {
                        console.error(`La chiave ${jsonKey} non è presente nella risposta JSON.`);
                        return null;
                    }

                } catch (error) {
                    // @ts-ignore
                    throw new Error(`Errore nel parsing del JSON: ${error.message}`);
                }

            } else {
                // Se non è JSON, logga la risposta come testo
                const text = await response.text();
                if (window.envMode === 'local') {
                    console.error('Risposta non JSON ricevuta:', text);
                }

                // Cerca di estrarre il dato come stringa
                const match = text.match(/"isNotBlocking":(true|false)/);
                if (match) {
                    // Estrai il valore come stringa e convertilo in booleano
                    const isNotBlocking = match[1] === 'true';
                    return isNotBlocking;
                } else {
                    // Se non trovi la chiave, lancia un'eccezione
                    throw new Error('La chiave "isNotBlocking" non è presente o non è parsabile nella risposta non JSON.');
                }
            }

        } catch (error) {
            if (window.envMode === 'local') {
                console.error(`Errore durante la chiamata API in checkErrorStatus per ${endpoint}:`, error);
            }
            return false;
        }
    };

    /**
     * Estrae il numero di riga, colonna, nome del file, metodo dalla stack trace di un errore, e aggiunge il codice di errore.
     *
     * @param {string} stack - La stack trace da cui estrarre le informazioni.
     * @param {string|number} codeError - Il codice di errore associato all'errore.
     * @returns {object} Un oggetto contenente il numero di riga, colonna, nome del file, metodo e il codice di errore.
     */
    window.extractErrorDetails = function (stack, codeError) {

        try {

            console.log('Stack trace:', stack);

            if (!stack) {
                console.error('Stack is undefined or null');
                return { methodName: 'N/A', fileName: 'N/A', lineNumber: 'N/A', columnNumber: 'N/A', codeError };
            }

            const stackLines = stack.split('\n');

            // Cerca la prima linea che contiene informazioni su file, linea e colonna
            for (const line of stackLines) {
                const match = line.match(/at\s+([^\s]+)\s+\((.*):(\d+):(\d+)\)/);
                if (match) {
                    const methodName = match[1];
                    const fileName = match[2].split('/').pop();  // Estrae solo il nome del file
                    const lineNumber = match[3];
                    const columnNumber = match[4];
                    return { methodName, fileName, lineNumber, columnNumber, codeError };
                }
            }

            // Se non trovi nulla, ritorna N/A
            return { methodName: 'N/A', fileName: 'N/A', lineNumber: 'N/A', columnNumber: 'N/A', codeError };

        } catch (e) {

            console.error('Errore in extractErrorDetails:', e);
            return { methodName: 'N/A', fileName: 'N/A', lineNumber: 'N/A', columnNumber: 'N/A', codeError };

        }
    }
