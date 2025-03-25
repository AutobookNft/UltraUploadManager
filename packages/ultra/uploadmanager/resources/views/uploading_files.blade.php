<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trans('uploadmanager::uploadmanager.first_template_title') }}</title>

@vite([
    'resources/css/app.css',
    'resources/js/app.js',
    'packages/ultra/uploadmanager/resources/css/app.css',
    'packages/ultra/uploadmanager/resources/js/app.js',
    'packages/ultra/uploadmanager/resources/ts/core/file_upload_manager.ts'
])

<script>
    window.allowedExtensions = @json(config('AllowedFileType.collection.allowed_extensions', []));
    window.allowedMimeTypes = @json(config('AllowedFileType.collection.allowed_mime_types', []));
    window.maxSize = {{ config('AllowedFileType.collection.max_size', 10 * 1024 * 1024) }};

    // Caricamento configurazione
    fetch('{{ route("global.config") }}', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    .then(response => response.json())
    .then(config => {
        Object.assign(window, config);
        document.dispatchEvent(new Event('configLoaded'));
    })
    .catch(error => console.error('Error loading configuration:', error));
</script>

</head>

<body id="uploading_files" class="bg-gray-900 font-sans antialiased">

<div class="max-w-5xl mx-auto mt-12 p-8 bg-gradient-to-br from-gray-800 via-purple-900 to-blue-900 rounded-2xl shadow-2xl border border-purple-500/30 relative nft-background"
    id="upload-container">

    <!-- Title with NFT style -->
    <h2 class="text-4xl font-extrabold text-white mb-6 text-center tracking-wide drop-shadow-lg nft-title">
        💎 {{ trans('uploadmanager::uploadmanager.mint_your_masterpiece') }}
    </h2>

    <!-- Max file size reminder with neon green accent -->
    <p class="text-center mb-8 text-green-400 font-medium">
        {{ trans('uploadmanager::uploadmanager.max_file_size_reminder') }}
    </p>

    <!-- Enhanced drag & drop upload area -->
    <div
        class="w-full h-64 border-4 border-dashed border-blue-400/50 rounded-2xl mb-6 flex flex-col items-center justify-center p-8 transition-all duration-300 bg-purple-800/20 hover:bg-purple-800/30 group"
        id="upload-drop-zone">
        <!-- Drag & drop icon/illustration -->
        <div class="text-5xl mb-4 text-blue-400 group-hover:scale-110 transition-transform duration-300">
            📤
        </div>
        <!-- Instructions -->
        <p class="text-xl text-center text-white mb-6">
            {{ trans('uploadmanager::uploadmanager.drag_files_here') }} <br>
            <span class="text-blue-300 text-sm">{{ trans('uploadmanager::uploadmanager.or') }}</span>
        </p>
        <!-- Button styled with your existing nft-button class -->
        <label for="files" id="file-label" class="relative cursor-pointer rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-4 flex items-center justify-center text-lg font-semibold text-white transition-all duration-300 ease-in-out hover:from-purple-500 hover:to-blue-500 hover:shadow-xl nft-button">
            {{ trans('uploadmanager::uploadmanager.select_files') }}
            <input type="file" id="files" multiple class="absolute left-0 top-0 h-full w-full cursor-pointer opacity-0">
        </label>
        <div class="upload-dropzone text-center text-gray-300 text-sm mt-2">
            <!-- About upload size -->
        </div>
    </div>

    <!-- Progress bar and virus switch -->
    <div class="mt-6 space-y-6">
        <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
            <div class="bg-gradient-to-r from-green-400 to-blue-500 h-3 rounded-full transition-all duration-500" id="progress-bar"></div>
        </div>
        <p class="text-gray-300 text-sm text-center"><span id="progress-text"></span></p>

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
            >{{ trans('uploadmanager::uploadmanager.virus_scan_disabled') }}</label>
        </div>
        <p class="text-gray-300 text-sm text-center"><span id="virus-advise"></span></p>
    </div>

    <!-- Previews grid -->
    <div id="collection" class="mt-10 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
        <!-- Previews will be loaded dynamically via JS -->
    </div>

    <!-- Action buttons with NFT style -->
    <div class="mt-10 flex justify-center space-x-6">
        <button type="button" id="uploadBtn" class="bg-green-500 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button opacity-50 cursor-not-allowed disabled:hover:bg-green-500 disabled:hover:shadow-none">
            💾 {{ trans('uploadmanager::uploadmanager.save_the_files') }}
        </button>
        <button type="button" onclick="cancelUpload()" id="cancelUpload" class="bg-red-500 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button opacity-50 cursor-not-allowed disabled:hover:bg-red-500 disabled:hover:shadow-none">
            ❌ {{ trans('uploadmanager::uploadmanager.cancel') }}
        </button>
    </div>

    <!-- Return to collection button -->
    <div class="mt-6 flex justify-center">
        <button type="button" onclick="redirectToCollection()" id="returnToCollection" class="bg-gray-700 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button hover:bg-gray-600">
            🔙 {{ trans('uploadmanager::uploadmanager.return_to_collection') }}
        </button>
    </div>

    <!-- Scan progress -->
    <div class="mt-10 text-center">
        <p class="text-gray-300 text-sm"><span id="scan-progress-text"></span></p>
    </div>

    <!-- Scan animation -->
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

    <!-- Status -->
    <div id="status" class="mt-6 text-center text-gray-200 text-lg font-medium"></div>

    <!-- Upload status -->
    <div id="upload-status" class="mt-8 text-center text-gray-300">
        <p id="status-message">{{ trans('uploadmanager::uploadmanager.preparing_to_mint') }}</p>
    </div>

</div>
{{-- <script type="module">
    import { initializeApp } from '/packages/ultra/uploadmanager/resources/ts/core/file_upload_manager.ts';
    document.addEventListener('DOMContentLoaded', initializeApp, { once: true });
</script> --}}
</body>


</html>
