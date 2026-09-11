<?php

namespace Tests\Unit\YandexMaps;

use App\Services\YandexMaps\Exceptions\InvalidUrlException;
use App\Services\YandexMaps\UrlResolver;
use App\Services\YandexMaps\YandexHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UrlResolverTest extends TestCase
{
    public static function urls(): array
    {
        return [
            ['https://yandex.ru/maps/org/yandeks/1124715036/', '1124715036'],
            ['https://yandex.ru/maps/org/yandeks/1124715036/reviews/?ll=37.5%2C55.7&z=16', '1124715036'],
            ['https://yandex.kz/maps/org/1124715036', '1124715036'],
            ['https://maps.yandex.ru/org/yandeks/1124715036/', '1124715036'],
            ['https://yandex.ru/maps/213/moscow/?ll=37.58&oid=1124715036&ol=biz', '1124715036'],
            ['https://yandex.ru/profile/1124715036/', '1124715036'],
        ];
    }

    #[DataProvider('urls')]
    public function test_extracts_business_id(string $url, string $expected): void
    {
        $this->assertSame($expected, $this->resolver()->resolve($url));
    }

    public function test_rejects_foreign_hosts(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->resolver()->resolve('https://2gis.ru/moscow/firm/70000001006809126');
    }

    public function test_rejects_yandex_url_without_id(): void
    {
        $this->expectException(InvalidUrlException::class);
        $this->resolver()->resolve('https://yandex.ru/maps/213/moscow/');
    }

    public function test_follows_short_links(): void
    {
        $http = $this->createMock(YandexHttpClient::class);
        $http->method('resolveRedirect')->willReturn('https://yandex.ru/maps/org/yandeks/1124715036/?utm_source=share');

        $this->assertSame('1124715036', (new UrlResolver($http))->resolve('https://yandex.ru/maps/-/CDabcXYZ'));
    }

    private function resolver(): UrlResolver
    {
        return new UrlResolver($this->createMock(YandexHttpClient::class));
    }
}
