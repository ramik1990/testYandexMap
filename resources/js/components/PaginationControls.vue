<script setup>
import { computed } from 'vue';

const props = defineProps({
    page: { type: Number, required: true },
    lastPage: { type: Number, required: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['change']);

const pages = computed(() => {
    const window = new Set([1, props.lastPage, props.page - 1, props.page, props.page + 1]);

    return [...window].filter((p) => p >= 1 && p <= props.lastPage).sort((a, b) => a - b);
});

const go = (page) => page !== props.page && page >= 1 && page <= props.lastPage && emit('change', page);
</script>

<template>
    <nav class="pagination" aria-label="Страницы отзывов">
        <button class="btn btn--secondary btn--small" :disabled="disabled || page <= 1" @click="go(page - 1)">← Назад</button>
        <div class="pagination__pages">
            <template v-for="(p, index) in pages" :key="p">
                <span v-if="index > 0 && p - pages[index - 1] > 1" class="muted">…</span>
                <button class="pagination__page" :class="{ 'pagination__page--active': p === page }" :disabled="disabled" @click="go(p)">{{ p }}</button>
            </template>
        </div>
        <button class="btn btn--secondary btn--small" :disabled="disabled || page >= lastPage" @click="go(page + 1)">Вперёд →</button>
    </nav>
</template>
