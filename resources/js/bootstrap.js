import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

// Assicurati che le variabili d'ambiente siano accessibili
if (!import.meta.env.VITE_PUSHER_APP_KEY) {
    console.error('VITE_PUSHER_APP_KEY non è impostata nelle variabili d\'ambiente');
}

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'eu',
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],  // Per forzare WebSockets
    enableLogging: true  // Attiva il logging
});

// In bootstrap.js dopo l'inizializzazione di Echo
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Pusher connesso correttamente!');
    console.log('Socket ID:', window.Echo.socketId());
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ Errore di connessione Pusher:', err);
});

console.log('VITE_PUSHER_APP_KEY:', import.meta.env.VITE_PUSHER_APP_KEY);
console.log('VITE_PUSHER_APP_CLUSTER:', import.meta.env.VITE_PUSHER_APP_CLUSTER);
