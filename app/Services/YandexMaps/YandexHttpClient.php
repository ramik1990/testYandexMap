<?php

namespace App\Services\YandexMaps;

use App\Services\YandexMaps\Exceptions\BlockedException;
use App\Services\YandexMaps\Exceptions\MarkupChangedException;
use App\Services\YandexMaps\Exceptions\SourceUnavailableException;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;

class YandexHttpClient
{
    private const CAPTCHA_MARKERS = ['showcaptcha', 'SmartCaptcha', 'captcha-page', 'CheckboxCaptcha'];

    private readonly Client $client;

    private string $lastEffectiveUrl = '';

    public function __construct(private readonly array $config)
    {
        $this->client = new Client([
            RequestOptions::COOKIES => new CookieJar(),
            RequestOptions::TIMEOUT => $config['timeout'],
            RequestOptions::CONNECT_TIMEOUT => 10,
            RequestOptions::HTTP_ERRORS => false,
            RequestOptions::PROXY => $config['proxy'] ?: null,
            RequestOptions::HEADERS => [
                'User-Agent' => Arr::random($config['user_agents']),
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ],
            RequestOptions::ALLOW_REDIRECTS => ['max' => 10, 'track_redirects' => true],
        ]);
    }

    public function fetchPage(string $url): string
    {
        $response = $this->request($url, ['Accept' => 'text/html,application/xhtml+xml']);
        $body = (string) $response->getBody();

        if ($response->getStatusCode() === 404) {
            throw new SourceUnavailableException('Страница организации не найдена (404).', ['url' => $url]);
        }

        if ($this->looksBlocked($response, $body)) {
            throw new BlockedException('Яндекс показал капчу или заблокировал запрос.', ['url' => $this->lastEffectiveUrl]);
        }

        $this->assertSuccessful($response, $url);

        if (trim($body) === '') {
            throw new SourceUnavailableException('Пустой ответ от Яндекс.Карт.', ['url' => $url]);
        }

        return $body;
    }

    public function fetchJson(string $url, string $referer): array
    {
        $response = $this->request($url, ['Accept' => 'application/json', 'Referer' => $referer]);
        $body = (string) $response->getBody();

        if ($this->looksBlocked($response, $body)) {
            throw new BlockedException('Яндекс заблокировал запрос к внутреннему API.', ['url' => $url]);
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            if ($response->getStatusCode() >= 500) {
                throw new SourceUnavailableException('Внутренний API Яндекс.Карт недоступен.', ['status' => $response->getStatusCode()]);
            }

            throw new MarkupChangedException('Внутренний API вернул не JSON.', [
                'status' => $response->getStatusCode(),
                'body' => mb_substr($body, 0, 300),
            ]);
        }

        return $decoded;
    }

    public function resolveRedirect(string $url): string
    {
        $this->request($url, ['Accept' => 'text/html']);

        return $this->lastEffectiveUrl;
    }

    public function lastEffectiveUrl(): string
    {
        return $this->lastEffectiveUrl;
    }

    public function throttle(): void
    {
        usleep(random_int($this->config['delay_ms']['min'], $this->config['delay_ms']['max']) * 1000);
    }

    private function request(string $url, array $headers): ResponseInterface
    {
        $this->lastEffectiveUrl = $url;

        try {
            return $this->client->get($url, [
                RequestOptions::HEADERS => $headers,
                RequestOptions::ON_STATS => function (TransferStats $stats): void {
                    $this->lastEffectiveUrl = (string) $stats->getEffectiveUri();
                },
            ]);
        } catch (ConnectException|RequestException $e) {
            throw new SourceUnavailableException('Не удалось соединиться с Яндекс.Картами: '.$e->getMessage(), ['url' => $url], $e);
        }
    }

    private function looksBlocked(ResponseInterface $response, string $body): bool
    {
        if (in_array($response->getStatusCode(), [403, 429], true)) {
            return true;
        }

        if (str_contains($this->lastEffectiveUrl, 'showcaptcha')) {
            return true;
        }

        $head = mb_substr($body, 0, 5000);

        foreach (self::CAPTCHA_MARKERS as $marker) {
            if (str_contains($head, $marker)) {
                return true;
            }
        }

        return false;
    }

    private function assertSuccessful(ResponseInterface $response, string $url): void
    {
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return;
        }

        throw new SourceUnavailableException("Яндекс.Карты ответили статусом {$status}.", ['url' => $url, 'status' => $status]);
    }
}
