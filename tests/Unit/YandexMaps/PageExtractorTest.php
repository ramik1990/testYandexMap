<?php

namespace Tests\Unit\YandexMaps;

use App\Services\YandexMaps\Exceptions\MarkupChangedException;
use App\Services\YandexMaps\PageExtractor;
use PHPUnit\Framework\TestCase;

class PageExtractorTest extends TestCase
{
    private PageExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new PageExtractor();
    }

    public function test_extracts_organization_and_session_from_state_json(): void
    {
        $page = $this->extractor->extract($this->fixture('organization_page.html'), '1124715036', 'https://yandex.kz/maps/org/1124715036/reviews/');

        $this->assertSame('Яндекс', $page->organization->title);
        $this->assertSame('Москва, улица Льва Толстого, 16', $page->organization->address);
        $this->assertSame(4.9, $page->organization->rating);
        $this->assertSame(21218, $page->organization->ratingCount);
        $this->assertSame(5859, $page->organization->reviewCount);
        $this->assertSame('yandex.kz', $page->session->host);
        $this->assertSame('token:1', $page->session->csrfToken);
        $this->assertSame('sess-1', $page->session->sessionId);
        $this->assertSame('ru_RU', $page->session->locale);
    }

    public function test_falls_back_to_microdata_when_state_has_no_organization(): void
    {
        $page = $this->extractor->extract($this->fixture('organization_page_microdata.html'), '42', 'https://yandex.ru/maps/org/42/');

        $this->assertSame('Кафе Ромашка — Яндекс Карты', $page->organization->title);
        $this->assertSame(4.3, $page->organization->rating);
        $this->assertSame(40, $page->organization->ratingCount);
        $this->assertSame(12, $page->organization->reviewCount);
    }

    public function test_detects_missing_state_script(): void
    {
        $this->expectException(MarkupChangedException::class);
        $this->extractor->extract('<html><body><h1>Яндекс Карты</h1></body></html>', '42', 'https://yandex.ru/maps/org/42/');
    }

    public function test_detects_missing_tokens(): void
    {
        $this->expectException(MarkupChangedException::class);
        $this->extractor->extract('<script type="application/json" class="state-view">{"config":{"locale":"ru_RU"}}</script>', '42', 'https://yandex.ru/maps/org/42/');
    }

    public function test_detects_missing_organization_data(): void
    {
        $this->expectException(MarkupChangedException::class);
        $this->extractor->extract('<script type="application/json" class="state-view">{"config":{"csrfToken":"t","requestId":"r"},"stack":[]}</script>', '42', 'https://yandex.ru/maps/org/42/');
    }

    private function fixture(string $name): string
    {
        return file_get_contents(__DIR__.'/../../Fixtures/'.$name);
    }
}
