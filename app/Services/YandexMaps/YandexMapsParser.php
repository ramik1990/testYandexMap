<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Data\ParseResult;
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

        while (true) {
            $this->http->throttle();
            $reviewsPage = $feed->fetchPage($number);

            if ($number === 1 && $reviewsPage->isEmpty() && ($page->organization->reviewCount ?? 0) > 0) {
                throw new EmptyResponseException('Отзывы не получены, хотя карточка сообщает об их наличии.', [
                    'review_count' => $page->organization->reviewCount,
                ]);
            }

            foreach ($reviewsPage->reviews as $review) {
                $reviews[$review->id] = $review;
            }

            $pagesTotal ??= $this->expectedPages($reviewsPage->totalCount);
            $onProgress($number, $pagesTotal, count($reviews), $reviewsPage->reviews);

            if ($reviewsPage->isEmpty() || $number >= $pagesTotal || count($reviews) >= $this->config['max_reviews']) {
                break;
            }

            $number++;
        }

        Log::channel('parser')->info('Организация распарсена', [
            'business_id' => $businessId,
            'reviews' => count($reviews),
            'pages' => $number,
        ]);

        return new ParseResult($page->organization, array_values($reviews));
    }

    private function expectedPages(int $totalCount): int
    {
        $expected = min($totalCount, $this->config['max_reviews']);

        return max(1, (int) ceil($expected / $this->config['page_size']));
    }
}
