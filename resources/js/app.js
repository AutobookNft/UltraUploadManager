// @ts-ignore
if (import.meta.env.MODE === 'development') {
    console.log('Dentro app.js');
    console.log('current window:',  window.currentView);
}

// import "./utils";
import Swal from 'sweetalert2';
// @ts-ignore
import $ from 'jquery';
import "./bootstrap";
import 'whatwg-fetch';

// import { Tooltip } from 'tw-elements';

// Importa il polyfill fetch
// Importa il polyfill whatwg-fetch per garantire la compatibilità della funzione fetch con tutti i browser
// -------------------------------------------------------------
// La funzione fetch è un'API moderna per effettuare richieste HTTP
// che non è supportata da tutti i browser, specialmente le versioni più vecchie.
// Il polyfill whatwg-fetch fornisce un'implementazione della funzione fetch
// che funziona anche su browser più datati, come Internet Explorer 11.
//
// Includendo questo import, assicuriamo che il nostro codice che utilizza fetch
// possa funzionare correttamente in tutti i browser, migliorando la compatibilità
// e l'esperienza utente. È importante includerlo qui nel file principale
// per essere sicuri che il polyfill sia caricato una volta sola e sia disponibile
// in tutto il nostro progetto JavaScript.
//
Documentazione: https://github.com/github/fetch
//


// @ts-ignore
 window.$ = window.jQuery = $;
// @ts-ignore
window.Swal = Swal;


