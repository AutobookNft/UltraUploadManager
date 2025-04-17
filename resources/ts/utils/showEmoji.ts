/**
 * 📜 Oracode TypeScript Module: Emoji Feedback Display
 *
 * @package     Ultra\UploadManager\Resources\TS\Utils
 * @version     1.1.0 // Refactored for Oracode v1.5.0, Vite asset imports, and translation integration.
 * @author      Fabio Cherici <fabiocherici@gmail.com>
 * @copyright   2024 Fabio Cherici
 * @license     MIT
 */

// --- Asset Imports (Managed by Vite) ---
// These imports ensure Vite processes the images and provides correct public paths.
import GirlHappy from '@ultra-images/GirlHappy.png'; // Asset for success state
import GirlDisp from '@ultra-images/GirlDisp.png';  // Asset for partial error state
import GirlSad from '@ultra-images/GirlSad.png';   // Asset for complete failure state
// --- End Asset Imports ---

/**
 * 🏷️ Defines the possible states for emoji feedback.
 */
type EmojiType = 'success' | 'someError' | 'completeFailure';

/**
 * 🎭 Displays visual feedback (an emoji image) in the designated status area
 * based on the overall outcome of the file upload process.
 *
 * --- Core Logic ---
 * 1.  Identifies the target DOM element (`#status`) where the emoji should be appended.
 * 2.  Determines the correct image source (`imageSrc`) and alternative text (`altText`)
 *     based on the provided `type` using the `getEmojiConfig` helper function.
 *     - Image sources are derived from direct Vite imports (`@ultra-images/...`).
 *     - Alt texts are retrieved from globally available JavaScript translations
 *       (expected at `window.translations.js`), falling back to default English strings.
 * 3.  Creates a new `div` element to contain the emoji image.
 * 4.  Sets the `innerHTML` of the div to an `<img>` tag using the determined `imageSrc` and `altText`.
 * 5.  Appends the new div (containing the image) to the status element.
 * 6.  Includes a (commented out) timeout fallback to display text if the image fails to load quickly,
 *     though this is generally less necessary with Vite's reliable asset handling.
 * --- End Core Logic ---
 *
 * @param {EmojiType} type The outcome status determining which emoji to display
 *                         ('success', 'someError', 'completeFailure').
 * @returns {Promise<void>} A promise that resolves when the emoji display logic is initiated.
 *                          Note: Does not wait for image loading itself.
 * @throws {Error} If the target status DOM element (`#status`) cannot be found.
 *
 * @sideEffect Modifies the DOM by appending an image element to the element with ID 'status'.
 * @dependency Relies on the DOM element `#status` being present.
 * @dependency Relies on global JavaScript translations being available at `window.translations.js`
 *             for alternative text (keys: `emoji_happy`, `emoji_sad`, `emoji_angry`).
 * @see getEmojiConfig() Helper function to retrieve configuration based on type.
 * @see Vite Asset Handling: https://vitejs.dev/guide/features.html#static-assets
 */
export async function showEmoji(type: EmojiType): Promise<void> {
    const statusDiv = document.getElementById('status');

    // --- Input Validation ---
    if (!statusDiv) {
        console.error('[UUM showEmoji] Target DOM element #status not found.');
        // Optionally use UEM here if frontend error reporting is set up
        // reportErrorToUEM('UUM_FRONTEND_DOM_MISSING', { elementId: 'status' });
        throw new Error("Target DOM element #status for emoji feedback not found.");
    }
    // --- End Input Validation ---

    console.debug(`[UUM showEmoji] Showing emoji for type: ${type}`); // Use debug level

    // --- Create Emoji Element ---
    const div = document.createElement('div');
    // Add styling classes consistent with previous implementation or application design
    div.classList.add('relative', 'group', 'flex', 'items-center', 'justify-center', 'mt-4'); // Added flex for centering

    // Get image source and alt text using the corrected helper
    const config = getEmojiConfig(type);

    /* // Fallback timeout - Generally not needed with Vite but kept for reference
    const timeoutId: number = window.setTimeout(() => {
        console.warn('[UUM showEmoji] Image load timeout reached, displaying text fallback.');
        div.innerHTML = `<span class="font-bold text-4xl">${config.result}</span>`;
        // Ensure it's appended even on timeout if the element hasn't been added yet
        if (!statusDiv.contains(div)) {
           statusDiv.appendChild(div);
        }
    }, 5000);
    */

    // Build DOM with the image using imported asset paths
    div.innerHTML = `
        <img src="${config.imageSrc}"
             alt="${config.altText}"
             title="${config.altText}"
             id="emojy"
             class="w-40 h-40 object-cover rounded-full shadow-md transition-all duration-300 group-hover:scale-105 z-0">
    `;
    // --- End Create Emoji Element ---

    // Append to the DOM
    statusDiv.appendChild(div);

    // Clear the timeout if the element was created successfully
    // clearTimeout(timeoutId); // Uncomment if using the timeout fallback
}

/**
 * ⚙️ Helper function to retrieve configuration (image source, alt text, result text)
 *    for a specific emoji type based on the upload outcome.
 *
 * --- Core Logic ---
 * 1. Defines a map (`imageMap`) associating `EmojiType` keys with the Vite-imported image paths.
 * 2. Retrieves JavaScript translations from the global `window.translations.js` object (with fallbacks).
 * 3. Uses a switch statement on the input `type` to determine the correct `imageSrc` from `imageMap`
 *    and the appropriate `altText` and `resultText` from the translations.
 * 4. Returns an object containing these three configuration values.
 * --- End Core Logic ---
 *
 * @param {EmojiType} type The type of emoji feedback required ('success', 'someError', 'completeFailure').
 * @returns {{ imageSrc: string; altText: string; result: string }} An object containing:
 *          - `imageSrc`: The public path to the appropriate emoji image (from Vite import).
 *          - `altText`: The localized alternative text for the image (from translations or fallback).
 *          - `result`: A short localized result text (currently hardcoded or from translations).
 * @internal Helper function for `showEmoji`. Relies on global `window.translations`.
 */
function getEmojiConfig(type: EmojiType): { imageSrc: string; altText: string; result: string } {
    // Map types to the imported image variables (paths resolved by Vite)
    const imageMap: Record<string, string> = { // Use string index for safety with default case
        success: GirlHappy,
        someError: GirlDisp,
        completeFailure: GirlSad,
    };

    // Access JS translations safely, providing defaults
    const jsTranslations = (window as any).translations?.js ?? {};
    const defaultAltText = 'Upload status indicator';
    const defaultResultText = 'Status';

    let altText: string;
    let resultText: string; // This was previously used in the timeout fallback

    switch (type) {
        case 'success':
            altText = jsTranslations.emoji_happy || 'Upload completed successfully';
            resultText = jsTranslations.result_ok || 'OK'; // Example using translation
            break;
        case 'someError':
            altText = jsTranslations.emoji_sad || 'Some files had errors during upload';
            resultText = jsTranslations.some_error || 'Partial Error'; // Example using translation
            break;
        case 'completeFailure':
            altText = jsTranslations.emoji_angry || 'Upload completely failed';
            resultText = jsTranslations.complete_failure || 'Failed'; // Example using translation
            break;
        default:
            // Handle unexpected type gracefully
            console.warn(`[UUM getEmojiConfig] Received unexpected emoji type: ${type}. Using defaults.`);
            altText = defaultAltText;
            resultText = defaultResultText;
            // Return a default image or handle as error? For now, maybe default to sad?
            // imageSrc = imageMap['someError'] ?? ''; // Example fallback image
            break; // Exit switch
    }

    // Get the specific image source, provide a fallback if type somehow invalid
    const imageSrc = imageMap[type] ?? imageMap['someError'] ?? ''; // Fallback to 'someError' image if type invalid

    return {
        imageSrc: imageSrc,
        altText: altText,
        result: resultText // Return the determined result text
    };
}