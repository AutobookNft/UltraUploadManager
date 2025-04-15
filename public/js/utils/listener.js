// Real-time listener for handling upload notifications via Laravel Echo.
import { updateStatusMessage } from '../index';
// Import global.d.ts types
import '../global'; // Ensure global.d.ts is recognized
/**
 * Sets up a real-time listener using Laravel Echo to handle status updates
 * during the upload and scanning process.
 * Listens to the 'upload' channel for events like virus scans and upload results.
 */
export function setupRealTimeUploadListener() {
    // Check if Laravel Echo is initialized
    if (!window.Echo) {
        console.error('Laravel Echo is not initialized');
        return;
    }
    // Debug: Log successful Pusher connection
    window.Echo.connector.pusher.connection.bind('connected', () => {
        console.log('✅ Pusher connected successfully!');
        console.log('Socket ID:', window.Echo.socketId());
    });
    // Debug: Log Pusher connection errors
    window.Echo.connector.pusher.connection.bind('error', (err) => {
        console.error('❌ Pusher connection error:', err);
    });
    // Log that the listener is active
    console.info('Laravel Echo is listening...');
    // Listen to the 'upload' channel for the '.TestUploadEvent12345' event
    window.Echo.channel('upload')
        .listen('.TestUploadEvent12345', (e) => {
        console.log('Event received:', e);
        logEvent(e, 'info'); // Log event details in local mode
        // Handle different event states
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
 * @param e - The event object received from Laravel Echo.
 * @param type - The type of the log (e.g., 'success', 'error', 'info').
 */
function logEvent(e, type) {
    if (window.envMode === 'local') {
        console.log(`Event Type: ${type}`, e);
        console.log(`Message: ${e.message}`);
    }
}
//# sourceMappingURL=listener.js.map