<script setup>
import { computed } from 'vue';

const props = defineProps({ value: { type: Number, default: 0 } });

const filled = computed(() => Math.round(props.value));
</script>

<template>
    <span class="stars" :aria-label="`Оценка ${value} из 5`">
        <span v-for="star in 5" :key="star" :class="{ 'stars__star--empty': star > filled }">★</span>
    </span>
</template>
