<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { organizationsApi } from '../api';
import { useAsync } from '../composables/useAsync';
import { usePolling } from '../composables/usePolling';
import AlertMessage from '../components/AlertMessage.vue';
import LoadingSpinner from '../components/LoadingSpinner.vue';
import OrganizationCard from '../components/OrganizationCard.vue';

const router = useRouter();
const url = ref('');
const organizations = ref([]);

const list = useAsync(async () => {
    organizations.value = (await organizationsApi.list()).data;
});

const create = useAsync(async () => {
    const organization = (await organizationsApi.create(url.value)).data;
    url.value = '';
    await router.push({ name: 'organization', params: { id: organization.id } });
});

const action = useAsync(async (fn) => {
    await fn();
    await list.run();
});

const hasActiveRuns = () => organizations.value.some((o) => ['pending', 'running'].includes(o.parse_run?.status));

const polling = usePolling(async () => {
    await list.run().catch(() => null);

    if (!hasActiveRuns()) {
        polling.stop();
    }
}, 3000);

onMounted(async () => {
    await list.run().catch(() => null);

    if (hasActiveRuns()) {
        polling.start();
    }
});
</script>

<template>
    <h1>Настройки</h1>

    <section class="card">
        <h2>Подключить организацию</h2>
        <p class="muted small">
            Вставьте ссылку на карточку организации в Яндекс.Картах, например
            <code>https://yandex.ru/maps/org/yandeks/1124715036/</code>. После сохранения отзывы и рейтинг
            подтянутся в фоне.
        </p>

        <form class="form-row" @submit.prevent="create.run">
            <input
                v-model="url"
                class="input"
                type="url"
                placeholder="https://yandex.ru/maps/org/…"
                required
                :disabled="create.loading.value"
            >
            <button class="btn" type="submit" :disabled="create.loading.value">
                {{ create.loading.value ? 'Сохраняем…' : 'Сохранить и собрать отзывы' }}
            </button>
        </form>

        <AlertMessage v-if="create.error.value" type="error" :message="create.error.value" />
    </section>

    <section class="card">
        <div class="section-header">
            <h2>Подключённые организации</h2>
            <button class="btn btn--secondary btn--small" :disabled="list.loading.value" @click="list.run">Обновить список</button>
        </div>

        <AlertMessage v-if="list.error.value" type="error" :message="list.error.value" />
        <AlertMessage v-if="action.error.value" type="error" :message="action.error.value" />
        <LoadingSpinner v-if="list.loading.value && organizations.length === 0" text="Загружаем организации…" />

        <p v-else-if="organizations.length === 0" class="muted">Пока ничего не подключено.</p>

        <div v-else class="org-list">
            <OrganizationCard
                v-for="organization in organizations"
                :key="organization.id"
                :organization="organization"
                :busy="action.loading.value"
                @refresh="action.run(() => organizationsApi.refresh(organization.id)).then(polling.start)"
                @remove="action.run(() => organizationsApi.remove(organization.id))"
            />
        </div>
    </section>
</template>
