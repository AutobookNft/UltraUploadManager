/**
 * Emoji Feedback Module
 *
 * Displays emoticon feedback based on upload results
 * Provides visual indication of success, partial success, or failure
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
import GirlHappy from '@ultra-images/GirlHappy.png';
import GirlDisp from '@ultra-images/GirlDisp.png';
import GirlSad from '@ultra-images/GirlSad.png';
/**
 * Displays an emoji based on the provided type
 * Creates a div with an image representing the specified state
 *
 * @param {EmojiType} type - The type of emoji to display
 * @returns {Promise<void>}
 * @throws {Error} If the type is invalid or configuration isn't loaded
 */
export function showEmoji(type) {
    return __awaiter(this, void 0, void 0, function* () {
        const div = document.createElement('div');
        const statusDiv = document.getElementById('status');
        div.classList.add('relative', 'group');
        // Ensure global variables are loaded
        if (!window.emogyHappy) {
            console.error('Global configuration not loaded. Make sure JSON has been fetched.');
            throw new Error('Global configuration not loaded. Make sure JSON has been fetched.');
        }
        console.log('Showing emoji:', type);
        // Map of Vite-imported images
        const imageMap = {
            success: GirlHappy,
            someError: GirlDisp,
            completeFailure: GirlSad,
        };
        // Configure emoji based on type
        const config = getEmojiConfig(type);
        // Set timeout to show text if image doesn't load within 5 seconds
        const timeoutId = window.setTimeout(() => {
            div.innerHTML = `
            <div class="flex items-center justify-center mt-4">
                <span class="font-bold text-4xl">${config.result}</span>
            </div>
        `;
            statusDiv.appendChild(div);
        }, 5000);
        // Build DOM with the image
        div.innerHTML = `
        <div class="flex items-center justify-center mt-4">
            <img src="${config.imageSrc}"
                alt="${config.altText}"
                id="emojy"
                title="${config.altText}"
                class="w-40 h-40 object-cover rounded-full shadow-md transition-all duration-300 group-hover:scale-105 z-0">
        </div>
    `;
        statusDiv.appendChild(div);
        clearTimeout(timeoutId);
    });
}
/**
 * Gets configuration for a specific emoji type
 *
 * @param {EmojiType} type - The emoji type
 * @returns {Object} Configuration object with image source, alt text and result
 * @throws {Error} If the type is invalid
 */
function getEmojiConfig(type) {
    const resultOk = 'OK';
    const imageMap = {
        success: GirlHappy,
        someError: GirlDisp,
        completeFailure: GirlSad,
    };
    switch (type) {
        case 'success':
            return {
                imageSrc: imageMap[type],
                altText: window.emogyHappy,
                result: resultOk
            };
        case 'someError':
            return {
                imageSrc: imageMap[type],
                altText: window.emogySad,
                result: window.someError
            };
        case 'completeFailure':
            return {
                imageSrc: imageMap[type],
                altText: window.emogyAngry,
                result: window.completeFailure
            };
        default:
            throw new Error('Invalid emoji type');
    }
}
//# sourceMappingURL=showEmoji.js.map