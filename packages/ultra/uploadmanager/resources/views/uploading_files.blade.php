<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('label.file_upload') }}</title>

    <script>
        console.log('File di layout: Uploading_files pippo pippo');
    </script>

    @vite([
        'packages/ultra/uploadmanager/resources/css/app.css',
        'packages/ultra/uploadmanager/resources/js/app.js',
        'packages/ultra/uploadmanager/resources/ts/file_upload_manager.ts'
    ])

</head>

<style>
    #circle-container {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 200px; /* Puoi modificare l'altezza come preferisci */
    }

    #circle-loader {
        width: 100px; /* Dimensione del cerchio */
        height: 100px;
        border-radius: 50%;
        background: conic-gradient(#4caf50 0%, #4caf50 0%, #ddd 0%); /* Inizialmente vuoto */
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        100% {
            transform: rotate(0deg);
        }
        0% {
            transform: rotate(360deg);
        }
    }

</style>

<body id="uploading_files"  class="bg-gray-100">

<div class="max-w-4xl mx-auto mt-16 p-8 bg-gradient-to-br from-purple-700 to-blue-500 rounded-xl shadow-2xl"
    ondragover="event.preventDefault()"
    ondrop="handleDrop(event)"
    id="upload-container">

    <h2 class="text-3xl font-bold text-white mb-6 text-center">{{ __('label.upload_your_files') }}</h2>

    <p class="text-center text-white mb-8" id="upload-instructions">
        {{ __('label.drag_and_drop_your_files_here_or_click_to_select') }}
    </p>

    <p class="text-center mb-8 text-green-500">
        {{ __('label.max_file_size_reminder') }}
    </p>

    <div class="text-center">
        <label for="files" id="file-label" class="relative mx-auto block w-64 cursor-pointer rounded-full bg-white px-6 py-3 text-lg font-medium text-purple-700 transition-all duration-300 ease-in-out hover:bg-purple-100 hover:shadow-lg">
            {{ __('label.upload_your_files') }}
            <input type="file" id="files" multiple class="absolute left-0 top-0 h-full w-full cursor-pointer opacity-0" onchange="handleFileSelect(event)">
        </label>

        <div class="mt-8">
            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mb-6">
                <div class="bg-blue-600 h-2.5 rounded-full" id="progress-bar"></div>
            </div>
            <p class="text-white"><span id="progress-text"></span></p>
            <input
                class="me-2 mt-[0.3rem] h-3.5 w-8 appearance-none rounded-[0.4375rem] bg-black/25 before:pointer-events-none before:absolute before:h-3.5 before:w-3.5 before:rounded-full before:bg-transparent before:content-[''] after:absolute after:z-[2] after:-mt-[0.1875rem] after:h-5 after:w-5 after:rounded-full after:border-none after:bg-white after:shadow-switch-2 after:transition-[background-color_0.2s,transform_0.2s] after:content-[''] checked:bg-primary checked:after:absolute checked:after:z-[2] checked:after:-mt-[3px] checked:after:ms-[1.0625rem] checked:after:h-5 checked:after:w-5 checked:after:rounded-full checked:after:border-none checked:after:bg-primary checked:after:shadow-switch-1 checked:after:transition-[background-color_0.2s,transform_0.2s] checked:after:content-[''] hover:cursor-pointer focus:outline-none focus:before:scale-100 focus:before:opacity-[0.12] focus:before:shadow-switch-3 focus:before:shadow-black/60 focus:before:transition-[box-shadow_0.2s,transform_0.2s] focus:after:absolute focus:after:z-[1] focus:after:block focus:after:h-5 focus:after:w-5 focus:after:rounded-full focus:after:content-[''] checked:focus:border-primary checked:focus:bg-primary checked:focus:before:ms-[1.0625rem] checked:focus:before:scale-100 checked:focus:before:shadow-switch-3 checked:focus:before:transition-[box-shadow_0.2s,transform_0.2s] dark:bg-white/25 dark:after:bg-surface-dark dark:checked:bg-primary dark:checked:after:bg-primary"
                type="checkbox"
                role="switch"
                id="scanvirus"
            />
            <label
                class="inline-block ps-[0.15rem] hover:pointer-events-none text-bold text-red-500"
                id="scanvirus_label"
                for="scanvirus"
                >{{ __('label.virus_scan_disabled') }}
            </label>
            <p class="text-white"><span id="virus-advise"></span></p>
        </div>

        <div id="collection" class="mt-8 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4"></div>

        <div class="mt-8 space-x-4">
            <button type="button" onclick="handleUpload()" id="uploadBtn" class="bg-green-500 text-white px-6 py-3 rounded-full font-medium text-lg transition-all duration-300 hover:bg-green-600 hover:shadow-lg opacity-50 cursor-not-allowed" disabled>
                {{ __('label.save_the_files') }}
            </button>
            <button type="button" onclick="cancelUpload()" id="cancelUpload" class="bg-red-400 text-white px-6 py-3 rounded-full font-medium text-lg transition-all duration-300 hover:bg-red-500 hover:shadow-lg opacity-50 cursor-not-allowed" disabled>
                {{ __('label.cancel') }}
            </button>
        </div>

        <!-- Bottone che permette di tornare alla collection -->
        <div class="mt-8 space-x-4">
            <button type="button" onclick="redirectToCollection()"
            id ="returnToCollection"
            class="bg-red-400 text-white px-6 py-3 rounded-full font-medium text-lg transition-all duration-300 hover:bg-red-500 hover:shadow-lg cursor-not-allowed">
                {{ __('label.return_to_collection') }}
            </button>
        </div>

        <!-- Progressbar -->
        <div class="mt-8">
            <p class="text-white"><span id="scan-progress-text"></span></p>
        </div>

        <!-- Animazione del cerchio mentre avviene la scansione dei virus-->
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

        <div id="status" class="mt-6 text-white text-lg"></div>

    </div>

    <div id="upload-status" class="mt-8 text-center text-white">
        <p id="status-message">{{ __('label.upload_status') .": " . __('label.waiting') }} </p>
    </div>

 <!--
        Importazione delle variabili di configurazione
        NOTA DI FABIO:
        Consapevole che la {include()} di un file blade non sia il metodo più convenzionale per caricare le varibili di ambiente Javascript,
        è stato scelto dopo aver notato che questo sia è l'unico modo che garantisce che tutte le variabili siano caricate prima
        del file Javascript qui sotto.
        Se in seguito verrà trovato un modo più convenionzale, verrà cambiato.
    -->
    <script>
        window.currentView = 'uploading_files';
    </script>

    @include('config.configLoader')

    {{-- <script type="module" src="{{ asset('js/uploading_files.js') }}"></script> --}}

</div>

</body>
</html>

