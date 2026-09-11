<script setup>
import { reactive } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuth } from '../composables/useAuth';
import { useAsync } from '../composables/useAsync';
import AlertMessage from '../components/AlertMessage.vue';

const router = useRouter();
const route = useRoute();
const auth = useAuth();
const form = reactive({ email: '', password: '' });

const { loading, error, run: submit } = useAsync(async () => {
    await auth.login(form);
    await router.replace(route.query.redirect || { name: 'settings' });
});
</script>

<template>
    <div class="card card--narrow">
        <h1>Вход</h1>
        <p class="muted small">Сервис отзывов Яндекс.Карт. Регистрации нет, используйте учётную запись из сида.</p>

        <form @submit.prevent="submit">
            <div class="field">
                <label for="email">Email</label>
                <input id="email" v-model="form.email" class="input" type="email" autocomplete="username" required>
            </div>
            <div class="field">
                <label for="password">Пароль</label>
                <input id="password" v-model="form.password" class="input" type="password" autocomplete="current-password" required>
            </div>

            <AlertMessage v-if="error" type="error" :message="error" />

            <button class="btn" type="submit" :disabled="loading">
                {{ loading ? 'Входим…' : 'Войти' }}
            </button>
        </form>
    </div>
</template>
