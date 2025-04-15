// Real-time listener for handling upload notifications via Laravel Echo.
// import Echo from 'laravel-echo';
import { updateStatusMessage } from '../index';
/**
 * Sets up a real-time listener using Laravel Echo to handle different states of the file upload and processing.
 * Updates UI elements based on the type of event received.
 */
export function setupRealTimeUploadListener() {
    window.Echo.private('upload')
        .listen('FileProcessingUpload', (e) => {
        switch (e.state) {
            case 'processSingleFileCompleted':
                logEvent(e, 'success');
                updateStatusMessage(e.message, 'success');
                clearInterval(window.scanInterval);
                break;
            case 'allFileSaved':
                logEvent(e, 'success');
                updateStatusMessage(e.message, 'success');
                clearInterval(window.scanInterval);
                break;
            case 'uploadFailed':
                logEvent(e, 'error');
                updateStatusMessage(e.message, 'error');
                clearInterval(window.scanInterval);
                break;
            case 'finishedWithSameError':
                logEvent(e, 'warning');
                updateStatusMessage(e.message, 'warning');
                clearInterval(window.scanInterval);
                document.getElementById('circle-container').style.display = 'none';
                break;
            case 'allFileScannedNotInfected':
                logEvent(e, 'success');
                updateStatusMessage(e.message, 'success');
                document.getElementById('circle-container').style.display = 'none';
                break;
            case 'allFileScannedSomeInfected':
                logEvent(e, 'warning');
                updateStatusMessage(e.message, 'warning');
                document.getElementById('circle-container').style.display = 'none';
                break;
            case 'scanndeSameError':
                logEvent(e, 'error');
                updateStatusMessage(e.message, 'error');
                document.getElementById('circle-container').style.display = 'none';
                break;
            case 'loadingProceedWithSaving':
                logEvent(e, 'error');
                updateStatusMessage(e.message, 'error');
                break;
            case 'virusScan':
                logEvent(e, 'info');
                document.getElementById('circle-container').style.display = 'block';
                document.getElementById('status-message').innerText = e.message;
                break;
            case 'endVirusScan':
                logEvent(e, 'info');
                document.getElementById('circle-container').style.display = 'none';
                document.getElementById('status-message').innerText = e.message;
                break;
            case 'validation':
                logEvent(e, 'info');
                updateStatusMessage(e.message, 'info');
                break;
            case 'tempFileDeleted':
                logEvent(e, 'info');
                break;
            case 'error':
                logEvent(e, 'error');
                break;
            case 'infected':
                logEvent(e, 'error');
                updateStatusMessage(e.message, 'error');
                clearInterval(window.scanInterval);
                document.getElementById('circle-loader').style.background = `conic-gradient(#ff0000 100%, #ddd 0%)`;
                document.getElementById('scan-progress-text').innerText = window.someInfectedFiles;
                document.getElementById('circle-container').style.display = 'none';
                break;
            case 'info':
                logEvent(e, 'info');
                updateStatusMessage(e.message, 'info');
                break;
            default:
                logEvent(e, 'info');
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
function logEvent(e, type) {
    if (window.envMode === 'local') {
        console.log(`Event Type: ${type}`, e);
        console.log(`Message: ${e.message}`);
    }
}
//# sourceMappingURL=realTimeUploadListener.js.map