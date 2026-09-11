<script setup>
import StarRating from './StarRating.vue';
import { formatDateTime, formatNumber, formatRating } from '../utils/format';

defineProps({ organization: { type: Object, required: true } });
</script>

<template>
    <div class="summary">
        <div>
            <div class="summary__rating">{{ formatRating(organization.rating) }}</div>
            <StarRating :value="organization.rating ?? 0" />
        </div>
        <div class="summary__stats">
            <div class="stat">
                <div class="stat__value">{{ formatNumber(organization.rating_count) }}</div>
                <div class="stat__label">оценок на Яндексе</div>
            </div>
            <div class="stat">
                <div class="stat__value">{{ formatNumber(organization.review_count) }}</div>
                <div class="stat__label">отзывов на Яндексе</div>
            </div>
            <div class="stat">
                <div class="stat__value">{{ formatNumber(organization.reviews_stored) }}</div>
                <div class="stat__label">отзывов собрано</div>
            </div>
            <div class="stat">
                <div class="stat__value small">{{ formatDateTime(organization.last_parsed_at) }}</div>
                <div class="stat__label">последнее обновление</div>
            </div>
        </div>
    </div>
</template>
