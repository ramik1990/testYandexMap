<?php

namespace App\Services\YandexMaps\Data;

final class ParseResult
{
    public function __construct(
        public readonly OrganizationInfo $organization,
        public readonly array $reviews,
    ) {
    }
}
