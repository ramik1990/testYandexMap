<?php

namespace Tests\Feature;

use App\Enums\ParseStatus;
use App\Jobs\ParseOrganizationJob;
use App\Models\Organization;
use App\Models\ParseRun;
use App\Models\Review;
use App\Services\YandexMaps\YandexHttpClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeYandexHttpClient;
use Tests\TestCase;

class PartialParseTest extends TestCase
{
    use RefreshDatabase;

    public function test_broken_page_keeps_collected_reviews_and_marks_run_partial(): void
    {
        $organization = $this->organization();
        $run = $organization->parseRuns()->create(['status' => ParseStatus::Pending]);

        $http = $this->fake(fn (int $page) => $page === 1
            ? $this->payload($this->reviews(50))
            : $this->payload([['stars' => 5, 'text' => 'без идентификатора']]));

        $this->runJob($http, $run);

        $run->refresh();
        $organization->refresh();

        $this->assertSame(ParseStatus::Partial, $run->status);
        $this->assertSame('markup_changed', $run->error_code);
        $this->assertSame([1, 2], $http->requestedPages);
        $this->assertSame(50, $organization->reviews()->count());
        $this->assertSame('Яндекс', $organization->title);
        $this->assertSame(4.9, (float) $organization->rating);
        $this->assertSame(21218, $organization->rating_count);
        $this->assertSame(5859, $organization->review_count);
        $this->assertNotNull($organization->last_parsed_at);

        $changes = $organization->snapshots()->latest('id')->first()->changes;

        $this->assertTrue($changes['partial']);
        $this->assertNull($changes['missing']);
        $this->assertSame(50, $changes['added']);
    }

    public function test_review_without_rating_or_date_is_stored_and_run_completes(): void
    {
        $organization = $this->organization();
        $run = $organization->parseRuns()->create(['status' => ParseStatus::Pending]);

        $http = $this->fake(function (int $page) {
            $reviews = $this->reviews(50, $page);

            if ($page === 3) {
                $reviews[0] = ['reviewId' => 'bare-3-1', 'text' => 'Отзыв без оценки и даты'];
            }

            return $this->payload($reviews);
        });

        $this->runJob($http, $run);

        $run->refresh();

        $this->assertSame(ParseStatus::Completed, $run->status);
        $this->assertNull($run->error_code);
        $this->assertSame(600, $organization->reviews()->count());
        $this->assertSame(600, $run->reviews_fetched);

        $bare = Review::where('external_id', 'bare-3-1')->firstOrFail();

        $this->assertNull($bare->rating);
        $this->assertNull($bare->published_at);
        $this->assertSame('Пользователь Яндекса', $bare->author_name);
        $this->assertSame('Отзыв без оценки и даты', $bare->text);
    }

    public function test_dropped_review_marks_run_partial_but_still_counts_missing(): void
    {
        $organization = $this->organization();
        Review::factory()->for($organization)->create(['external_id' => 'gone', 'last_seen_at' => now()->subDay()]);

        $run = $organization->parseRuns()->create(['status' => ParseStatus::Pending]);

        $http = $this->fake(function (int $page) {
            $reviews = $this->reviews(50, $page);

            if ($page === 2) {
                $reviews[0] = ['stars' => 5, 'text' => 'без идентификатора'];
            }

            return $this->payload($reviews);
        });

        $this->runJob($http, $run);

        $run->refresh();
        $changes = $organization->snapshots()->latest('id')->first()->changes;

        $this->assertSame(ParseStatus::Partial, $run->status);
        $this->assertSame('reviews_skipped', $run->error_code);
        $this->assertSame(599, $organization->reviews()->where('external_id', '!=', 'gone')->count());
        $this->assertFalse($changes['partial']);
        $this->assertSame(1, $changes['missing']);
    }

    public function test_clean_run_stays_completed(): void
    {
        $organization = $this->organization();
        $run = $organization->parseRuns()->create(['status' => ParseStatus::Pending]);

        $this->runJob($this->fake(fn (int $page) => $this->payload($this->reviews(50, $page))), $run);

        $run->refresh();

        $this->assertSame(ParseStatus::Completed, $run->status);
        $this->assertNull($run->error_code);
        $this->assertSame(600, $organization->reviews()->count());
        $this->assertSame(0, $organization->snapshots()->latest('id')->first()->changes['missing']);
    }

    private function organization(): Organization
    {
        return Organization::factory()->create([
            'yandex_id' => '1124715036',
            'title' => null,
            'rating' => null,
            'rating_count' => null,
            'review_count' => null,
        ]);
    }

    private function runJob(FakeYandexHttpClient $http, ParseRun $run): void
    {
        $this->app->instance(YandexHttpClient::class, $http);
        $this->app->call([new ParseOrganizationJob($run), 'handle']);
    }

    private function fake(callable $reviews): FakeYandexHttpClient
    {
        return new FakeYandexHttpClient(file_get_contents(__DIR__.'/../Fixtures/organization_page.html'), $reviews(...));
    }

    private function payload(array $reviews): array
    {
        return ['data' => ['reviews' => $reviews, 'params' => ['count' => 600]]];
    }

    private function reviews(int $count, int $page = 1): array
    {
        return array_map(fn (int $index) => [
            'reviewId' => "p{$page}-{$index}",
            'author' => ['name' => "Автор {$page}-{$index}", 'avatarUrl' => 'https://avatars.mds.yandex.net/{size}'],
            'rating' => 5,
            'text' => 'Хорошее место',
            'updatedTime' => '2026-09-13T10:00:00.000Z',
            'reactions' => ['likes' => 1, 'dislikes' => 0],
        ], range(1, $count));
    }
}
