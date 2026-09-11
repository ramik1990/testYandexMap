<?php

namespace App\Console\Commands;

use App\Services\YandexMaps\Exceptions\ParserException;
use App\Services\YandexMaps\UrlResolver;
use App\Services\YandexMaps\YandexMapsParser;
use Illuminate\Console\Command;

class ParseOrganizationCommand extends Command
{
    protected $signature = 'yandex:parse {url : Ссылка на карточку организации или её числовой id}';

    protected $description = 'Синхронно собирает отзывы организации из Яндекс.Карт и выводит сводку (для отладки парсера)';

    public function handle(UrlResolver $resolver, YandexMapsParser $parser): int
    {
        $url = $this->argument('url');

        try {
            $businessId = ctype_digit($url) ? $url : $resolver->resolve($url);
            $result = $parser->parse($businessId, fn (int $done, int $total, int $fetched) => $this->line("Страница {$done}/{$total}, получено отзывов: {$fetched}"));
        } catch (ParserException $e) {
            $this->error("[{$e->errorCode()}] {$e->getMessage()}");

            return self::FAILURE;
        }

        $organization = $result->organization;
        $this->table(['Поле', 'Значение'], [
            ['id', $organization->id],
            ['Название', $organization->title],
            ['Адрес', $organization->address],
            ['Рейтинг', $organization->rating],
            ['Оценок', $organization->ratingCount],
            ['Отзывов на Яндексе', $organization->reviewCount],
            ['Отзывов получено', count($result->reviews)],
        ]);

        return self::SUCCESS;
    }
}
