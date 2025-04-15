/**
 * Helper functions for handling UI operations related to the upload process.
 * These functions include disabling/enabling buttons, removing emojis, updating status messages,
 * and handling image previews.
 */
var __awaiter = (this && this.__awaiter) || function (thisArg, _arguments, P, generator) {
    function adopt(value) { return value instanceof P ? value : new P(function (resolve) { resolve(value); }); }
    return new (P || (P = Promise))(function (resolve, reject) {
        function fulfilled(value) { try { step(generator.next(value)); } catch (e) { reject(e); } }
        function rejected(value) { try { step(generator["throw"](value)); } catch (e) { reject(e); } }
        function step(result) { result.done ? resolve(result.value) : adopt(result.value).then(fulfilled, rejected); }
        step((generator = generator.apply(thisArg, _arguments || [])).next());
    });
};
import { statusMessage, statusDiv, getFiles, uploadBtn, uploadFilebtn, returnToCollectionBtn, cancelUploadBtn, emojiElements, collection } from './domElements';
const files = getFiles() || [];
export function disableButtons() {
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
export function enableButtons() {
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
export function resetButtons() {
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
    removeEmojy();
}
export function removeEmojy() {
    emojiElements.forEach((emoji) => {
        emoji.remove();
    });
}
export function handleImage(index, event, files) {
    const div = document.createElement('div');
    div.classList.add('relative', 'group');
    div.index = index;
    div.innerHTML = `
        <div class="relative group" id="file-${files[index].name}">
        <img src="${event.target.result}" alt="File Image" class="w-full h-40 object-cover rounded-lg shadow-md transition-all duration-300 group-hover:scale-105 z-0">
        <button type="button" id="button-${files[index].name}" onclick="removeFile('${files[index].name}')" class="bg-red-500 text-white absolute bottom-4 px-4 rounded-full text-sm hover:bg-red-700 z-10 hidden">
            ${window.btnDel}
        </button>
        <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-lg">
            <p class="text-white text-sm">File ${files[index].name}</p>
        </div>
    </div>`;
    collection.appendChild(div);
    if (window.envMode === 'local') {
        console.log('File added:', files[index].name);
    }
}
export function updateStatusDiv(message, type = 'info') {
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
export function updateStatusMessage(message, type = 'info') {
    let colorClass;
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
            break;
        default:
            colorClass = 'text-blue-700';
    }
    if (!message.includes("nn")) {
        statusMessage.innerText = message;
        statusMessage.className = `font-bold ${colorClass}`;
    }
}
/**
 * Evidenzia le immagini infette modificandone il bordo.
 *
 * @param fileNameInfected - Il nome del file infetto.
 */
export function highlightInfectedImages(fileNameInfected) {
    // Verifica che fileNameInfected sia una stringa
    if (typeof fileNameInfected !== 'string') {
        console.error('fileNameInfected deve essere una stringa');
        return;
    }
    const infectedImage = document.getElementById(`file-${fileNameInfected}`);
    if (infectedImage) {
        const imgElement = infectedImage.querySelector('img');
        // Verifica che imgElement sia un elemento immagine
        if (imgElement instanceof HTMLImageElement) {
            imgElement.style.border = '3px solid red';
        }
        else {
            console.error('Elemento immagine non trovato');
        }
    }
    else {
        console.error(`Immagine non trovata per il file: ${fileNameInfected}`);
    }
}
/**
 * Funzione per rimuovere un file specifico.
 *
 * @param fileName - Il nome del file da eliminare
 */
export function removeFile(fileName) {
    return __awaiter(this, void 0, void 0, function* () {
        if (fileName) {
            try {
                // deleteTemporaryFileDO() è una funzione che elimina il file temporaneo dal disco esterno
                // Questa funzione è commentata perché il file temporaneo sul disco esterno viene creato solo se è gestita la presignedURL
                // in questa versione dell'applicazione non è gestita la presignedURL, quindi non viene creato il file temporaneo sul disco esterno
                // await deleteTemporaryFileDO(fileName);
                const fileIndex = Array.from(files).findIndex((file) => file.name === fileName);
                if (fileIndex !== -1) {
                    const filesArray = Array.from(files);
                    filesArray.splice(fileIndex, 1);
                    if (window.envMode === 'local') {
                        console.log('file rimanenti:', files);
                    }
                }
                removeImg(fileName);
                if (window.envMode === 'local') {
                    console.log('file presenti DOPO della rimozione:', files);
                }
            }
            catch (error) {
                if (window.envMode === 'local') {
                    console.error('Error deleting temporary file:', error);
                }
                throw new Error(window.deleteFileError);
            }
        }
        else {
            if (window.envMode === 'local') {
                console.log('File:', fileName);
                console.log('File index not removed:', fileName);
                console.log('File name not removed:', fileName);
            }
        }
    });
}
/**
 * Funzione per rimuovere l'immagine dal DOM.
 * @param fileName - Il nome del file da eliminare
 */
export function removeImg(fileName) {
    const remove = document.getElementById(`file-${fileName}`);
    if (window.envMode === 'local') {
        console.log('file rimosso:', `file-${fileName}`);
    }
    if (remove && remove.parentNode) {
        remove.parentNode.removeChild(remove);
        if (files.length === 0) {
            resetButtons();
        }
    }
}
//# sourceMappingURL=uploadUtils.js.map