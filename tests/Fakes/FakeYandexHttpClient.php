<?php

namespace Tests\Fakes;

use App\Services\YandexMaps\YandexHttpClient;
use Closure;

class FakeYandexHttpClient extends YandexHttpClient
{
    public array $requestedPages = [];

    private string $effectiveUrl = '';

    public function __construct(private readonly string $html, private readonly Closure $reviews)
    {
        parent::__construct([
            'timeout' => 5,
            'proxy' => null,
            'user_agents' => ['test-agent'],
            'delay_ms' => ['min' => 0, 'max' => 0],
        ]);
    }

    public function fetchPage(string $url): string
    {
        $this->effectiveUrl = $url;

        return $this->html;
    }

    public function fetchJson(string $url, string $referer): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $page = (int) ($query['page'] ?? 1);
        $this->requestedPages[] = $page;

        return ($this->reviews)($page, $query);
    }

    public function lastEffectiveUrl(): string
    {
        return $this->effectiveUrl;
    }

    public function throttle(): void
    {
    }
}
