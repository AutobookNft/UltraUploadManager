/**
 * Shows HEIC/HEIF detection message with user-friendly instructions
 */
function showHEICMessage(): void {
    // Check if SweetAlert2 is available
    if (typeof (window as any).Swal === 'undefined') {
        console.warn('⚠️ SweetAlert2 not available, using alert fallback');
        alert('📸 HEIC Format Detected\n\nThe selected file is in HEIC/HEIF format.\nWeb browsers don\'t support this format.\n\nSuggestion: Convert the file to JPG or PNG.');
        return;
    }

    const Swal = (window as any).Swal;
    
    // Use injected HEIC translations from EGI app if available, otherwise fallback to English
    const translations = (window as any).heicTranslations || {
        title: '📸 HEIC Format Detected',
        greeting: 'Hello! 👋 We noticed you\'re trying to upload <strong>HEIC/HEIF</strong> format files.',
        explanation: 'These are great for quality and storage space, but unfortunately web browsers don\'t fully support them yet. 😔',
        solutions_title: '💡 What you can do:',
        solution_ios: '<strong>📱 iPhone/iPad:</strong> Settings → Camera → Formats → "Most Compatible"',
        solution_share: '<strong>🔄 Quick conversion:</strong> Share the photo from Photos app (it will convert automatically)',
        solution_computer: '<strong>💻 On computer:</strong> Open with Preview (Mac) or online converters',
        thanks: 'Thanks for your patience! 💚',
        button: '✨ I Understand'
    };

    console.log('🎯 UUM: Using HEIC translations:', translations);

    const htmlContent = `
        <div style="text-align: left; line-height: 1.6;">
            <p style="margin-bottom: 15px;">${translations.greeting}</p>
            <p style="margin-bottom: 20px;">${translations.explanation}</p>
            
            <div style="margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px; color: #333;">${translations.solutions_title}</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li style="margin-bottom: 8px;">${translations.solution_ios}</li>
                    <li style="margin-bottom: 8px;">${translations.solution_share}</li>
                    <li style="margin-bottom: 8px;">${translations.solution_computer}</li>
                </ul>
            </div>
            
            <p style="margin-bottom: 0; text-align: center; font-style: italic;">${translations.thanks}</p>
        </div>
    `;

    Swal.fire({
        title: translations.title,
        html: htmlContent,
        icon: 'info',
        confirmButtonText: translations.button,
        width: '600px',
        showCancelButton: false,
        allowOutsideClick: true,
        allowEscapeKey: true
    });
}

/**
 * Validates a file based on allowed extensions, MIME types, size limits, and filename format.
 * Includes HEIC/HEIF detection with user-friendly messaging.
 */
export function validateFile(file: File): ValidationResult {
    // Environment check
    if (typeof window === 'undefined' || !window.allowedExtensions || !window.allowedMimeTypes) {
        console.warn('⚠️ Upload validation: Window properties not available');
        return { isValid: false, message: 'Configuration error: upload settings not available' };
    }

    // 🎯 HEIC Detection - NEW FEATURE
    const extension = file.name.split('.').pop()?.toLowerCase() || '';
    if (['heic', 'heif'].includes(extension)) {
        console.log('📸 HEIC file detected:', file.name);
        
        // Use the showHEICMessage function if available
        if (showHEICMessage) {
            showHEICMessage();
        } else {
            console.warn('⚠️ showHEICMessage not available');
        }
        
        return { 
            isValid: false, 
            message: 'HEIC/HEIF files are not supported by web browsers. Please convert to JPG or PNG format.' 
        };
    }

    // Extension validation
    if (!window.allowedExtensions.includes(extension)) {
        const allowedExtensionsList = window.allowedExtensions.join(', ');
        const errorMessage = (window.allowedExtensionsMessage || 'File extension :extension is not allowed. Allowed extensions are: :extensions')
            .replace(':extension', extension)
            .replace(':extensions', allowedExtensionsList);
        return { isValid: false, message: errorMessage };
    }

    // MIME type validation
    if (!window.allowedMimeTypes.includes(file.type)) {
        const errorMessage = (window.allowedMimeTypesMessage || 'File type :type is not allowed. Allowed types are: :mimetypes')
            .replace(':type', file.type)
            .replace(':mimetypes', window.allowedMimeTypesListMessage || 'Allowed types');
        return { isValid: false, message: errorMessage };
    }

    // File size validation
    if (file.size > (window.maxSize || 10 * 1024 * 1024)) {
        const errorMessage = (window.maxSizeMessage || 'File size exceeds the maximum allowed size of :size MB')
            .replace(':size', ((window.maxSize || 10 * 1024 * 1024) / 1024 / 1024).toFixed(2));
        return { isValid: false, message: errorMessage };
    }

    // Nome del file (M-EGI-430).
    //
    // Ammette i nomi VERI: quelli che escono da WhatsApp — «foto (1).jpeg» — dalle macchine
    // fotografiche e dai telefoni, con parentesi, virgole, apostrofi, e-commerciali e lettere
    // accentate di qualunque alfabeto. Prima passavano solo lettere latine senza accento,
    // numeri, punto, trattino basso, trattino e spazio: misurato su una cartella reale, 5
    // immagini su 29 venivano respinte per le sole parentesi, e chi caricava doveva
    // rinominare a mano.
    //
    // Resta fuori ciò che è pericoloso o insensato in un nome, non ciò che è scomodo:
    // separatori di percorso (/ \), caratteri di controllo, e i caratteri vietati dai
    // filesystem (: * ? " < > |). La risalita di cartella («..») non si ferma qui ma alla
    // scrittura, dove il nome viene bonificato: questo controllo è nel browser e chi manda
    // una richiesta costruita a mano non ci passa nemmeno.
    //
    // La stessa regola vive sul server (HasValidation::validateFileName): se le due divergono,
    // si crea il caso peggiore — un file che il browser accetta e il server rifiuta.
    if (!/^[\p{L}\p{N}\s._\-(),'&+#@!]+$/u.test(file.name)) {
        const errorMessage = (window.invalidFileNameMessage || 'Filename :filename contains invalid characters')
            .replace(':filename', file.name);
        return { isValid: false, message: errorMessage };
    }

    // All validations passed
    return { isValid: true };
}
