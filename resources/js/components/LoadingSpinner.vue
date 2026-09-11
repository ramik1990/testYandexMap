<script setup>
defineProps({ text: { type: String, default: 'Загрузка…' } });
</script>

<template>
    <div class="spinner">{{ text }}</div>
</template>
