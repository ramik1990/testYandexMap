<script setup>
import { computed } from 'vue';
import { ERROR_LABELS, STATUS_LABELS } from '../utils/format';

const props = defineProps({ run: { type: Object, required: true } });

const active = computed(() => ['pending', 'running'].includes(props.run.status));
const failed = computed(() => props.run.status === 'failed');
const partial = computed(() => props.run.status === 'partial');
const errorTitle = computed(() => ERROR_LABELS[props.run.error_code] ?? ERROR_LABELS.unexpected);
</script>

<template>
    <div v-if="active" class="alert alert--info">
        <div>
            <strong>{{ STATUS_LABELS[run.status] }}</strong>
            <span v-if="run.pages_total"> · страница {{ run.pages_done }} из {{ run.pages_total }}, получено {{ run.reviews_fetched }} отзывов</span>
            <span v-if="run.attempts > 1"> · попытка {{ run.attempts }}</span>
        </div>
        <div class="progress"><div class="progress__bar" :style="{ width: `${run.progress}%` }"></div></div>
        <div v-if="run.error_message" class="small">Прошлая попытка: {{ run.error_message }}</div>
    </div>

    <div v-else-if="partial" class="alert alert--warning">
        <strong>{{ STATUS_LABELS.partial }}</strong>
        <div class="small">{{ errorTitle }}. {{ run.error_message }}</div>
        <div class="small muted">
            Собрано отзывов: {{ run.reviews_fetched }}<span v-if="run.pages_total">, страниц {{ run.pages_done }} из {{ run.pages_total }}</span>.
            Рейтинг и счётчики обновлены. Нажмите «Обновить данные», чтобы попробовать собрать остальное.
        </div>
    </div>

    <div v-else-if="failed" class="alert alert--error">
        <strong>{{ errorTitle }}</strong>
        <div class="small">{{ run.error_message }}</div>
        <div class="small muted">Попыток: {{ run.attempts }}. Нажмите «Обновить данные», чтобы запустить сбор заново.</div>
    </div>
</template>
