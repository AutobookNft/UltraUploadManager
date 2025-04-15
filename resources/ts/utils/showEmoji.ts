/**
 * Emoji Feedback Module
 *
 * Displays emoticon feedback based on upload results
 * Provides visual indication of success, partial success, or failure
 */

import GirlHappy from '@ultra-images/GirlHappy.png';
import GirlDisp from '@ultra-images/GirlDisp.png';
import GirlSad from '@ultra-images/GirlSad.png';

/**
 * Available emoji feedback types
 */
type EmojiType = 'success' | 'someError' | 'completeFailure';

/**
 * Displays an emoji based on the provided type
 * Creates a div with an image representing the specified state
 *
 * @param {EmojiType} type - The type of emoji to display
 * @returns {Promise<void>}
 * @throws {Error} If the type is invalid or configuration isn't loaded
 */
export async function showEmoji(type: EmojiType): Promise<void> {
    const div: HTMLDivElement = document.createElement('div');
    const statusDiv: HTMLElement = document.getElementById('status') as HTMLElement;
    div.classList.add('relative', 'group');

    // Ensure global variables are loaded
    if (!(window as any).emogyHappy) {
        console.error('Global configuration not loaded. Make sure JSON has been fetched.');
        throw new Error('Global configuration not loaded. Make sure JSON has been fetched.');
    }

    console.log('Showing emoji:', type);
    // Map of Vite-imported images
    const imageMap: Record<EmojiType, string> = {
        success: GirlHappy,
        someError: GirlDisp,
        completeFailure: GirlSad,
    };

    // Configure emoji based on type
    const config = getEmojiConfig(type);

    // Set timeout to show text if image doesn't load within 5 seconds
    const timeoutId: number = window.setTimeout(() => {
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
}

/**
 * Gets configuration for a specific emoji type
 *
 * @param {EmojiType} type - The emoji type
 * @returns {Object} Configuration object with image source, alt text and result
 * @throws {Error} If the type is invalid
 */
function getEmojiConfig(type: EmojiType): {
    imageSrc: string;
    altText: string;
    result: string
} {
    const resultOk: string = 'OK';
    const imageMap: Record<EmojiType, string> = {
        success: GirlHappy,
        someError: GirlDisp,
        completeFailure: GirlSad,
    };

    switch (type) {
        case 'success':
            return {
                imageSrc: imageMap[type],
                altText: (window as any).emogyHappy,
                result: resultOk
            };
        case 'someError':
            return {
                imageSrc: imageMap[type],
                altText: (window as any).emogySad,
                result: (window as any).someError
            };
        case 'completeFailure':
            return {
                imageSrc: imageMap[type],
                altText: (window as any).emogyAngry,
                result: (window as any).completeFailure
            };
        default:
            throw new Error('Invalid emoji type');
    }
}
