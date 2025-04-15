// resources/ts/validation.ts
if (window.envMode === 'local') {
    console.log('Dentro resources/ts/validation.ts');
}
import Swal from 'sweetalert2';
export function validateFile(file) {
    var _a;
    const extension = (_a = file.name.split('.').pop()) === null || _a === void 0 ? void 0 : _a.toLowerCase();
    if (!window.allowedExtensions.includes(extension)) {
        const allowedExtensionsList = window.allowedExtensions.join(', ');
        const errorMessage = window.allowedExtensionsMessage
            .replace(':extension', extension)
            .replace(':extensions', allowedExtensionsList);
        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleExtensionNotAllowedMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return { isValid: false, message: errorMessage };
    }
    if (!window.allowedMimeTypes.includes(file.type)) {
        const errorMessage = window.allowedMimeTypesMessage
            .replace(':type', file.type)
            .replace(':mimetypes', window.allowedMimeTypesListMessage);
        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleFileTypeNotAllowedMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return { isValid: false, message: errorMessage };
    }
    if (file.size > window.maxSize) {
        const errorMessage = window.maxSizeMessage.replace(':size', (window.maxSize / 1024).toString());
        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleFileSizeExceedsMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return { isValid: false, message: errorMessage };
    }
    if (!validateFileName(file.name)) {
        const errorMessage = window.invalidFileNameMessage.replace(':filename', file.name);
        // Mostra il messaggio di errore all'utente usando Swal
        Swal.fire({
            title: window.titleInvalidFileNameMessage,
            text: errorMessage,
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return { isValid: false, message: errorMessage };
    }
    return { isValid: true };
}
export function validateFileName(fileName) {
    const regex = /^[\w\-. ]+$/;
    return regex.test(fileName);
}
//# sourceMappingURL=validation.js.map