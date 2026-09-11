<?php

namespace App\Services\YandexMaps\Data;

final class OrganizationInfo
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
        public readonly ?string $address,
        public readonly ?float $rating,
        public readonly ?int $ratingCount,
        public readonly ?int $reviewCount,
    ) {
    }
}
