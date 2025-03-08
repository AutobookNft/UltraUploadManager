/**
 * Function to display an emoji based on the type provided.
 * This function dynamically creates a div element containing an image that represents a specific state.
 * The image source is first attempted to load from a CDN; if it fails, a fallback URL is used.
 * If the image takes longer than 5 seconds to load, a text "OK" is displayed instead of the image.
 * The function handles three types of states: success, someError, and completeFailure.
 *
 * @param {string} type - The type of emoji to display. It can be "success", "someError", or "completeFailure".
 * @throws Will throw an error if the type provided is not valid.
 */
export async function showEmoji(type: string): Promise<void> {
    const div: HTMLDivElement = document.createElement('div');
    const statusDiv: HTMLElement = document.getElementById('status') as HTMLElement;
    div.classList.add('relative', 'group');

    let emojyPng: string = '';
    let fallbackUrl: string = '';
    let altType: string = '';
    let result: string = '';

    if (type === "success") {
        altType = (window as any).emogyHappy;
        result = "OK";
        emojyPng = "https://cdn.nftflorence.com/assets/images/icons/GirlHappy.png";
        fallbackUrl = "https://frangettediskspace.fra1.digitaloceanspaces.com/assets/images/icons/GirlHappy.png";
    } else if (type === "someError") {
        altType = (window as any).emogySad;
        result = window.someError;
        emojyPng = "https://cdn.nftflorence.com/assets/images/icons/GirlDisp.png";
        fallbackUrl = "https://frangettediskspace.fra1.digitaloceanspaces.com/assets/images/icons/GirlDisp.png";
    } else if (type === "completeFailure") {
        altType = (window as any).emogyAngry;
        result = window.completeFailure;
        emojyPng = "https://cdn.nftflorence.com/assets/images/icons/GirlSad.png";
        fallbackUrl = "https://frangettediskspace.fra1.digitaloceanspaces.com/assets/images/icons/GirlSad.png";
    } else {
        throw new Error('Tipo non valido');
    }

    const timeoutId: number = window.setTimeout(() => {
        div.innerHTML = `
            <div class="flex items-center justify-center mt-4">
                <span class="font-bold text-4xl">${result}</span>
            </div>
        `;
        statusDiv.appendChild(div);
    }, 3000);

    try {
        const response: Response = await fetch(emojyPng, { method: 'HEAD' });
        if (!response.ok) {
            emojyPng = fallbackUrl;
        }
    } catch (error) {
        emojyPng = fallbackUrl;
    }

    div.innerHTML = `
        <div class="flex items-center justify-center mt-4">
            <img src="${emojyPng}"
            alt="${altType}"
            id="emojy"
            title="${altType}"
            class="w-40 h-40 object-cover rounded-full shadow-md transition-all duration-300 group-hover:scale-105 z-0">
        </div>
    `;

    statusDiv.appendChild(div);
    clearTimeout(timeoutId);
}
