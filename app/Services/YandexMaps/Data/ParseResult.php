<?php

namespace App\Services\YandexMaps\Data;

final class ParseResult
{
    public function __construct(
        public readonly OrganizationInfo $organization,
        public readonly array $reviews,
        public readonly ?ParseWarning $warning = null,
        public readonly bool $interrupted = false,
    ) {
    }

    public function traversedAll(): bool
    {
        return ! $this->interrupted;
    }
}
