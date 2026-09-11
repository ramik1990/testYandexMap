<script setup>
import { formatDateTime, formatNumber, formatRating } from '../utils/format';

defineProps({ snapshots: { type: Array, required: true } });

const diff = (before, after, format) => (before === null || before === undefined || before === after ? '' : `было ${format(before)}`);
</script>

<template>
    <p v-if="snapshots.length === 0" class="muted">Снимков пока нет: история появится после первого успешного сбора.</p>

    <div v-else class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Рейтинг</th>
                    <th>Оценок</th>
                    <th>Отзывов</th>
                    <th>В базе</th>
                    <th>Изменения отзывов</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="snapshot in snapshots" :key="snapshot.id">
                    <td>{{ formatDateTime(snapshot.created_at) }}</td>
                    <td>{{ formatRating(snapshot.rating) }} <div class="diff">{{ diff(snapshot.changes.before?.rating, snapshot.rating, formatRating) }}</div></td>
                    <td>{{ formatNumber(snapshot.rating_count) }} <div class="diff">{{ diff(snapshot.changes.before?.rating_count, snapshot.rating_count, formatNumber) }}</div></td>
                    <td>{{ formatNumber(snapshot.review_count) }} <div class="diff">{{ diff(snapshot.changes.before?.review_count, snapshot.review_count, formatNumber) }}</div></td>
                    <td>{{ formatNumber(snapshot.reviews_stored) }}</td>
                    <td>+{{ snapshot.changes.added }} новых · {{ snapshot.changes.updated }} изменённых · {{ snapshot.changes.missing }} пропало из выдачи</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
