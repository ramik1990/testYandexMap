<?php

namespace App\Providers;

use App\Services\YandexMaps\YandexHttpClient;
use App\Services\YandexMaps\YandexMapsParser;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(YandexHttpClient::class, fn () => new YandexHttpClient(config('yandex')));

        $this->app->bind(YandexMapsParser::class, fn ($app) => new YandexMapsParser(
            $app->make(YandexHttpClient::class),
            $app->make(\App\Services\YandexMaps\PageExtractor::class),
            $app->make(\App\Services\YandexMaps\RequestSigner::class),
            config('yandex'),
        ));
    }

    public function boot(): void
    {
    }
}
