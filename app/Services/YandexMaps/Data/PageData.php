<?php

namespace App\Services\YandexMaps\Data;

final class PageData
{
    public function __construct(
        public readonly OrganizationInfo $organization,
        public readonly PageSession $session,
    ) {
    }
}
