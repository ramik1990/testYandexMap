<?php

namespace App\Services\YandexMaps\Exceptions;

class InvalidUrlException extends ParserException
{
    public function errorCode(): string
    {
        return 'invalid_url';
    }

    public function isRetryable(): bool
    {
        return false;
    }
}
