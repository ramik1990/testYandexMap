<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Data\PageSession;
use App\Services\YandexMaps\Data\ReviewData;
use App\Services\YandexMaps\Data\ReviewsPage;
use App\Services\YandexMaps\Exceptions\MarkupChangedException;
use App\Services\YandexMaps\Exceptions\SourceUnavailableException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

final class ReviewsFeed
{
    private const REQUIRED_REVIEW_KEYS = ['reviewId', 'author', 'rating', 'updatedTime'];

    public function __construct(
        private readonly YandexHttpClient $http,
        private readonly RequestSigner $signer,
        private PageSession $session,
        private readonly string $businessId,
        private readonly int $pageSize,
        private readonly string $ranking,
    ) {
    }

    public function fetchPage(int $page): ReviewsPage
    {
        $payload = $this->request($page);

        if ($this->isCsrfRefresh($payload)) {
            $this->session = $this->session->withCsrfToken($payload['csrfToken']);
            $payload = $this->request($page);

            if ($this->isCsrfRefresh($payload)) {
                throw new MarkupChangedException('Яндекс повторно требует новый csrfToken: схема подписи запросов изменилась.');
            }
        }

        if (isset($payload['error'])) {
            return $this->handleError($payload['error'], $page);
        }

        $reviews = Arr::get($payload, 'data.reviews');
        $total = Arr::get($payload, 'data.params.count');

        if (! is_array($reviews) || ! is_numeric($total)) {
            throw new MarkupChangedException('Ответ fetchReviews не содержит data.reviews или data.params.count.', [
                'keys' => array_keys($payload),
                'data_keys' => array_keys(Arr::get($payload, 'data', [])),
            ]);
        }

        return $this->mapPage($page, $reviews, (int) $total);
    }

    private function mapPage(int $page, array $items, int $total): ReviewsPage
    {
        $reviews = [];
        $skipped = 0;

        foreach ($items as $item) {
            $review = is_array($item) ? $this->mapReview($item, $page) : null;

            if ($review === null) {
                $skipped++;

                continue;
            }

            $reviews[] = $review;
        }

        if ($reviews === [] && $skipped > 0) {
            throw new MarkupChangedException("Ни один отзыв на странице {$page} не удалось разобрать: изменилась структура отзыва.", [
                'business_id' => $this->businessId,
                'skipped' => $skipped,
                'sample_keys' => array_keys((array) reset($items)),
            ]);
        }

        return new ReviewsPage($page, $reviews, $total, $skipped);
    }

    private function request(int $page): array
    {
        $query = $this->signer->signedQuery([
            'ajax' => 1,
            'businessId' => $this->businessId,
            'csrfToken' => $this->session->csrfToken,
            'locale' => $this->session->locale,
            'page' => $page,
            'pageSize' => $this->pageSize,
            'ranking' => $this->ranking,
            'reqId' => $this->session->reqId,
            'sessionId' => $this->session->sessionId,
        ]);

        $host = $this->session->host;

        return $this->http->fetchJson(
            "https://{$host}/maps/api/business/fetchReviews?{$query}",
            "https://{$host}/maps/org/{$this->businessId}/reviews/",
        );
    }

    private function isCsrfRefresh(array $payload): bool
    {
        return isset($payload['csrfToken']) && ! isset($payload['data']);
    }

    private function handleError(mixed $error, int $page): ReviewsPage
    {
        if ($page > 1) {
            Log::channel('parser')->info('fetchReviews вернул ошибку за пределами доступного окна отзывов', [
                'business_id' => $this->businessId,
                'page' => $page,
                'error' => $error,
            ]);

            return new ReviewsPage($page, [], 0);
        }

        throw new SourceUnavailableException('fetchReviews вернул ошибку на первой странице.', ['error' => $error]);
    }

    private function mapReview(array $item, int $page): ?ReviewData
    {
        $missing = array_values(array_filter(
            self::REQUIRED_REVIEW_KEYS,
            static fn (string $key) => ! array_key_exists($key, $item),
        ));

        if ($missing !== []) {
            return $this->skip($item, $page, 'нет обязательных полей: '.implode(', ', $missing));
        }

        if (! is_numeric($item['rating'])) {
            return $this->skip($item, $page, 'оценка не числовая');
        }

        try {
            $publishedAt = CarbonImmutable::parse($item['updatedTime']);
            $replyAt = Arr::get($item, 'businessComment.updatedTime');
            $replyAt = $replyAt ? CarbonImmutable::parse($replyAt) : null;
        } catch (Throwable $e) {
            return $this->skip($item, $page, 'некорректная дата: '.$e->getMessage());
        }

        return new ReviewData(
            (string) $item['reviewId'],
            (string) (Arr::get($item, 'author.name') ?: 'Пользователь Яндекса'),
            $this->avatar(Arr::get($item, 'author.avatarUrl')),
            Arr::get($item, 'author.professionLevel'),
            (int) $item['rating'],
            (string) ($item['text'] ?? ''),
            $publishedAt,
            (int) Arr::get($item, 'reactions.likes', 0),
            (int) Arr::get($item, 'reactions.dislikes', 0),
            Arr::get($item, 'businessComment.text'),
            $replyAt,
        );
    }

    private function skip(array $item, int $page, string $reason): ?ReviewData
    {
        Log::channel('parser')->warning('Отзыв пропущен', [
            'business_id' => $this->businessId,
            'page' => $page,
            'review_id' => $item['reviewId'] ?? null,
            'reason' => $reason,
            'keys' => array_keys($item),
        ]);

        return null;
    }

    private function avatar(?string $url): ?string
    {
        return $url ? str_replace('{size}', 'islands-68', $url) : null;
    }
}
