<script setup>
import { useRouter } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useAsync } from '../composables/useAsync';

const router = useRouter();
const auth = useAuth();

const { loading, run: logout } = useAsync(async () => {
    await auth.logout();
    await router.replace({ name: 'login' });
});
</script>

<template>
    <header class="header">
        <div class="header__inner">
            <router-link class="header__brand" to="/">Отзывы Яндекс.Карт</router-link>
            <div class="header__user">
                <span>{{ auth.user.value?.email }}</span>
                <button class="btn btn--secondary btn--small" :disabled="loading" @click="logout">Выйти</button>
            </div>
        </div>
    </header>
</template>
