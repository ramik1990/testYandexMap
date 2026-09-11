<?php

namespace App\Services\YandexMaps\Data;

final class PageSession
{
    public function __construct(
        public readonly string $host,
        public readonly string $csrfToken,
        public readonly string $sessionId,
        public readonly string $locale,
        public readonly string $reqId,
    ) {
    }

    public function withCsrfToken(string $csrfToken): self
    {
        return new self($this->host, $csrfToken, $this->sessionId, $this->locale, $this->reqId);
    }
}
