<script setup>
import AppHeader from './components/AppHeader.vue';
import { useAuth } from './composables/useAuth';

const { user } = useAuth();
</script>

<template>
    <AppHeader v-if="user" />
    <main class="container">
        <router-view />
    </main>
</template>
