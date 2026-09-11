<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { organizationsApi } from '../api';
import { useAsync } from '../composables/useAsync';
import { usePolling } from '../composables/usePolling';
import AlertMessage from '../components/AlertMessage.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import RatingSummary from '../components/RatingSummary.vue';
import ParseProgress from '../components/ParseProgress.vue';
import ReviewList from '../components/ReviewList.vue';
import PaginationControls from '../components/PaginationControls.vue';
import SnapshotHistory from '../components/SnapshotHistory.vue';

const props = defineProps({ id: { type: Number, required: true } });

const organization = ref(null);
const reviews = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const snapshots = ref([]);
const showHistory = ref(false);

const run = computed(() => organization.value?.parse_run ?? null);
const isParsing = computed(() => ['pending', 'running'].includes(run.value?.status));

const loadOrganization = useAsync(async () => {
    organization.value = (await organizationsApi.get(props.id)).data;
});

const loadReviews = useAsync(async (page = meta.value.current_page) => {
    const response = await organizationsApi.reviews(props.id, page);
    reviews.value = response.data;
    meta.value = response.meta;
});

const loadSnapshots = useAsync(async () => {
    snapshots.value = (await organizationsApi.snapshots(props.id)).data;
});

const refresh = useAsync(async () => {
    organization.value = (await organizationsApi.refresh(props.id)).data;
    polling.start();
});

const polling = usePolling(async () => {
    const fetchedBefore = run.value?.reviews_fetched;
    await loadOrganization.run().catch(() => polling.stop());

    if (!isParsing.value) {
        polling.stop();
        await Promise.all([loadReviews.run(1), loadSnapshots.run()]);
    } else if (run.value?.reviews_fetched !== fetchedBefore) {
        await loadReviews.run();
    }
}, 2500);

watch(isParsing, (active) => (active ? polling.start() : polling.stop()));

async function toggleHistory() {
    showHistory.value = !showHistory.value;

    if (showHistory.value && snapshots.value.length === 0) {
        await loadSnapshots.run();
    }
}

onMounted(async () => {
    await loadOrganization.run().catch(() => null);
    await loadReviews.run(1).catch(() => null);
});
</script>

<template>
    <p><router-link to="/">← К настройкам</router-link></p>

    <LoadingSpinner v-if="loadOrganization.loading.value && !organization" text="Загружаем организацию…" />
    <AlertMessage v-else-if="loadOrganization.error.value && !organization" type="error" :message="loadOrganization.error.value" />

    <template v-if="organization">
        <div class="section-header">
            <div>
                <h1 style="margin-bottom: 4px">{{ organization.title || 'Организация #' + organization.yandex_id }}</h1>
                <div class="muted small">
                    <span v-if="organization.address">{{ organization.address }} · </span>
                    <a :href="organization.yandex_url" target="_blank" rel="noopener">Открыть в Яндекс.Картах</a>
                </div>
            </div>
            <div class="actions">
                <button class="btn btn--secondary" :disabled="isParsing || refresh.loading.value" @click="refresh.run">
                    {{ isParsing ? 'Сбор идёт…' : 'Обновить данные' }}
                </button>
                <button class="btn btn--secondary" @click="toggleHistory">
                    {{ showHistory ? 'Скрыть историю' : 'История изменений' }}
                </button>
            </div>
        </div>

        <AlertMessage v-if="refresh.error.value" type="error" :message="refresh.error.value" />

        <ParseProgress v-if="run" :run="run" />

        <section class="card">
            <RatingSummary :organization="organization" />
        </section>

        <section v-if="showHistory" class="card">
            <h2>История парсинга</h2>
            <LoadingSpinner v-if="loadSnapshots.loading.value" text="Загружаем историю…" />
            <AlertMessage v-else-if="loadSnapshots.error.value" type="error" :message="loadSnapshots.error.value" />
            <SnapshotHistory v-else :snapshots="snapshots" />
        </section>

        <section class="card">
            <div class="section-header">
                <h2 style="margin: 0">Отзывы</h2>
                <span class="muted small">Всего в базе: {{ meta.total }} · страница {{ meta.current_page }} из {{ meta.last_page }}</span>
            </div>

            <ReviewList :reviews="reviews" :loading="loadReviews.loading.value" :error="loadReviews.error.value" :parsing="isParsing" />

            <PaginationControls
                v-if="meta.last_page > 1"
                :page="meta.current_page"
                :last-page="meta.last_page"
                :disabled="loadReviews.loading.value"
                @change="loadReviews.run"
            />
        </section>
    </template>
</template>
