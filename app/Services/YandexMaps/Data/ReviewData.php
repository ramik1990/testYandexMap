<?php

namespace App\Services\YandexMaps\Data;

use Carbon\CarbonImmutable;

final class ReviewData
{
    public function __construct(
        public readonly string $id,
        public readonly string $authorName,
        public readonly ?string $authorAvatar,
        public readonly ?string $authorLevel,
        public readonly int $rating,
        public readonly string $text,
        public readonly CarbonImmutable $publishedAt,
        public readonly int $likes,
        public readonly int $dislikes,
        public readonly ?string $businessReply,
        public readonly ?CarbonImmutable $businessReplyAt,
    ) {
    }

    public function contentHash(): string
    {
        return sha1(implode('|', [
            $this->rating,
            $this->text,
            $this->publishedAt->toIso8601String(),
            $this->likes,
            $this->dislikes,
            $this->businessReply,
        ]));
    }
}
