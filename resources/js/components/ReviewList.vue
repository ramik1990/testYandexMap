<script setup>
import AlertMessage from './AlertMessage.vue';
import LoadingSpinner from './LoadingSpinner.vue';
import ReviewCard from './ReviewCard.vue';

defineProps({
    reviews: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    parsing: { type: Boolean, default: false },
});
</script>

<template>
    <AlertMessage v-if="error" type="error" :message="error" />
    <LoadingSpinner v-if="loading && reviews.length === 0" text="Загружаем отзывы…" />

    <p v-else-if="reviews.length === 0" class="muted">
        {{ parsing ? 'Отзывы появятся по мере сбора…' : 'Отзывов пока нет.' }}
    </p>

    <div v-else :style="{ opacity: loading ? 0.6 : 1 }">
        <ReviewCard v-for="review in reviews" :key="review.id" :review="review" />
    </div>
</template>
