import { onUnmounted } from 'vue';

export function usePolling(callback, intervalMs) {
    let timer = null;

    function start() {
        if (timer === null) {
            timer = setInterval(callback, intervalMs);
        }
    }

    function stop() {
        clearInterval(timer);
        timer = null;
    }

    onUnmounted(stop);

    return { start, stop };
}
