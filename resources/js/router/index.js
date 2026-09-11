import { createRouter, createWebHistory } from 'vue-router';
import { useAuth } from '../composables/useAuth';

const routes = [
    { path: '/login', name: 'login', component: () => import('../pages/LoginPage.vue'), meta: { guest: true } },
    { path: '/', name: 'settings', component: () => import('../pages/SettingsPage.vue'), meta: { auth: true } },
    {
        path: '/organizations/:id(\d+)',
        name: 'organization',
        component: () => import('../pages/OrganizationPage.vue'),
        props: (route) => ({ id: Number(route.params.id) }),
        meta: { auth: true },
    },
    { path: '/:pathMatch(.*)*', redirect: '/' },
];

const router = createRouter({ history: createWebHistory(), routes });

router.beforeEach(async (to) => {
    const auth = useAuth();
    await auth.ensureLoaded();

    if (to.meta.auth && !auth.user.value) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.guest && auth.user.value) {
        return { name: 'settings' };
    }
});

export default router;
