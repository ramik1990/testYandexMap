<?php

namespace App\Services\YandexMaps\Data;

final class ParseWarning
{
    public function __construct(
        public readonly string $code,
        public readonly string $message,
    ) {
    }
}
