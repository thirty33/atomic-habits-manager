export function debounce(fn, delay) {
    let timer = null;

    const debounced = (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };

    debounced.cancel = () => clearTimeout(timer);

    debounced.flush = () => {
        clearTimeout(timer);
        fn();
    };

    return debounced;
}