<?php

namespace Tests\Unit\YandexMaps;

use App\Services\YandexMaps\Data\PageSession;
use App\Services\YandexMaps\Exceptions\MarkupChangedException;
use App\Services\YandexMaps\RequestSigner;
use App\Services\YandexMaps\ReviewsFeed;
use Tests\Fakes\FakeYandexHttpClient;
use Tests\TestCase;

class ReviewsFeedTest extends TestCase
{
    public function test_skips_broken_reviews_and_keeps_the_rest(): void
    {
        $feed = $this->feed([
            $this->review('a'),
            $this->review('b', ['rating' => null]),
            $this->review('c', ['updatedTime' => 'позавчера']),
            $this->review('d'),
            ['author' => ['name' => 'Без идентификатора']],
        ]);

        $page = $feed->fetchPage(1);

        $this->assertCount(2, $page->reviews);
        $this->assertSame(3, $page->skipped);
        $this->assertSame(['a', 'd'], array_map(fn ($review) => $review->id, $page->reviews));
        $this->assertSame(600, $page->totalCount);
    }

    public function test_throws_when_the_whole_page_cannot_be_parsed(): void
    {
        $feed = $this->feed([
            ['id' => 1, 'stars' => 5],
            ['id' => 2, 'stars' => 4],
        ]);

        $this->expectException(MarkupChangedException::class);

        $feed->fetchPage(1);
    }

    public function test_keeps_an_empty_page_without_raising(): void
    {
        $page = $this->feed([])->fetchPage(1);

        $this->assertSame([], $page->reviews);
        $this->assertSame(0, $page->skipped);
    }

    private function feed(array $items): ReviewsFeed
    {
        $http = new FakeYandexHttpClient('', fn () => ['data' => ['reviews' => $items, 'params' => ['count' => 600]]]);
        $session = new PageSession('yandex.kz', 'token:1', 'sess-1', 'ru_RU', 'req-1');

        return new ReviewsFeed($http, new RequestSigner(), $session, '1124715036', 50, 'by_time');
    }

    private function review(string $id, array $overrides = []): array
    {
        return array_replace([
            'reviewId' => $id,
            'author' => ['name' => 'Автор '.$id, 'avatarUrl' => 'https://avatars.mds.yandex.net/{size}', 'professionLevel' => 'Знаток города 3 уровня'],
            'rating' => 5,
            'text' => 'Текст отзыва '.$id,
            'updatedTime' => '2026-09-13T10:00:00.000Z',
            'reactions' => ['likes' => 2, 'dislikes' => 1],
        ], $overrides);
    }
}
