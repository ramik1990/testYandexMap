<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Exceptions\InvalidUrlException;

final class UrlResolver
{
    private const HOST_PATTERN = '~^(?:www\.|maps\.|m\.)?yandex\.(?:ru|com|kz|by|uz|ua|az|ge|kg|tj|tm|md|lv|lt|ee|fr|com\.tr|com\.am|com\.ge)$~i';

    private const PATH_PATTERNS = [
        '#/org/(?:[^/]+/)?(\d{5,})(?:/|$)#',
        '#^/profile/(\d{5,})(?:/|$)#',
    ];

    private const SHORT_LINK_PATTERN = '#^/maps/-/[A-Za-z0-9~_-]+#';

    public function __construct(private readonly YandexHttpClient $http)
    {
    }

    public function isYandexMapsUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && preg_match(self::HOST_PATTERN, $host) === 1;
    }

    public function resolve(string $url): string
    {
        return $this->resolveInternal($url, true);
    }

    private function resolveInternal(string $url, bool $followShortLink): string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! $this->isYandexMapsUrl($url)) {
            throw new InvalidUrlException('Ссылка не ведёт на Яндекс.Карты.', ['url' => $url]);
        }

        $path = $parts['path'] ?? '/';
        parse_str($parts['query'] ?? '', $query);

        foreach (self::PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $path, $match)) {
                return $match[1];
            }
        }

        if (isset($query['oid']) && preg_match('~^\d{5,}$~', (string) $query['oid'])) {
            return (string) $query['oid'];
        }

        if ($followShortLink && preg_match(self::SHORT_LINK_PATTERN, $path)) {
            return $this->resolveInternal($this->http->resolveRedirect($url), false);
        }

        throw new InvalidUrlException('В ссылке не найден идентификатор организации Яндекс.Карт.', ['url' => $url]);
    }
}
