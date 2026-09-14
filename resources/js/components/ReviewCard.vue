<script setup>
import StarRating from './StarRating.vue';
import { formatDate } from '../utils/format';

defineProps({ review: { type: Object, required: true } });
</script>

<template>
    <article class="review">
        <div class="review__header">
            <img v-if="review.author.avatar" class="review__avatar" :src="review.author.avatar" alt="" loading="lazy">
            <div v-else class="review__avatar">{{ review.author.name.slice(0, 1).toUpperCase() }}</div>
            <div>
                <div class="review__author">{{ review.author.name }}</div>
                <div class="review__meta">
                    <StarRating v-if="review.rating" :value="review.rating" />
                    <span v-else class="muted">без оценки</span>
                    <span v-if="review.published_at">{{ formatDate(review.published_at) }}</span>
                    <span v-if="review.author.level">{{ review.author.level }}</span>
                </div>
            </div>
        </div>

        <p v-if="review.text" class="review__text">{{ review.text }}</p>
        <p v-else class="review__text muted">Без текста</p>

        <div class="review__reactions">👍 {{ review.likes }} · 👎 {{ review.dislikes }}</div>

        <div v-if="review.business_reply" class="review__reply">
            <div class="review__reply-title">Ответ организации · {{ formatDate(review.business_reply.at) }}</div>
            <div style="white-space: pre-line">{{ review.business_reply.text }}</div>
        </div>
    </article>
</template>
