<?php

namespace App\Services\YandexMaps\Exceptions;

class MarkupChangedException extends ParserException
{
    public function errorCode(): string
    {
        return 'markup_changed';
    }

    public function isRetryable(): bool
    {
        return false;
    }
}
