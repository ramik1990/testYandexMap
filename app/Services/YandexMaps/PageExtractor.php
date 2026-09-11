<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Data\OrganizationInfo;
use App\Services\YandexMaps\Data\PageData;
use App\Services\YandexMaps\Data\PageSession;
use App\Services\YandexMaps\Exceptions\MarkupChangedException;
use Illuminate\Support\Arr;

final class PageExtractor
{
    private const STATE_PATTERN = '~<script[^>]+class="state-view"[^>]*>(.*?)</script>~su';

    public function extract(string $html, string $businessId, string $effectiveUrl): PageData
    {
        $state = $this->decodeState($html);
        $config = $state['config'] ?? null;

        if (! is_array($config)) {
            throw new MarkupChangedException('В состоянии страницы отсутствует блок config.', $this->debug($state));
        }

        $session = $this->extractSession($config, $effectiveUrl);
        $organization = $this->extractOrganization($state, $html, $businessId);

        return new PageData($organization, $session);
    }

    private function decodeState(string $html): array
    {
        if (! preg_match(self::STATE_PATTERN, $html, $match)) {
            throw new MarkupChangedException('Не найден JSON состояния страницы (script.state-view).', [
                'title' => $this->title($html),
            ]);
        }

        $state = json_decode($match[1], true);

        if (! is_array($state)) {
            throw new MarkupChangedException('JSON состояния страницы не декодируется: '.json_last_error_msg());
        }

        return $state;
    }

    private function extractSession(array $config, string $effectiveUrl): PageSession
    {
        $csrfToken = Arr::get($config, 'csrfToken');
        $sessionId = Arr::get($config, 'counters.analytics.sessionId') ?? Arr::get($config, 'requestId');
        $host = parse_url($effectiveUrl, PHP_URL_HOST);

        if (! is_string($csrfToken) || ! is_string($sessionId) || ! is_string($host)) {
            throw new MarkupChangedException('Не найдены csrfToken/sessionId в состоянии страницы.', [
                'config_keys' => array_keys($config),
            ]);
        }

        return new PageSession(
            $host,
            $csrfToken,
            $sessionId,
            (string) Arr::get($config, 'locale', 'ru_RU'),
            (string) Arr::get($config, 'requestId', $sessionId),
        );
    }

    private function extractOrganization(array $state, string $html, string $businessId): OrganizationInfo
    {
        $item = $this->findOrganizationItem($state, $businessId);

        if ($item !== null) {
            $rating = Arr::get($item, 'ratingData', []);

            return new OrganizationInfo(
                (string) $item['id'],
                (string) ($item['title'] ?? $item['shortTitle'] ?? ''),
                $item['fullAddress'] ?? $item['address'] ?? null,
                $this->number(Arr::get($rating, 'ratingValue')),
                $this->integer(Arr::get($rating, 'ratingCount')),
                $this->integer(Arr::get($rating, 'reviewCount')),
            );
        }

        $meta = $this->microdata($html);

        if ($meta === []) {
            throw new MarkupChangedException('Данные организации не найдены ни в JSON состояния, ни в микроразметке.', [
                'business_id' => $businessId,
                'title' => $this->title($html),
            ]);
        }

        return new OrganizationInfo(
            $businessId,
            $this->title($html),
            null,
            $this->number($meta['ratingValue'] ?? null),
            $this->integer($meta['ratingCount'] ?? null),
            $this->integer($meta['reviewCount'] ?? null),
        );
    }

    private function findOrganizationItem(array $state, string $businessId): ?array
    {
        $fallback = null;

        foreach (Arr::get($state, 'stack', []) as $entry) {
            foreach (Arr::get($entry, 'results.items', []) as $item) {
                if (! is_array($item) || ! isset($item['id'], $item['ratingData'])) {
                    continue;
                }

                if ((string) $item['id'] === $businessId) {
                    return $item;
                }

                $fallback ??= $item;
            }
        }

        return $fallback;
    }

    private function microdata(string $html): array
    {
        preg_match_all('~<meta\s+itemProp="(ratingValue|ratingCount|reviewCount)"\s+content="([^"]*)"~', $html, $matches, PREG_SET_ORDER);

        return array_column($matches, 2, 1);
    }

    private function title(string $html): string
    {
        return preg_match('~<title>(.*?)</title>~su', $html, $match) ? html_entity_decode(trim($match[1])) : '';
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? round((float) $value, 2) : null;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function debug(array $state): array
    {
        return ['state_keys' => array_slice(array_keys($state), 0, 20)];
    }
}
