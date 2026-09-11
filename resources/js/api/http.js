import axios from 'axios';

const http = axios.create({
    baseURL: '/',
    withCredentials: true,
    withXSRFToken: true,
    headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
});

let csrfReady = null;

export function ensureCsrf() {
    csrfReady ??= http.get('/sanctum/csrf-cookie').catch((e) => {
        csrfReady = null;
        throw e;
    });

    return csrfReady;
}

http.interceptors.request.use(async (config) => {
    if (config.method !== 'get') {
        await ensureCsrf();
    }

    return config;
});

http.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 419) {
            csrfReady = null;
        }

        return Promise.reject(error);
    },
);

export default http;
