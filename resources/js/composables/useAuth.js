import { computed, ref } from 'vue';
import { authApi } from '../api';

const user = ref(null);
const loaded = ref(false);

export function useAuth() {
    async function ensureLoaded() {
        if (loaded.value) {
            return;
        }

        try {
            user.value = (await authApi.me()).data;
        } catch {
            user.value = null;
        } finally {
            loaded.value = true;
        }
    }

    async function login(credentials) {
        user.value = (await authApi.login(credentials)).data;
    }

    async function logout() {
        await authApi.logout();
        user.value = null;
    }

    return { user, isAuthenticated: computed(() => user.value !== null), ensureLoaded, login, logout };
}
