import { ref } from 'vue';
import { errorMessage } from '../utils/format';

export function useAsync(fn) {
    const loading = ref(false);
    const error = ref('');

    async function run(...args) {
        loading.value = true;
        error.value = '';

        try {
            return await fn(...args);
        } catch (e) {
            error.value = errorMessage(e);
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return { loading, error, run };
}
