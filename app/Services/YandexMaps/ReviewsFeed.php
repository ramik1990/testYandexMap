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
    private const ID_KEYS = ['reviewId', 'id'];

    private const DATE_KEYS = ['updatedTime', 'createdTime', 'time'];

    private const MIN_RATING = 1;

    private const MAX_RATING = 5;

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
            $review = is_array($item) ? $this->mapReview($item, $page) : $this->skip($item, $page, 'элемент списка не является объектом');

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
        $id = $this->first($item, self::ID_KEYS);

        if ($id === null) {
            return $this->skip($item, $page, 'нет идентификатора отзыва');
        }

        return new ReviewData(
            $id,
            $this->string(Arr::get($item, 'author.name')) ?: 'Пользователь Яндекса',
            $this->avatar($this->string(Arr::get($item, 'author.avatarUrl'))),
            $this->string(Arr::get($item, 'author.professionLevel')),
            $this->rating($item, $page),
            $this->string($item['text'] ?? null) ?? '',
            $this->date($item, self::DATE_KEYS, $page),
            (int) Arr::get($item, 'reactions.likes', 0),
            (int) Arr::get($item, 'reactions.dislikes', 0),
            $this->string(Arr::get($item, 'businessComment.text')),
            $this->date($item, ['businessComment.updatedTime'], $page),
        );
    }

    private function rating(array $item, int $page): ?int
    {
        $rating = $item['rating'] ?? null;

        if (! is_numeric($rating)) {
            return $rating === null ? null : $this->note($item, $page, 'оценка не числовая, сохраняем отзыв без оценки');
        }

        $rating = (int) round((float) $rating);

        if ($rating < self::MIN_RATING || $rating > self::MAX_RATING) {
            return $this->note($item, $page, "оценка вне диапазона {$rating}, сохраняем отзыв без оценки");
        }

        return $rating;
    }

    private function date(array $item, array $keys, int $page): ?CarbonImmutable
    {
        foreach ($keys as $key) {
            $value = Arr::get($item, $key);

            if (blank($value) || ! is_scalar($value)) {
                continue;
            }

            try {
                return CarbonImmutable::parse($value);
            } catch (Throwable $e) {
                $this->note($item, $page, "не разобрана дата {$key}: ".$e->getMessage());
            }
        }

        return null;
    }

    private function first(array $item, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $this->string(Arr::get($item, $key));

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function string(mixed $value): ?string
    {
        return is_scalar($value) ? (string) $value : null;
    }

    private function skip(mixed $item, int $page, string $reason): ?ReviewData
    {
        return $this->write('Отзыв пропущен', is_array($item) ? $item : [], $page, $reason);
    }

    private function note(array $item, int $page, string $reason): ?ReviewData
    {
        return $this->write('Отзыв разобран частично', $item, $page, $reason);
    }

    private function write(string $message, array $item, int $page, string $reason): ?ReviewData
    {
        Log::channel('parser')->warning($message, [
            'business_id' => $this->businessId,
            'page' => $page,
            'review_id' => $this->first($item, self::ID_KEYS),
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
