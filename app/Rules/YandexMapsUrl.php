<?php

namespace App\Rules;

use App\Services\YandexMaps\UrlResolver;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YandexMapsUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! app(UrlResolver::class)->isYandexMapsUrl($value)) {
            $fail('Укажите ссылку на карточку организации в Яндекс.Картах, например https://yandex.ru/maps/org/.../123456789/');
        }
    }
}
