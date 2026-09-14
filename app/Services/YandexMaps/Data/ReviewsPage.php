<?php

namespace App\Services\YandexMaps\Data;

final class ReviewsPage
{
    public function __construct(
        public readonly int $page,
        public readonly array $reviews,
        public readonly int $totalCount,
        public readonly int $skipped = 0,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->reviews === [];
    }
}
