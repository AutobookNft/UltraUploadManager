// Real-time listener for handling upload notifications via Laravel Echo.
import { updateStatusMessage } from '../index';

declare const window: any;

/**
 * Interface for upload event data received from Laravel Echo.
 */
interface UploadEvent {
    state: string;
    message: string;
    [key: string]: any; // For any additional properties that might be present
}

/**
 * Configura un listener in tempo reale usando Laravel Echo per gestire gli aggiornamenti di stato
 * durante il processo di upload e scansione.
 */
export function setupRealTimeUploadListener(): void {
    if (!window.Echo) {
        console.error('Laravel Echo non è inizializzato');
        return;
    }

    // Aggiungi questo per debug
    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Pusher connesso correttamente!');
        console.log('Socket ID:', window.Echo.socketId());
    });

    window.Echo.connector.pusher.connection.bind('error', (err) => {
        console.error('❌ Errore di connessione Pusher:', err);
    });

    console.info('Laravel Echo è in ascolto...');

    window.Echo.channel('upload')
        .listen('.TestUploadEvent12345', (e: { state: any; message: string; }) => {  // Nota il punto all'inizio
        console.log('Evento ricevuto:', e);

        switch (e.state) {
            case 'virusScan':
                updateStatusMessage(e.message, 'info');
                break;
            case 'allFileScannedNotInfected':
                updateStatusMessage(e.message, 'success');
                break;
            case 'uploadFailed':
                updateStatusMessage(e.message, 'error');
                break;
            default:
                updateStatusMessage(e.message, 'info');
                break;
        }
    });

    }

/**
 * Logs events to the console if running in local environment mode.
 *
 * @param e The event object received from Laravel Echo.
 * @param type The type of the log (e.g., success, error, info).
 */
function logEvent(e: UploadEvent, type: string): void {
    if (window.envMode === 'local') {
        console.log(`Event Type: ${type}`, e);
        console.log(`Message: ${e.message}`);
    }
}
