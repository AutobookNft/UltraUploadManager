<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ trans('uploadmanager::first_template_title') }}</title>

@vite([
    'resources/css/app.css',
    'resources/js/app.js',
    // Assicurati che questi percorsi siano corretti per la tua configurazione Vite
    'vendor/ultra/ultra-upload-manager/resources/css/app.css',
    'vendor/ultra/ultra-upload-manager/resources/js/app.js',
    'vendor/ultra/ultra-upload-manager/resources/ts/core/file_upload_manager.ts'
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

    <h2 class="text-4xl font-extrabold text-white mb-6 text-center tracking-wide drop-shadow-lg nft-title">
        💎 {{ trans('uploadmanager::mint_your_masterpiece') }}
    </h2>

    <div class="flex flex-wrap justify-center gap-3 mb-6">
        <div class="bg-blue-900/60 text-blue-200 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center shadow-md" title="{{ trans('uploadmanager::secure_storage_tooltip') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            {{ trans('uploadmanager::secure_storage') }}
        </div>
        <div class="bg-purple-900/60 text-purple-200 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center shadow-md" title="{{ trans('uploadmanager::virus_scan_tooltip') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            {{ trans('uploadmanager::virus_scan_feature') }}
        </div>
        <div class="bg-green-900/60 text-green-200 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center shadow-md" title="{{ trans('uploadmanager::advanced_validation_tooltip') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            {{ trans('uploadmanager::advanced_validation') }}
        </div>
        <div class="bg-indigo-900/60 text-indigo-200 px-3 py-1.5 rounded-lg text-sm font-medium flex items-center shadow-md" title="{{ trans('uploadmanager::storage_space_tooltip') }}">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <span>
                <span id="storage-used">2.4</span>/<span id="storage-total">50</span> {{ trans('uploadmanager::storage_space_unit') }}
            </span>
        </div>
    </div>

    {{-- <p class="text-center mb-8 text-green-300 font-medium text-lg">
        {{ trans('uploadmanager::max_file_size_reminder') }}
    </p> --}}

    <div
        class="w-full h-64 border-4 border-dashed border-blue-400/50 rounded-2xl mb-6 flex flex-col items-center justify-center p-8 transition-all duration-300 bg-purple-800/20 hover:bg-purple-800/30 group"
        id="upload-drop-zone">
        <div class="text-5xl mb-4 text-blue-400 group-hover:scale-110 transition-transform duration-300">
            📤
        </div>
        <p class="text-xl text-center text-white mb-6">
            {{ trans('uploadmanager::drag_files_here') }} <br>
            <span class="text-blue-200 text-sm">{{ trans('uploadmanager::or') }}</span>
        </p>
        <label for="files" id="file-label" class="relative cursor-pointer rounded-full bg-gradient-to-r from-purple-600 to-blue-600 px-8 py-4 flex items-center justify-center text-lg font-semibold text-white transition-all duration-300 ease-in-out hover:from-purple-500 hover:to-blue-500 hover:shadow-xl nft-button group" aria-label="{{ trans('uploadmanager::select_files_aria') }}">
            {{ trans('uploadmanager::select_files') }}
            <input type="file" id="files" multiple class="absolute left-0 top-0 h-full w-full cursor-pointer opacity-0">
            <span class="absolute -top-12 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 w-48 text-center">
                {{ trans('uploadmanager::select_files_tooltip') }}
            </span>
        </label>
        <div class="upload-dropzone text-center text-gray-200 text-sm mt-2">
            </div>
    </div>

    <div class="bg-gray-800/50 rounded-xl p-5 mb-6 border border-purple-500/30">
        <h3 class="text-lg font-semibold text-white mb-3">{{ trans('uploadmanager::quick_egi_metadata') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="egi-title" class="block text-sm font-medium text-gray-200 mb-1">{{ trans('uploadmanager::egi_title') }}</label>
                <input type="text" id="egi-title" name="egi-title" placeholder="{{ trans('uploadmanager::egi_title_placeholder') }}"
                       class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
            <div>
                <label for="egi-collection" class="block text-sm font-medium text-gray-200 mb-1">{{ trans('uploadmanager::egi_collection') }}</label>
                <select id="egi-collection" name="egi-collection"
                        class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-purple-500">
                    <option value="">{{ trans('uploadmanager::select_collection') }}</option>
                    <option value="existing">{{ trans('uploadmanager::existing_collections') }}</option>
                    <option value="new">{{ trans('uploadmanager::create_new_collection') }}</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label for="egi-description" class="block text-sm font-medium text-gray-200 mb-1">{{ trans('uploadmanager::egi_description') }}</label>
                <textarea id="egi-description" name="egi-description" rows="2" placeholder="{{ trans('uploadmanager::egi_description_placeholder') }}"
                          class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-md text-white focus:outline-none focus:ring-2 focus:ring-purple-500"></textarea>
                <p class="text-xs text-gray-400 mt-1">{{ trans('uploadmanager::metadata_notice') }}</p>
            </div>
        </div>
    </div>

    <div class="mt-6 space-y-6">
        <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
            <div class="bg-gradient-to-r from-green-400 to-blue-500 h-3 rounded-full transition-all duration-500" id="progress-bar"></div>
        </div>
        <p class="text-gray-200 text-sm text-center"><span id="progress-text"></span></p>

        <div class="flex items-center justify-center gap-3">
            <input
                class="me-2 h-4 w-8 appearance-none rounded-full bg-gray-600 before:pointer-events-none before:absolute before:h-4 before:w-4 before:rounded-full before:bg-transparent after:absolute after:z-[2] after:-mt-0.5 after:h-6 after:w-6 after:rounded-full after:bg-white after:shadow-md after:transition-all checked:bg-purple-600 checked:after:ms-4 checked:after:bg-purple-400 checked:after:shadow-md hover:cursor-pointer focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-gray-900"
                type="checkbox"
                role="switch"
                id="scanvirus"
                title="{{ trans('uploadmanager::toggle_virus_scan') }}"
            />
            <label
                class="text-red-400 font-medium hover:pointer-events-none"
                id="scanvirus_label"
                for="scanvirus"
            >
            {{ trans('uploadmanager::virus_scan_disabled') }}</label>
        </div>
        <p class="text-gray-200 text-sm text-center"><span id="virus-advise"></span></p>
    </div>

     <div class="mt-10 flex justify-center space-x-6">
        <button type="button" id="uploadBtn" class="relative bg-green-500 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button opacity-50 cursor-not-allowed disabled:hover:bg-green-500 disabled:hover:shadow-none group" aria-label="{{ trans('uploadmanager::save_aria') }}">
            💾 {{ trans('uploadmanager::save_the_files') }}
            <span class="absolute -top-12 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 w-48 text-center pointer-events-none">
                {{ trans('uploadmanager::save_tooltip') }}
            </span>
        </button>
        <button type="button" onclick="cancelUpload()" id="cancelUpload" class="relative bg-red-500 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button opacity-50 cursor-not-allowed disabled:hover:bg-red-500 disabled:hover:shadow-none group" aria-label="{{ trans('uploadmanager::cancel_aria') }}">
            ❌ {{ trans('uploadmanager::cancel') }}
            <span class="absolute -top-12 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 w-48 text-center pointer-events-none">
                {{ trans('uploadmanager::cancel_tooltip') }}
            </span>
        </button>
    </div>

    <div id="collection" class="mt-10 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
        </div>

    <div class="mt-6 flex justify-center">
        <button type="button" onclick="redirectToCollection()" id="returnToCollection" class="relative bg-gray-700 text-white px-8 py-4 rounded-full font-semibold text-lg nft-button hover:bg-gray-600 group" aria-label="{{ trans('uploadmanager::return_aria') }}">
            🔙 {{ trans('uploadmanager::return_to_collection') }}
            <span class="absolute -top-12 left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity duration-300 w-48 text-center pointer-events-none">
                {{ trans('uploadmanager::return_tooltip') }}
            </span>
        </button>
    </div>

    <div class="mt-10 text-center">
        <p class="text-gray-200 text-sm"><span id="scan-progress-text"></span></p>
    </div>

    <div id="status" class="mt-6 text-center text-gray-200 text-lg font-medium"></div>

    <div id="upload-status" class="mt-8 text-center text-gray-200">
        <p id="status-message">{{ trans('uploadmanager::preparing_to_mint') }}</p>
    </div>

</div>
</body>
</html>