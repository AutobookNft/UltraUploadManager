import {
    statusMessage,
    statusDiv,
    getFiles,
    uploadBtn,
    uploadFilebtn,
    returnToCollectionBtn,
    cancelUploadBtn,
    emojiElements,
    collection,
    progressBar,
    progressText,
    scanProgressText,
    scanvirus,
    scanvirusLabel,
    virusAdvise,
    dropZone,
} from '../index';

// const files = getFiles() || [];

// Lista dinamica nel modulo
let files: File[] = Array.from(getFiles() || []);

/**
 * Aggiorna la lista dinamica con i file attuali
 */
function syncFilesWithSource(): void {
    files = Array.from(getFiles() || []);
}

/**
 * Hides all UI buttons during file processing operations.
 */
export function disableButtons(): void {
    for (let i = 0; i < files.length; i++) {
        const delFileBtn = document.getElementById(`button-${files[i].name}`);
        if (delFileBtn) {
            delFileBtn.style.display = 'none';
        }
    }

    uploadFilebtn.style.display = 'none';
    uploadBtn.style.display = 'none';
    returnToCollectionBtn.style.display = 'none';
    cancelUploadBtn.style.display = 'none';
}

/**
 * Re-enables all UI buttons and makes them interactive.
 */
export function enableButtons(): void {
    for (let i = 0; i < files.length; i++) {
        const delFileBtn = document.getElementById(`button-${files[i].name}`);
        if (delFileBtn) {
            delFileBtn.style.display = 'inline-block';
        }
    }

    cancelUploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    uploadBtn.style.display = 'inline-block';
    returnToCollectionBtn.style.display = 'inline-block';
    cancelUploadBtn.style.display = 'inline-block';
    uploadBtn.disabled = false;
    uploadBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    cancelUploadBtn.disabled = false;
}

/**
 * Resets UI buttons to their initial state.
 * Shows all buttons but disables upload and cancel buttons.
 */
export function resetButtons(): void {
    for (let i = 0; i < files.length; i++) {
        const delFileBtn = document.getElementById(`button-${files[i].name}`);
        if (delFileBtn) {
            delFileBtn.style.display = 'inline-block';
        }
    }

    uploadFilebtn.style.display = 'inline-block';
    uploadBtn.style.display = 'inline-block';
    uploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
    uploadBtn.disabled = true;
    cancelUploadBtn.style.display = 'inline-block';
    cancelUploadBtn.disabled = true;
    cancelUploadBtn.classList.add('opacity-50', 'cursor-not-allowed');
    returnToCollectionBtn.style.display = 'inline-block';
    removeEmoji();
}

/**
 * Removes all emoji elements from the DOM.
 */
export function removeEmoji(): void {
    emojiElements.forEach((emoji) => {
        emoji.remove();
    });
}

/**
 * Creates and displays a preview for an uploaded image file, with a delete button that appears on hover and is clickable.
 *
 * This function generates a dynamic preview of an image file from a FileList and appends it to a collection element.
 * The preview includes an image, a file name overlay, and a delete button. The overlay and button are hidden by default
 * and become visible only when hovering over the preview. The button remains interactive by ensuring proper z-index layering.
 *
 * @param index - The index of the file in the FileList, used to identify the specific file being processed.
 * @param event - The event object from a FileReader, containing the file data result (base64 string) in `event.target.result`.
 * @param files - The FileList containing all uploaded files, from which the file name and other metadata are retrieved.
 */
/**
 * Crea e mostra un'anteprima per un file immagine caricato
 */
export function handleImage(index: number, event: { target: { result: string } }, inputFiles: FileList): void {
    const div = document.createElement('div');
    div.classList.add('relative', 'group');
    (div as any).index = index;

    div.innerHTML = `
        <div class="relative group" id="file-${inputFiles[index].name}">
            <img src="${event.target.result}" alt="File Image" class="w-full h-40 object-cover rounded-lg shadow-md transition-all duration-300 group-hover:scale-105 z-0">
            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg pointer-events-none z-10">
                <p class="text-white text-sm">File ${inputFiles[index].name}</p>
            </div>
            <button type="button" id="button-${inputFiles[index].name}" onclick="removeFile('${inputFiles[index].name}')"
                class="bg-red-500 text-white absolute bottom-4 px-4 rounded-full text-sm hover:bg-red-700 z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                ${window.btnDel}
            </button>
        </div>`;

    collection.appendChild(div);
    if (!files.some(f => f.name === inputFiles[index].name)) { // Evita duplicati
        files.push(inputFiles[index]);
    }

    if (window.envMode === 'local') {
        console.log('File added:', inputFiles[index].name);
        console.log('Current files:', files);
    }
}
/**
 * Adds a status message to the status div with appropriate styling.
 *
 * @param message - The message to display
 * @param type - The type of message (error, success, info, warning)
 */
export function updateStatusDiv(message: string, type: string = 'info'): void {
    let colorClass = '';
    let backgroundClass = '';

    switch (type) {
        case 'error':
            colorClass = 'text-red-700';
            backgroundClass = 'bg-red-200';
            break;
        case 'success':
            colorClass = 'text-green-700';
            backgroundClass = 'bg-green-200';
            break;
        case 'info':
            colorClass = 'text-blue-700';
            backgroundClass = 'bg-blue-200';
            break;
        case 'warning':
            colorClass = 'text-yellow-700';
            backgroundClass = 'bg-yellow-200';
            break;
        default:
            colorClass = 'text-blue-700';
            backgroundClass = 'bg-blue-200';
    }

    if (!message.includes("nn")) {
        statusDiv.innerHTML += `
            <p class="font-bold ${colorClass} ${backgroundClass} px-4 py-2 rounded-lg shadow-md">
                ${message}
            </p>`;
    }
}

/**
 * Updates the main status message with appropriate styling.
 * Adds a pulsating effect for info messages during scanning.
 *
 * @param message - The message to display
 * @param type - The type of message (error, success, warning, info)
 */
export function updateStatusMessage(message: string, type: string = 'info'): void {
    let colorClass = '';
    let animationClass = '';

    if (window.envMode === 'local') {
        console.log(`updateStatusMessage Type: `, type);
    }

    switch (type) {
        case 'error':
            colorClass = 'text-red-700';
            break;
        case 'success':
            colorClass = 'text-green-700';
            break;
        case 'warning':
            colorClass = 'text-yellow-700';
            break;
        case 'info':
            colorClass = 'text-orange-700';
            animationClass = 'animate-pulse';
            break;
        default:
            colorClass = 'text-blue-700';
    }

    statusMessage.innerText = message;
    statusMessage.className = `font-bold ${colorClass} ${animationClass}`;

    if (type !== 'info') {
        statusMessage.classList.remove('animate-pulse');
    }
}

/**
 * Highlights infected images by adding a red border.
 *
 * @param fileNameInfected - The name of the infected file
 */
export function highlightInfectedImages(fileNameInfected: string): void {
    if (typeof fileNameInfected !== 'string') {
        console.error('fileNameInfected must be a string');
        return;
    }

    const infectedImage = document.getElementById(`file-${fileNameInfected}`);

    if (infectedImage) {
        const imgElement = infectedImage.querySelector('img');

        if (imgElement instanceof HTMLImageElement) {
            imgElement.style.border = '3px solid red';
        } else {
            console.error('Image element not found');
        }
    } else {
        console.error(`Image not found for file: ${fileNameInfected}`);
    }
}

/**
 * Removes a file from the list and updates the UI.
 *
 * @param fileName - The name of the file to remove
 */
export async function removeFile(fileName: string): Promise<void> {
    if (fileName) {
        try {
            const fileIndex = files.findIndex((file: File) => file.name === fileName);

            if (fileIndex !== -1) {
                files.splice(fileIndex, 1); // Aggiorna la lista dinamica
                if (window.envMode === 'local') {
                    console.log('Remaining files:', files);
                }
            }

            removeImg(fileName); // Rimuovi dal DOM

            // Opzionale: sincronizza con l'input o window.droppedFiles
            updateInputFiles();

            if (window.envMode === 'local') {
                console.log('Files present AFTER removal:', files);
            }
        } catch (error) {
            if (window.envMode === 'local') {
                console.error('Error deleting temporary file:', error);
            }
            throw new Error(window.deleteFileError);
        }
    }

    /**
     * Updates the file input element or global dropped files with the current list of files.
     *
     * This function synchronizes the file input element with ID "files" or the global `window.droppedFiles`
     * variable with the current state of the `files` array maintained in the module. It uses a `DataTransfer`
     * object to construct a new `FileList` from the `files` array, ensuring that the source of truth (either
     * the input element or the drag-and-drop storage) reflects any additions or removals made to the `files`
     * list through operations like `handleImage` or `removeFile`. This is particularly useful when the UI
     * and the underlying file selection need to remain consistent after dynamic modifications.
     *
     * The function first checks if an `<input type="file" id="files">` element exists and has files. If so,
     * it updates its `files` property. Otherwise, it falls back to updating `window.droppedFiles`, which
     * stores files from drag-and-drop events. If neither source is available, the function does nothing.
     *
     * @remarks
     * - This function assumes the existence of a global `files` array (type `File[]`) that tracks the current
     *   list of files in the module. This array must be updated separately (e.g., in `handleImage` or `removeFile`).
     * - The `DataTransfer` API is used because `FileList` is immutable and cannot be modified directly.
     * - If the input element or `window.droppedFiles` is not present, the function silently exits without errors.
     *
     * @example
     * // After removing a file from the `files` array
     * files.splice(0, 1); // Remove the first file
     * updateInputFiles(); // Sync the input or window.droppedFiles
     * console.log(document.getElementById('files')?.files.length); // Updated length
     *
     * @example
     * // Adding a file and syncing
     * files.push(new File(["content"], "example.txt"));
     * updateInputFiles();
     * console.log(window.droppedFiles?.length); // Reflects the new file if input is not present
     *
     * @sideeffects
     * - Modifies the `files` property of the `<input id="files">` element or the `window.droppedFiles` variable.
     * - Logs debug information to the console if `window.envMode` is set to `'local'`.
     *
     * @dependencies
     * - Requires the global `files` array to be defined and maintained elsewhere in the module.
     * - Relies on the `getFiles` function to determine the initial source of files (input or dropped).
     */
    function updateInputFiles(): void {
        const input = document.getElementById('files') as HTMLInputElement;
        if (input && input.files) {
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            input.files = dataTransfer.files;
        } else if (window.droppedFiles) {
            const dataTransfer = new DataTransfer();
            files.forEach(file => dataTransfer.items.add(file));
            window.droppedFiles = dataTransfer.files;
        }
    }
}

    /**
     * Removes an image element from the DOM.
     *
     * @param fileName - The name of the file whose image should be removed
     */
    export function removeImg(fileName: string): void {
        const remove = document.getElementById(`file-${fileName}`);
        if (window.envMode === 'local') {
            console.log('File removed:', `file-${fileName}`);
        }
        if (remove && remove.parentNode) {
            remove.parentNode.removeChild(remove);
            if (files.length === 0) {
                resetButtons();
            }
        }
    }

    /**
     * Updates the UI to display upload limits in the drop zone.
     *
     * @param limits - The upload limits object containing max_files, max_file_size_formatted, and max_total_size_formatted
     */
    export function updateUploadLimitsDisplay(limits: any): void {
        const limitsInfo = document.createElement('div');
        limitsInfo.className = 'upload-limits-info';
        const maxFilesText = window.translations?.upload?.max_files || 'Max :count file';
        const maxFileSizeText = window.translations?.upload?.max_file_size || 'Max :size per file';
        const maxTotalSizeText = window.translations?.upload?.max_total_size || 'Max :size total';
        limitsInfo.innerHTML = `
            <small class="upload-limits-text">
                ${maxFilesText.replace(':count', limits.max_files)}${limits.max_files !== 1 ? 's' : ''},
                ${maxFileSizeText.replace(':size', limits.max_file_size_formatted)},
                ${maxTotalSizeText.replace(':size', limits.max_total_size_formatted)}
            </small>
        `;
        dropZone.appendChild(limitsInfo);
    }

    /**
     * Validates a list of files against upload limits.
     *
     * @param files - The list of files to validate
     * @returns Object with validation result and error message if invalid
     */
    export function validateFilesAgainstLimits(files: FileList): { valid: boolean; message?: string } {
        const limits = window.uploadLimits;

        if (!limits) {
            console.warn('Upload limits not loaded yet');
            return { valid: true };
        }

        if (files.length > limits.max_files) {
            return {
                valid: false,
                message: window.translations?.upload?.max_files_error
                    ?.replace(':count', limits.max_files)
                    || `You can upload a maximum of ${limits.max_files} files at a time.`
            };
        }

        for (let i = 0; i < files.length; i++) {
            if (files[i].size > limits.max_file_size) {
                return {
                    valid: false,
                    message: window.translations?.upload?.max_file_size_error
                        ?.replace(':name', files[i].name)
                        ?.replace(':size', limits.max_file_size_formatted)
                        || `The file "${files[i].name}" exceeds the maximum allowed size (${limits.max_file_size_formatted}).`
                };
            }
        }

        let totalSize = Array.from(files).reduce((sum, file) => sum + file.size, 0);
        const sizeMargin = limits.size_margin || 1.1; // Usa il valore dal backend, con fallback
        totalSize = Math.round(totalSize * sizeMargin);

        if (totalSize > limits.max_total_size) {
            return {
                valid: false,
                message: window.translations?.upload?.max_total_size_error
                    ?.replace(':size', formatSize(totalSize))
                    ?.replace(':limit', limits.max_total_size_formatted)
                    || `The total size of the files (${formatSize(totalSize)}) exceeds the allowed limit (${limits.max_total_size_formatted}).`
            };
        }

        return { valid: true };
    }

    /**
     * Formats bytes into human-readable size.
     *
     * @param bytes - Size in bytes
     * @returns Formatted size (e.g., "8 MB")
     */
    export function formatSize(bytes: number): string {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let i = 0;

        while (bytes >= 1024 && i < units.length - 1) {
            bytes /= 1024;
            i++;
        }

        return `${bytes.toFixed(2)} ${units[i]}`;
    }

    /**
     * Redirects the user to a collection page.
     * Waits for config to load if not yet available.
     */
    export function redirectToCollection() {
        if (typeof window.URLRedirectToCollection === 'undefined') {
            console.warn('Config not yet loaded, delaying redirectToCollection...');
            document.addEventListener('configLoaded', redirectToCollection, { once: true });
            return;
        }
        window.location.href = window.URLRedirectToCollection;
    }
    
    /**
     * Redirects the user to a collection page.
     * Waits for config to load if not yet available.
     */
    export function redirectToURL() {
        if (typeof window.uploadRedirectToUrl === 'undefined') {
            console.warn('Config not yet loaded, delaying redirectToCollection...');
            document.addEventListener('configLoaded', redirectToURL, { once: true });
            return;
        }
        window.location.href = window.uploadRedirectToUrl;
    }
