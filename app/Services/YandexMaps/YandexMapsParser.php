<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Data\ParseResult;
use App\Services\YandexMaps\Data\ParseWarning;
use App\Services\YandexMaps\Exceptions\EmptyResponseException;
use App\Services\YandexMaps\Exceptions\ParserException;
use Closure;
use Illuminate\Support\Facades\Log;

final class YandexMapsParser
{
    public function __construct(
        private readonly YandexHttpClient $http,
        private readonly PageExtractor $extractor,
        private readonly RequestSigner $signer,
        private readonly array $config,
    ) {
    }

    public function parse(string $businessId, ?Closure $onProgress = null): ParseResult
    {
        try {
            return $this->doParse($businessId, $onProgress ?? fn () => null);
        } catch (ParserException $e) {
            Log::channel('parser')->error($e->getMessage(), [
                'business_id' => $businessId,
                'code' => $e->errorCode(),
                'retryable' => $e->isRetryable(),
            ] + $e->context());

            throw $e;
        }
    }

    private function doParse(string $businessId, Closure $onProgress): ParseResult
    {
        $entryUrl = sprintf('https://%s/maps/org/%s/reviews/', $this->config['entry_host'], $businessId);
        $page = $this->extractor->extract($this->http->fetchPage($entryUrl), $businessId, $this->http->lastEffectiveUrl());

        $feed = new ReviewsFeed($this->http, $this->signer, $page->session, $businessId, $this->config['page_size'], $this->config['ranking']);

        $reviews = [];
        $number = 1;
        $pagesTotal = null;
        $skipped = 0;
        $warning = null;
        $interrupted = false;

        while (true) {
            $this->http->throttle();

            try {
                $reviewsPage = $feed->fetchPage($number);
            } catch (ParserException $e) {
                if ($reviews === [] || $e->isRetryable()) {
                    throw $e;
                }

                $warning = new ParseWarning($e->errorCode(), $e->getMessage());
                $interrupted = true;

                Log::channel('parser')->warning('Сбор остановлен на странице '.$number.', сохраняем уже полученные отзывы', [
                    'business_id' => $businessId,
                    'reviews' => count($reviews),
                    'code' => $e->errorCode(),
                ] + $e->context());

                break;
            }

            if ($number === 1 && $reviewsPage->isEmpty() && ($page->organization->reviewCount ?? 0) > 0) {
                throw new EmptyResponseException('Отзывы не получены, хотя карточка сообщает об их наличии.', [
                    'review_count' => $page->organization->reviewCount,
                ]);
            }

            foreach ($reviewsPage->reviews as $review) {
                $reviews[$review->id] = $review;
            }

            $skipped += $reviewsPage->skipped;
            $pagesTotal ??= $this->expectedPages($reviewsPage->totalCount);
            $onProgress($number, $pagesTotal, count($reviews), $reviewsPage->reviews);

            if ($reviewsPage->isEmpty() || $number >= $pagesTotal || count($reviews) >= $this->config['max_reviews']) {
                break;
            }

            $number++;
        }

        if ($warning === null && $skipped > 0) {
            $warning = new ParseWarning('reviews_skipped', "Пропущено отзывов с неожиданной структурой: {$skipped}.");
        }

        Log::channel('parser')->info('Организация распарсена', [
            'business_id' => $businessId,
            'reviews' => count($reviews),
            'pages' => $number,
            'skipped' => $skipped,
            'warning' => $warning?->code,
        ]);

        return new ParseResult($page->organization, array_values($reviews), $warning, $interrupted);
    }

    private function expectedPages(int $totalCount): int
    {
        $expected = min($totalCount, $this->config['max_reviews']);

        return max(1, (int) ceil($expected / $this->config['page_size']));
    }
}
