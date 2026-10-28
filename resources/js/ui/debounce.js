/**
 * Trailing edge debounce, shared by the search and filter widgets.
 *
 * @param {(...args: any[]) => void} callback
 * @param {number} delay
 * @returns {(...args: any[]) => void}
 */
const debounce = (callback, delay = 180) => {
    let timer = null;

    return (...args) => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => callback(...args), delay);
    };
};

export { debounce };