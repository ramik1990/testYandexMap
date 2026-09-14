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
    public function test_keeps_reviews_without_rating_author_or_date(): void
    {
        $feed = $this->feed([
            $this->review('a'),
            $this->review('b', ['rating' => null]),
            $this->review('c', ['updatedTime' => 'позавчера']),
            $this->review('d', ['author' => null]),
            ['stars' => 5, 'text' => 'Отзыв без идентификатора'],
        ]);

        $page = $feed->fetchPage(1);

        $this->assertCount(4, $page->reviews);
        $this->assertSame(1, $page->skipped);
        $this->assertSame(['a', 'b', 'c', 'd'], array_map(fn ($review) => $review->id, $page->reviews));
        $this->assertNull($page->reviews[1]->rating);
        $this->assertNull($page->reviews[2]->publishedAt);
        $this->assertSame('Пользователь Яндекса', $page->reviews[3]->authorName);
    }

    public function test_keeps_a_review_that_has_nothing_but_an_identifier(): void
    {
        $review = $this->feed([['reviewId' => 'bare']])->fetchPage(1)->reviews[0];

        $this->assertSame('bare', $review->id);
        $this->assertSame('Пользователь Яндекса', $review->authorName);
        $this->assertNull($review->rating);
        $this->assertNull($review->publishedAt);
        $this->assertSame('', $review->text);
        $this->assertSame(0, $review->likes);
    }

    public function test_normalises_rating_and_falls_back_to_created_time(): void
    {
        $page = $this->feed([
            $this->review('a', ['rating' => '4']),
            $this->review('b', ['rating' => 42]),
            $this->review('c', ['rating' => -1]),
            $this->review('d', ['updatedTime' => null, 'createdTime' => '2026-01-05T08:30:00.000Z']),
        ])->fetchPage(1);

        $this->assertSame(4, $page->reviews[0]->rating);
        $this->assertNull($page->reviews[1]->rating);
        $this->assertNull($page->reviews[2]->rating);
        $this->assertSame('2026-01-05', $page->reviews[3]->publishedAt->toDateString());
        $this->assertSame(0, $page->skipped);
    }

    public function test_throws_when_the_whole_page_cannot_be_parsed(): void
    {
        $feed = $this->feed([
            ['stars' => 5],
            ['stars' => 4],
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
