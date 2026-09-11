import http from './http';

const data = (promise) => promise.then((response) => response.data);

export const authApi = {
    login: (credentials) => data(http.post('/login', credentials)),
    logout: () => http.post('/logout'),
    me: () => data(http.get('/api/user')),
};

export const organizationsApi = {
    list: () => data(http.get('/api/organizations')),
    create: (url) => data(http.post('/api/organizations', { url })),
    get: (id) => data(http.get(`/api/organizations/${id}`)),
    refresh: (id) => data(http.put(`/api/organizations/${id}`)),
    remove: (id) => http.delete(`/api/organizations/${id}`),
    reviews: (id, page) => data(http.get(`/api/organizations/${id}/reviews`, { params: { page } })),
    snapshots: (id) => data(http.get(`/api/organizations/${id}/snapshots`)),
};
