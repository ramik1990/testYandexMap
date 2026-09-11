<script setup>
import { computed } from 'vue';
import { formatDateTime, formatNumber, formatRating, STATUS_LABELS } from '../utils/format';

const props = defineProps({
    organization: { type: Object, required: true },
    busy: { type: Boolean, default: false },
});

const emit = defineEmits(['refresh', 'remove']);

const run = computed(() => props.organization.parse_run);
const active = computed(() => ['pending', 'running'].includes(run.value?.status));

function remove() {
    if (window.confirm('Удалить организацию и все собранные отзывы?')) {
        emit('remove');
    }
}
</script>

<template>
    <div class="org-card">
        <div>
            <router-link class="org-card__title" :to="{ name: 'organization', params: { id: organization.id } }">
                {{ organization.title || 'Организация #' + organization.yandex_id }}
            </router-link>
            <div class="muted small">{{ organization.address || organization.source_url }}</div>
            <div class="small" style="margin-top: 6px">
                <span v-if="run" class="badge" :class="`badge--${run.status}`">{{ STATUS_LABELS[run.status] }}<template v-if="active && run.pages_total"> {{ run.progress }}%</template></span>
                · рейтинг {{ formatRating(organization.rating) }}
                · оценок {{ formatNumber(organization.rating_count) }}
                · отзывов {{ formatNumber(organization.review_count) }}
                · собрано {{ formatNumber(organization.reviews_stored) }}
            </div>
            <div v-if="run?.status === 'failed'" class="small" style="color: var(--danger)">{{ run.error_message }}</div>
            <div class="muted small">Обновлено: {{ formatDateTime(organization.last_parsed_at) }}</div>
        </div>
        <div class="org-card__actions">
            <button class="btn btn--secondary btn--small" :disabled="busy || active" @click="emit('refresh')">Обновить</button>
            <button class="btn btn--danger btn--small" :disabled="busy" @click="remove">Удалить</button>
        </div>
    </div>
</template>
