<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('label.file_upload') }}</title>

    <script>
        console.log('File di layout: Uploading_files fabio');
    </script>

@vite([
    'resources/css/app.css',
    'resources/js/app.js',
    'packages/ultra/uploadmanager/resources/css/app.css',
    'packages/ultra/uploadmanager/resources/js/app.js',
    'packages/ultra/uploadmanager/resources/ts/core/file_upload_manager.ts'
])

</head>

<body id="uploading_files" class="bg-gray-900 font-sans antialiased">

<div class="max-w-5xl mx-auto mt-12 p-8 bg-gradient-to-br from-gray-800 via-purple-900 to-blue-900 rounded-2xl shadow-2xl border border-purple-500/30 relative nft-background"
    ondragover="event.preventDefault()"
    ondrop="handleDrop(event)"
    id="upload-container">

    <!-- Titolo con stile NFT -->
    <h2 class="text-4xl font-extrabold text-white mb-6 text-center tracking-wide drop-shadow-lg nft-title">
        💎 Mint Your NFT Masterpiece
    </h2>

    <!-- Selettore Lingua -->
    <div class="absolute top-4 right-4">
        <select
            id="langSelector"
            class="bg-purple-700 text-white px-4 py-2 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all duration-300 ease-in-out hover:bg-purple-600"
        >
            @foreach(['it' => '🇮🇹 Italiano', 'en' => '🇬🇧 English', 'fr' => '🇫🇷 Français', 'pt' => '🇵🇹 Português', 'es' => '🇪🇸 Español', 'de' => '🇩🇪 Deutsch'] as $code => $name)
                <option value="{{ $code }}" {{ app()->getLocale() === $code ? 'selected' : '' }}>
                    {{ $name }}
                </option>
            @endforeach
        </select>
    </div>


    <!-- Istruzioni con tipografia moderna -->
    <p class="text-center text-gray-300 mb-8 text-lg" id="upload-instructions">
        Drop your artwork to mint on the blockchain!
    </p>

    <!-- Reminder max file size con accento verde neon -->
    <p class="text-center mb-8 text-green-400 font-medium">
        {{ __('label.max_file_size_reminder') }}
    </p>

    <!-- Area upload con bottone stilizzato -->
    <div class="text-center">
        <label for="files" id="file-label" class="relative mx-auto block w-72 cursor-pointer rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-4 text-xl font-semibold text-white transition-all duration-300 ease-in-out hover:from-purple-500 hover:to-blue-500 hover:shadow-xl nft-button">
            📤 {{ __('label.upload_your_files') }}
            <input type="file" id="files" multiple class="absolute left-0 top-0 h-full w-full cursor-pointer opacity-0" onchange="handleFileSelect(event)">
        </label>

        <!-- Progress bar e switch virus -->
        <div class="mt-10 space-y-6">
            <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                <div class="bg-gradient-to-r from-green-400 to-blue-500 h-3 rounded-full transition-all duration-500" id="progress-bar"></div>
            </div>
            <p class="text-gray-300 text-sm"><span id="progress-text"></span></p>

            <div class="flex items-center justify-center gap-3">
                <input
                    class="me-2 h-4 w-8 appearance-none rounded-full bg-gray-600 before:pointer-events-none before:absolute before:h-4 before:w-4 before:rounded-full before:bg-transparent after:absolute after:z-[2] after:-mt-0.5 after:h-6 after:w-6 after:rounded-full after:bg-white after:shadow-md after:transition-all checked:bg-purple-600 checked:after:ms-4 checked:after:bg-purple-400 checked:after:shadow-md hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                    type="checkbox"
                    role="switch"
                    id="scanvirus"
                />
                <label
                    class="text-red-400 font-medium hover:pointer-events-none"
                    id="scanvirus_label"
                    for="scanvirus"
                >{{ __('label.virus_scan_disabled') }}</label>
            </div>
            <p class="text-gray-300 text-sm"><span id="virus-advise"></span></p>
        </div>

        <!-- Griglia delle anteprime -->
        <div id="collection" class="mt-10 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
            <!-- Anteprime caricate dinamicamente via JS -->
        </div>

        <!-- Bottoni azione con stile NFT -->
        <div class="mt-10 flex justify-center space-x-6">
            <button type="button" id="uploadBtn" class="bg-green-500 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button opacity-50 cursor-not-allowed disabled:hover:bg-green-500 disabled:hover:shadow-none">
                💾 {{ __('label.save_the_files') }}
            </button>
            <button type="button" onclick="cancelUpload()" id="cancelUpload" class="bg-red-500 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button opacity-50 cursor-not-allowed disabled:hover:bg-red-500 disabled:hover:shadow-none">
                ❌ {{ __('label.cancel') }}
            </button>
        </div>

        <!-- Bottone ritorno alla collezione -->
        <div class="mt-6 flex justify-center">
            <button type="button" onclick="redirectToCollection()" id="returnToCollection" class="bg-gray-700 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button hover:bg-gray-600">
                🔙 {{ __('label.return_to_collection') }}
            </button>
        </div>

        <!-- Progresso scansione -->
        <div class="mt-10 text-center">
            <p class="text-gray-300 text-sm"><span id="scan-progress-text"></span></p>
        </div>

        <!-- Animazione cerchio scansione -->
        <div id="circle-container" style="display: none;">
            <div class="flex justify-center">
                <script src="https://unpkg.com/@dotlottie/player-component@latest/dist/dotlottie-player.mjs" type="module"></script>
                <dotlottie-player src="https://lottie.host/03e45a31-c2aa-4c9f-97be-f3bdf1e628fc/LmKcByRgIp.json"
                    background="transparent"
                    speed="1"
                    style="width: 200px; height: 200px;" loop autoplay
                    id="circle-loader"
                    style="display: none;">
                </dotlottie-player>
            </div>
        </div>

        <!-- Stato -->
        <div id="status" class="mt-6 text-center text-gray-200 text-lg font-medium"></div>
    </div>

    <!-- Stato upload esterno -->
    <div id="upload-status" class="mt-8 text-center text-gray-300">
        <p id="status-message">Preparing to mint on the blockchain...</p>
    </div>

</div>

<!-- Configurazione JS -->
<script>
    function setLanguage(lang) {
        fetch('{{ route("global.config") }}?lang=' + lang, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(config => {
            // Salva la lingua preferita nel localStorage
            localStorage.setItem('preferredLanguage', lang);
            // Ricarica la pagina
            window.location.reload();
        })
        .catch(error => {
            console.error('Errore nel cambio lingua:', error);
            alert('Errore nel cambio lingua. Riprova.');
        });
    }

    // Inizializzazione al caricamento della pagina
    document.addEventListener('DOMContentLoaded', () => {
        const langSelector = document.getElementById('langSelector');
        if (langSelector) {
            // Imposta la lingua corrente dal localStorage se esiste
            const savedLang = localStorage.getItem('preferredLanguage');
            if (savedLang) {
                langSelector.value = savedLang;
            }

            // Aggiungi l'event listener per il cambio lingua
            langSelector.addEventListener('change', (e) => {
                setLanguage(e.target.value);
            });
        }
    });
</script>
</body>
</html>
