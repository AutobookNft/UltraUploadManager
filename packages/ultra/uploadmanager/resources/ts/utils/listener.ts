import Pusher from 'pusher-js'; // Importa Pusher come default export
import { updateStatusMessage, statusMessage } from '../index';

/**
 * Interface for upload event data received from Laravel Echo.
 */
interface UploadEvent {
    state: string;
    message: string;
    user_id?: number;
    progress?: number;
    [key: string]: any;
}

/**
 * Sets up a real-time listener using Laravel Echo to handle status updates
 * during the upload and scanning process.
 * Listens to the 'upload' channel for events like virus scans and upload results.
 */
export function setupRealTimeUploadListener(): void {
    if (!window.Echo) {
        console.error('Laravel Echo is not initialized');
        return;
    }

    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Pusher connected successfully!');
        console.log('Socket ID:', window.Echo.socketId());
    });

    window.Echo.connector.pusher.connection.bind('error', (err: Error) => {
        console.error('❌ Pusher connection error:', err);
    });

    console.info('Laravel Echo is listening...');

    window.Echo.channel('upload')
        .listen('.TestUploadEvent12345', (e: UploadEvent) => {
            console.log('Event received:', e);

            if (window.envMode === 'local') {
                console.log(`setupRealTimeUploadListener Type: info`, e);
            }

            switch (e.state) {
                case 'virusScan':
                    updateStatusMessage(e.message, 'info'); // Durante la scansione -> animazione
                    break;
                case 'allFileScannedNotInfected':
                    updateStatusMessage(e.message, 'success'); // Scansione completata senza virus -> nessuna animazione
                    break;
                case 'allFileScannedSomeInfected':
                    updateStatusMessage(e.message, 'warning'); // Scansione completata con alcuni virus -> nessuna animazione
                    break;
                case 'endVirusScan':
                    updateStatusMessage(e.message, 'warning'); // Scansione terminata per errore -> nessuna animazione
                    break;
                case 'uploadFailed':
                    updateStatusMessage(e.message, 'error'); // Upload fallito -> nessuna animazione
                    break;
                default:
                    // Per sicurezza, qualsiasi stato sconosciuto non dovrebbe avere l'animazione
                    updateStatusMessage(e.message, 'info');
                    statusMessage.classList.remove('animate-pulse'); // Rimuovi esplicitamente l'animazione
                    break;
            }
        });
}
